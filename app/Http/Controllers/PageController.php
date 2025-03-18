<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\User;
use Illuminate\Http\Request;

use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\DB;

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

}
