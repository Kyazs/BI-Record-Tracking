<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\DB;
use App\Models\Log; // Import the Log model
use Illuminate\Support\Facades\Auth;

class PageController extends Controller
{
    public function index(): Response
    {
        $applicants = Applicant::latest()->paginate(10);
        $totalApplicants = Applicant::count();
        $totalUsers = DB::table('users')->count(); // Count all users
        return Inertia::render('admin/Dashboard', ['applicants' => $applicants, 'totalApplicants' => $totalApplicants, 'totalUsers' => $totalUsers]);
    }

    public function manageUser(): Response
    {
        $users = User::paginate(10);
        return Inertia::render('admin/user/User', ['users' => $users]);
    }

    public function logs(): Response
    {
        $logs = Log::latest()->paginate(10)->through(function ($log) {
            $log->user_id = User::find($log->user_id)?->name; // Replace user_id with the user's name
            if ($applicant = Applicant::find($log->applicant_id)) {
                $log->applicant_id = "{$applicant->id} - {$applicant->full_name}"; // Concatenate applicant_id and full_name
            }
            return $log;
        });

        return Inertia::render('Log', ['logs' => $logs]);
    }
}
