<?php

namespace Database\Seeders;

use App\Models\Applicant;
use App\Models\User;
use App\Models\Log; // Add Log model import
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create the user first
        $user = User::factory()->create([
            'name' => 'super Admin',
            'email' => 'admin@gmail.com',
            'role' => 'admin',
            'password' => bcrypt('asfcasper'),
        ]);

        // // Create applicants and logs simultaneously
        // for ($i = 1; $i < 5; $i++) {
        //     $applicant = Applicant::factory()->create();

        //     Log::create([
        //         'user_id' => $user->id, // Use valid user_id
        //         'applicant_id' => $applicant->id, // Use valid applicant_id
        //         'action' => 'created',
        //         'new_data' => $applicant->toJson(),
        //     ]);
        // }
    }
}
