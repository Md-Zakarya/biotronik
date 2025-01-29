<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Spatie\Permission\Exceptions\UnauthorizedException;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        // Authentication check with logging
        \Log::info('Auth check:', ['is_authenticated' => (bool)$request->user()]);
        
        if (!$request->user()) {
            \Log::error('User not authenticated');
            throw UnauthorizedException::notLoggedIn();
        }
    
        // Log request details
        \Log::info('Request details:', [
            'path' => $request->path(),
            'method' => $request->method(),
            'ip' => $request->ip()
        ]);
    
        // Log user details
        \Log::info('User details:', [
            'id' => $request->user()->id,
            'email' => $request->user()->email,
            'roles' => $request->user()->getRoleNames(),
            'permissions' => $request->user()->getAllPermissions()->pluck('name')
        ]);
    
        // Log required roles
        \Log::info('Required roles:', ['roles' => $roles]);
    
        // Check if user has any of the required roles
        foreach ($roles as $role) {
            \Log::info('Checking role:', [
                'role' => $role,
                'has_role' => $request->user()->hasRole($role)
            ]);
    
            if ($request->user()->hasRole($role)) {
                \Log::info('Role check passed:', ['role' => $role]);
                return $next($request);
            }
        }
    
        \Log::warning('Access denied - required roles not found', [
            'user_roles' => $request->user()->getRoleNames(),
            'required_roles' => $roles
        ]);
    
        throw UnauthorizedException::forRoles($roles);
    }
}