<?php

use App\Http\Controllers\Controller;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ApplicantController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use App\Jobs\RunBackupJob;
use Illuminate\Support\Facades\Cache;

Route::get('/', function () {
    return Inertia::render('auth/Login');
})->middleware(['guest'])->name('home');

// Route::get('dashboard', function () {
//     return Inertia::render('Dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {

    Route::get('/dashboard', [PageController::class, 'index'])->name('dashboard');
    Route::get('/stream-applicants', [ApplicantController::class, 'streamApplicants']);
    Route::get('/manage-applicant', [ApplicantController::class, 'index'])->name('manageApplicant');

    Route::get('/applicants', [ApplicantController::class, 'show'])->name('search.applicant');
    Route::get('/manage-applicant/create-applicant', [ApplicantController::class, 'create'])->name('createApplicant');
    Route::post('/manage-applicant/create-applicant', [ApplicantController::class, 'store'])->name('storeApplicant');
    Route::get('/applicant/{id}', [ApplicantController::class, 'edit'])->name('showApplicant');

    Route::get('/logs', [PageController::class, 'logs'])->name('logs');


});

//  ADMIN ROUTES
Route::middleware(['auth', RoleMiddleware::class])->group(function () {

    Route::get('/manage-user', [UserController::class, 'index'])->name('manageUser');
    Route::get('/user/{id}', [UserController::class, 'edit'])->name('showUser');
    Route::put('/user/{id}', [UserController::class, 'update'])->name('user.update');
    Route::delete('/user/{id}', [UserController::class, 'destroy'])->name('deleteUser');
    Route::get('/create-user', [UserController::class, 'create'])->name('createUser');
    Route::post('/admin/user', [UserController::class, 'store'])->name('storeUser');
    Route::post('/applicant/{id}', [ApplicantController::class, 'update'])->name('applicant.update');
    Route::delete('/applicant/{id}', [ApplicantController::class, 'destroy'])->name('deleteApplicant');

    // route for backup
    Route::get('/backup', function (Request $request) {
        // Unique rate limiter key (per user)
        $key = 'backup-run-' . ($request->user()->id ?? $request->ip());
        // Prevent multiple backups within 5 minutes
        if (RateLimiter::tooManyAttempts($key, 1)) {
            return back()->with('error', 'You can only perform a backup every 5 minutes.');
        }
        // Lock for 5 minutes (300 seconds)
        RateLimiter::hit($key, 300);
        // Dispatch the backup job to the queue
        dispatch(new RunBackupJob());
        return back()->with('success', 'Backup is being processed in the background!');
    })->name('backup.run');
    Route::get('/backup/status', function () {
        return response()->json([
            'status' => Cache::get('backup_status', 'not_started'),
        ]);
    })->name('backup.status');
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
