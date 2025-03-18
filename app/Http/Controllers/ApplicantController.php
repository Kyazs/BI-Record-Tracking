<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
