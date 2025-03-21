<?php

use App\Http\Controllers\Controller;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ApplicantController;
use App\Http\Controllers\UserController;
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

    Route::get('/manage-user', [UserController::class, 'index'])->name('manageUser');
    Route::get('/create-user', [UserController::class, 'create'])->name('createUser');
    Route::post('/admin/user', [UserController::class, 'store'])->name('storeUser');
    Route::get('/user/{id}', [UserController::class, 'edit'])->name('showUser');
    Route::put('/user/{id}', [UserController::class, 'update'])->name('user.update');
    Route::delete('/user/{id}', [UserController::class, 'destroy'])->name('deleteUser');

    Route::get('/stream-applicants', [ApplicantController::class, 'streamApplicants']);
    Route::get('/manage-applicant', [ApplicantController::class, 'index'])->name('manageApplicant');
    Route::get('/applicant/{id}', [ApplicantController::class, 'edit'])->name('showApplicant');
    Route::post('/applicant/{id}', [ApplicantController::class, 'update'])->name('applicant.update');
    Route::delete('/applicant/{id}', [ApplicantController::class, 'destroy'])->name('deleteApplicant');
    Route::get('/applicants', [ApplicantController::class, 'show'])->name('search.applicant');
    Route::get('/manage-applicant/create-applicant', [ApplicantController::class, 'create'])->name('createApplicant');
    Route::post('/manage-applicant/create-applicant', [ApplicantController::class, 'store'])->name('storeApplicant');

    Route::get('/logs', [PageController::class, 'logs'])->name('logs');

});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
