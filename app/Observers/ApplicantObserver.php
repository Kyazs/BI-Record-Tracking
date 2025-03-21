<?php

namespace App\Observers;

use App\Models\Applicant;
use App\Models\Log; // Ensure Log is imported

class ApplicantObserver
{
    /**
     * Handle the Applicant "created" event.
     */
    public function created(Applicant $applicant)
    {
        Log::create([
            'user_id' => auth()->id() ?? 0, // Default to 0 if no user is authenticated
            'applicant_id' => $applicant->id,
            'action' => 'created',
            'new_data' => json_encode($applicant),
        ]);
    }

    /**
     * Handle the Applicant "updated" event.
     */
    public function updated(Applicant $applicant)
    {
        Log::create([
            'user_id' => auth()->id() ?? 0, // Default to 0 if no user is authenticated
            'applicant_id' => $applicant->id,
            'action' => 'updated',
            'old_data' => json_encode($applicant->getOriginal()),
            'new_data' => json_encode($applicant->getChanges()),
        ]);
    }

    /**
     * Handle the Applicant "deleted" event.
     */
    public function deleted(Applicant $applicant)
    {
        Log::create([
            'user_id' => auth()->id() ?? 0, // Default to 0 if no user is authenticated
            'applicant_id' => $applicant->id,
            'action' => 'deleted',
            'old_data' => json_encode($applicant),
        ]);
    }

    // Removed empty methods for "restored" and "forceDeleted" events
}
