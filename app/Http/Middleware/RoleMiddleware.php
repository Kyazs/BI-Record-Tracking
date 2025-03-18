<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $role
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, string $role)
    {
        // if (!Auth::check() || !Auth::user()) {
        //     if ($request->route()->getName() !== 'login') {
        //         return redirect()->route('login');
        //     }
        // }

        // if (Auth::check() && Auth::user() && Auth::user()->role !== $role) {
        //     abort(403, 'Forbidden');
        // }

        return $next($request);
    }
}
