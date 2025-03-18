<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Response;
use Inertia\Inertia;

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
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
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
        return Inertia::render('admin/applicant/Show', ['applicant' => $applicant]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        // Validate request (only include fields that might be updated)
        $validatedData = $request->validate([
            'full_name'     => 'sometimes|string|max:255',
            'phone'         => 'sometimes|string|max:20',
            'date_of_birth' => 'sometimes|date',
            'age'           => 'sometimes|integer|min:0',
            'address'       => 'sometimes|string|max:255',
            'birth_place'   => 'sometimes|string|max:255',
            'school'        => 'sometimes|string|max:255',
            'status'        => ['sometimes', Rule::in(['Cleared', 'Not Cleared', 'Pending'])],
            'officer_name'  => 'sometimes|string|max:255',
        ]);

        // Find the applicant
        $applicant = Applicant::find($id);
        if (!$applicant) {
            return response()->json(['message' => 'Applicant not found'], 404);
        }

        // Update the applicant with only the provided fields
        $applicant->update($validatedData);

        return response()->json([
            'message'   => 'Applicant updated successfully',
            'applicant' => $applicant,
        ], 200);
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
