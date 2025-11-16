<?php

namespace Roshp\LaravelRakshak\Http\Middleware;

use App\Traits\APIResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class PermissioMiddleware
{
    use APIResponse;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  mixed $roles
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, mixed $permissions): Response
    {
        // Get the authenticated user
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (is_string($permissions)) {
            $permissions = explode('|', $permissions);
        }

        // Check if the user is authenticated and has the required permission
        if (!$user || !$user->hasAllPermissions($permissions)) {
            // If the user does not have the required roles, return 403 Forbidden response
            return $this->unauthorizedResponse();
        }

        // If user has the required role, proceed with the request
        return $next($request);
    }
}
