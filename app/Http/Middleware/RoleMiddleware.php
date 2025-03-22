<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if the user is authenticated
        if (!Auth::check()) {
            return Inertia::render('auth/login'); // Render the 'Home' page if not authenticated
        }

        // Check if the user's role is 'admin'
        if ($request->user()->role !== 'admin') {
            return redirect()->route('dashboard')->with('error', 'You are not authorized to access the page: ' . $request->path());
        }

        return $next($request);
    }
}
