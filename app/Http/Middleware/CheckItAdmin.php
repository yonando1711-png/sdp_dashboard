<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckItAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        if (!auth()->user()->isItAdmin()) {
            abort(403, 'Access Denied: Only IT Administrators can access system Utilities & User Management.');
        }

        return $next($request);
    }
}
