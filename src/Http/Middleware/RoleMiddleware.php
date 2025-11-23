<?php

declare(strict_types=1);

namespace Roshify\LaravelRakshak\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Get the guard from config
        $guard = config('rakshak.guard', 'web');
        
        // Get the authenticated user
        $user = Auth::guard($guard)->user();

        // Check if user is authenticated
        if (!$user) {
            return $this->unauthorized($request);
        }

        // Check if user has the HasRoles trait
        if (!method_exists($user, 'hasRole')) {
            return $this->unauthorized($request, 'User model must use HasRoles trait.');
        }

        // Parse roles (handle pipe-separated roles from route definition)
        if (count($roles) === 1 && str_contains($roles[0], '|')) {
            $roles = explode('|', $roles[0]);
        }

        // Check if user has any of the required roles
        if (!$user->hasRole($roles)) {
            return $this->unauthorized($request, 'You do not have the required role.');
        }

        // If user has the required role, proceed with the request
        return $next($request);
    }

    /**
     * Return an unauthorized response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $message
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function unauthorized(Request $request, string $message = null): Response
    {
        $message = $message ?? config('rakshak.exceptions.unauthorized_message', 'Unauthorized access.');
        $statusCode = config('rakshak.exceptions.unauthorized_status_code', 403);

        // Return JSON response for API requests
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], $statusCode);
        }

        // Return redirect for web requests
        abort($statusCode, $message);
    }
}
