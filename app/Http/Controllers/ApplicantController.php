<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Response;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ApplicantController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $applicants = Applicant::latest()->paginate(10);
        return Inertia::render('admin/applicant/Applicant', ['applicants' => $applicants]);
    }

    public function streamApplicants()
    {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');

        $lastApplicantId = Applicant::latest()->first()->id ?? 0;

        while (true) {
            $applicants = Applicant::latest()->get(10);
            $totalApplicants = Applicant::count();
            $newApplicant = Applicant::latest()->first();

            // Send the current list of applicants and total count
            echo "data: " . json_encode([
                'applicants' => $applicants,
                'totalApplicants' => $totalApplicants,
                'newApplicant' => ($newApplicant && $newApplicant->id > $lastApplicantId) ? $newApplicant : null, // 🔥 Ensure it's sent
            ]) . "\n\n";

            if ($newApplicant && $newApplicant->id > $lastApplicantId) {
                $lastApplicantId = $newApplicant->id; // Update the last known applicant ID
            }

            ob_flush();
            flush();

            if (connection_aborted()) {
                break;
            }

            sleep(3); // Wait 3 seconds before fetching updates again
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('admin/applicant/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $validatedData = $request->validate([
            'full_name.first_name' => 'required|string|min:2|max:50',
            'full_name.middle_name' => 'required|string|min:2|max:50',
            'full_name.last_name' => 'required|string|min:2|max:50',
            'full_name.suffix' => 'nullable|string|min:1|max:10',
            'age' => 'required|integer|min:18|max:150',
            'date_of_birth' => 'required|date|before:today',
            'address.street' => 'required|string|min:2|max:50',
            'address.barangay' => 'required|string|min:2|max:50',
            'address.city' => 'required|string|min:2|max:50',
            'address.province' => 'required|string|min:2|max:50',
            'phone' => ['required', 'regex:/^09\\d{9}$/'],
            'birth_place' => 'required|string|min:2|max:50',
            'school' => 'required|string|min:2|max:50',
            'status' => 'required|in:Cleared,Pending,Not Cleared',
            'officer_name' => 'required|string|min:2|max:50',
            'file' => 'required|array',
            'file.*' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $fullName = implode(' ', array_filter([
            $validatedData['full_name']['first_name'] ?? '',
            $validatedData['full_name']['middle_name'] ?? '',
            $validatedData['full_name']['last_name'] ?? '',
            $validatedData['full_name']['suffix'] ?? '', // Ensure undefined or null values are excluded
        ]));

        $address = implode(', ', array_filter([
            $validatedData['address']['street'] ?? '',
            $validatedData['address']['barangay'] ?? '',
            $validatedData['address']['city'] ?? '',
            $validatedData['address']['province'] ?? '',
        ]));

        $applicant = Applicant::create([
            ...$validatedData,
            'full_name' => $fullName,
            'address' => $address,
        ]);

        $documentPaths = collect($request->file('file'))->mapWithKeys(function ($file, $key) {
            return ["{$key}_path" => $file->store('certificates', 'public')];
        })->toArray();

        DB::table('documents')->insert([
            'applicant_id' => $applicant->id,
            ...$documentPaths,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect('/manage-applicant')->with('message', 'Applicant created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        $search = $request->query('search');
        $status = $request->query('status');
        $sortOrder = $request->query('sort', 'desc'); // Default to descending

        $query = Applicant::query();

        // Apply search filter
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'LIKE', "%{$search}%")
                    ->orWhere('phone', 'LIKE', "%{$search}%")
                    ->orWhere('school', 'LIKE', "%{$search}%")
                    ->orWhere('date_of_birth', 'LIKE', "%{$search}%")
                    ->orWhere('officer_name', 'LIKE', "%{$search}%");
            });
        }

        // Apply status filter
        if (!empty($status)) {
            $query->where('status', $status);
        }

        $query->orderBy('created_at', $sortOrder);

        try {
            // Paginate results
            $result = $query->latest()->paginate(10)->appends($request->all());
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred while fetching applicants.'], 500);
        }

        // Return the paginated results with pagination links as JSON response
        return response()->json([
            'data' => $result->items(),
            'current_page' => $result->currentPage(),
            'last_page' => $result->lastPage(),
            'next_page_url' => $result->nextPageUrl(),
            'prev_page_url' => $result->previousPageUrl(),
        ]);
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $applicant = Applicant::findOrFail($id);
        $documents = Document::where('applicant_id', $id)->first();

        if (!$documents) {
            return Inertia::render('admin/applicant/Show', [
                'applicant' => $applicant,
                'documents' => null
            ]);
        }

        // Ensure correct storage path
        $certificates = collect([
            'barangay_cert' => $documents->barangay_cert_path,
            'pnp_clearance' => $documents->pnp_clearance_path,
            'rtc_clearance' => $documents->rtc_clearance_path,
            'school_cert' => $documents->school_cert_path,
            'derogatory_records' => $documents->derogatory_records_path,
        ])->mapWithKeys(function ($value, $key) {
            // Use correct storage path
            $url = $value ? asset("storage/certificates/" . basename($value)) : null;
            return [$key => $url];
        });

        return Inertia::render('admin/applicant/Show', [
            'applicant' => $applicant,
            'documents' => $certificates,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            \Log::info('Update Request Data:', $request->all());

            // ✅ Manually merge JSON payload
            $request->merge($request->json()->all());

            // ✅ Validate request data
            $validatedData = $request->validate([
                'full_name'     => 'nullable|string|max:255', // Allow empty strings
                'phone'         => 'nullable|string|max:20',
                'date_of_birth' => 'nullable|date',
                'age'           => 'nullable|integer|min:0',
                'address'       => 'nullable|string|max:255',
                'birth_place'   => 'nullable|string|max:255',
                'school'        => 'nullable|string|max:255',
                'status'        => ['nullable', Rule::in(['Cleared', 'Not Cleared', 'Pending'])],
                'officer_name'  => 'nullable|string|max:255',
            ]);

            \Log::info('Validated Data:', $validatedData);

            $applicant = Applicant::findOrFail($id);
            \Log::info('Applicant Before Update:', $applicant->toArray());

            // ✅ Ensure update happens
            $applicant->update($validatedData);

            \Log::info('Applicant After Update:', $applicant->toArray());

            return response()->json([
                'message'   => 'Applicant updated successfully',
                'applicant' => $applicant->fresh(),
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation Errors:', $e->errors());
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            \Log::error('Error updating applicant: ' . $e->getMessage());
            return response()->json(['message' => 'An error occurred while updating the applicant.'], 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $applicant = Applicant::findOrFail($id);
        $applicant->delete();

        return response()->json(['message' => 'Applicant deleted successfully'], 200);
    }
}
