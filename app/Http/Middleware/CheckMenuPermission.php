<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckMenuPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $menuKey
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $menuKey)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        if (!auth()->user()->hasMenuPermission($menuKey)) {
            abort(403, 'Access Denied: Your branch account does not have access to this menu/feature.');
        }

        return $next($request);
    }
}
