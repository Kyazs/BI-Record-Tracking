<?php

use App\Http\Controllers\Controller;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ApplicantController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('auth/Login');
})->middleware(['guest'])->name('home');

// Route::get('dashboard', function () {
//     return Inertia::render('Dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

//  ADMIN ROUTES
Route::middleware('auth')->group(function () {

    // Route::get('dashboard', function () {
    //     return Inertia::render('admin/Dashboard');
    // })->middleware(['auth', 'verified'])->name('dashboard');

    Route::get('/dashboard', [PageController::class, 'index'])->name('dashboard');

    Route::get('/manage-user', [PageController::class, 'manageUser'])->name('manageUser');



    Route::get('/stream-applicants', [ApplicantController::class, 'streamApplicants']);
    Route::get('/manage-applicant', [ApplicantController::class, 'index'])->name('manageApplicant');
    Route::get('/applicant/{id}', [ApplicantController::class, 'edit'])->name('showApplicant');
    Route::put('/applicant/{id}', [ApplicantController::class, 'update'])->name('updateApplicant');
    Route::delete('/applicant/{id}', [ApplicantController::class, 'destroy'])->name('deleteApplicant');
    Route::get('/applicants', [ApplicantController::class, 'show'])->name('search.applicant');
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
