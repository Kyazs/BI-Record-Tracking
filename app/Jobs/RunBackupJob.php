<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class RunBackupJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
                // Mark backup as "in progress"
                Cache::put('backup_status', 'in_progress', now()->addMinutes(30));

                // Run the backup command
                Artisan::call('backup:run');
        
                // Mark backup as "completed"
                Cache::put('backup_status', 'completed', now()->addMinutes(30));
    }
}
