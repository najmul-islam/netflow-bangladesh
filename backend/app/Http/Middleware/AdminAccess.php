<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        
        // Debug: Check if user is authenticated
        dd([
            'user_authenticated' => Auth::check(),
            'user_id' => $user ? $user->id : null,
            'user_email' => $user ? $user->email : null,
            'user_roles' => $user ? $user->roles->pluck('name')->toArray() : [],
            'has_admin_role' => $user ? $user->hasRole('admin') : false,
            'request_url' => $request->url(),
            'request_route' => $request->route() ? $request->route()->getName() : null,
        ]);
        
        // If user is not authenticated, this should be handled by Authenticate middleware first
        if (!$user) {
           return redirect()->route('filament.admin.auth.login')
            ->withErrors(['email' => 'Access denied. Admin privileges required.']);
        }

        // If user has admin role, allow access
        if ($user->hasRole('admin')) {
            return $next($request);
        }

        // If user doesn't have admin role, deny access
        Auth::logout();
        return redirect()->route('filament.admin.auth.login')
            ->withErrors(['email' => 'Access denied. Admin privileges required.']);
    }
}