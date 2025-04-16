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
            'first_name' => 'required|string|min:2|max:50',
            'middle_name' => 'required|string|min:2|max:50',
            'last_name' => 'required|string|min:2|max:50',
            'suffix' => 'nullable|string|min:1|max:10',
            'age' => 'required|integer|min:18|max:150',
            'date_of_birth' => 'required|date|before:today',
            'street' => 'required|string|min:2|max:50',
            'barangay' => 'required|string|min:2|max:50',
            'city' => 'required|string|min:2|max:50',
            'province' => 'required|string|min:2|max:50',
            'phone' => ['required', 'regex:/^09\\d{9}$/'],
            'birth_place' => 'required|string|min:2|max:50',
            'school' => 'required|string|min:2|max:50',
            'status' => 'required|in:Cleared,Pending,Not Cleared',
            'pnp_officer_name' => 'required|string|min:2|max:50',
            'barangay_officer_name' => 'required|string|min:2|max:50',
            'school_officer_name' => 'required|string|min:2|max:50',
            'rtc_officer_name' => 'required|string|min:2|max:50',
            'file' => 'required|array',
            'file.*' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $fullName = implode(' ', array_filter([
            $validatedData['first_name'] ?? '',
            $validatedData['middle_name'] ?? '',
            $validatedData['last_name'] ?? '',
            $validatedData['suffix'] ?? '',
        ]));

        $address = implode(', ', array_filter([
            $validatedData['street'] ?? '',
            $validatedData['barangay'] ?? '',
            $validatedData['city'] ?? '',
            $validatedData['province'] ?? '',
        ]));

        $applicant = Applicant::create([
            'full_name' => $fullName,
            'age' => $validatedData['age'],
            'date_of_birth' => $validatedData['date_of_birth'],
            'phone' => $validatedData['phone'],
            'birth_place' => $validatedData['birth_place'],
            'school' => $validatedData['school'],
            'status' => $validatedData['status'],
            'pnp_officer_name' => $validatedData['pnp_officer_name'], // Ensure this field is included
            'barangay_officer_name' => $validatedData['barangay_officer_name'], // Ensure this field is included
            'school_officer_name' => $validatedData['school_officer_name'], // Ensure this field is included
            'rtc_officer_name' => $validatedData['rtc_officer_name'], // Ensure this field is included
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
                    ->orWhere('age', 'LIKE', "%{$search}%")
                    ->orWhere('created_at', 'LIKE', "%{$search}%")
                    ->orWhere('date_of_birth', 'LIKE', "%{$search}%");
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

            // Manually merge JSON payload if present
            if ($request->isJson()) {
                $request->merge($request->json()->all());
            }

            // Validate request data
            $validatedData = $request->validate([
                'full_name'     => 'nullable|string|max:255',
                'phone'         => 'nullable|string|max:20',
                'date_of_birth' => 'nullable|date',
                'age'           => 'nullable|integer|min:0',
                'address'       => 'nullable|string|max:255',
                'birth_place'   => 'nullable|string|max:255',
                'school'        => 'nullable|string|max:255',
                'status'        => ['nullable', Rule::in(['Cleared', 'Not Cleared', 'Pending'])],
                'pnp_officer_name'       => 'nullable|string|max:255',
                'barangay_officer_name'  => 'nullable|string|max:255',
                'rtc_officer_name'       => 'nullable|string|max:255',
                'school_officer_name'    => 'nullable|string|max:255',
                'file'          => 'nullable|array',
                'file.*'        => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            ]);

            \Log::info('Validated Data:', $validatedData);

            $applicant = Applicant::findOrFail($id);
            \Log::info('Applicant Before Update:', $applicant->toArray());

            // Update the applicant with only the fields that are present in the request
            $dataToUpdate = array_filter($validatedData, function ($value) {
                return $value !== null;
            });
            
            $applicant->update($dataToUpdate);

            // Handle document uploads if any
            if ($request->hasFile('file')) {
                $documents = Document::where('applicant_id', $id)->first();
                if (!$documents) {
                    // Create new document records if they don't exist
                    $documentPaths = collect($request->file('file'))->mapWithKeys(function ($file, $key) {
                        return ["{$key}_path" => $file->store('certificates', 'public')];
                    })->toArray();
                    
                    DB::table('documents')->insert([
                        'applicant_id' => $id,
                        ...$documentPaths,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    // Update existing document records
                    foreach ($request->file('file') as $key => $file) {
                        $field = "{$key}_path";
                        
                        // Delete old file if it exists
                        if ($documents->$field) {
                            Storage::disk('public')->delete(str_replace('storage/', '', $documents->$field));
                        }
                        
                        // Store new file
                        $documents->$field = $file->store('certificates', 'public');
                    }
                    $documents->save();
                }
            }

            // Get updated document URLs
            $documents = Document::where('applicant_id', $id)->first();
            $certificates = null;
            
            if ($documents) {
                $certificates = collect([
                    'barangay_cert' => $documents->barangay_cert_path,
                    'pnp_clearance' => $documents->pnp_clearance_path,
                    'rtc_clearance' => $documents->rtc_clearance_path,
                    'school_cert' => $documents->school_cert_path,
                    'derogatory_records' => $documents->derogatory_records_path,
                ])->mapWithKeys(function ($value, $key) {
                    $url = $value ? asset("storage/certificates/" . basename($value)) : null;
                    return [$key => $url];
                });
            }

            \Log::info('Applicant After Update:', $applicant->fresh()->toArray());

            return response()->json([
                'message'   => 'Applicant updated successfully',
                'applicant' => $applicant->fresh(),
                'documents' => $certificates,
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation Errors:', $e->errors());
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            \Log::error('Error updating applicant: ' . $e->getMessage());
            return response()->json(['message' => 'An error occurred while updating the applicant.', 'error' => $e->getMessage()], 500);
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
