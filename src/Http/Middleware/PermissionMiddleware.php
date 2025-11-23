<?php

declare(strict_types=1);

namespace Roshify\LaravelRakshak\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$permissions
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        // Resolve guard
        $guard = config('rakshak.guard', 'web');

        // Authenticated user
        $user = Auth::guard($guard)->user();

        if (! $user) {
            return $this->unauthorized($request);
        }

        // Ensure user model implements HasPermissions
        if (! method_exists($user, 'hasPermission')) {
            return $this->unauthorized($request, 'User model must use HasPermissions trait.');
        }

        // Support pipe syntax: 'module:action|module:action2'
        if (count($permissions) === 1 && str_contains($permissions[0], '|')) {
            $permissions = explode('|', $permissions[0]);
        }

        // Single unified check — your hasPermission supports multiple rules
        if (! $user->hasPermission($permissions)) {
            return $this->unauthorized($request, 'You do not have the required permission.');
        }

        return $next($request);
    }

    /**
     * Format unauthorized response based on config and content-type.
     */
    protected function unauthorized(Request $request, ?string $message = null): Response
    {
        $message = $message ?? config('rakshak.exceptions.unauthorized_message', 'Unauthorized access.');
        $status  = (int) config('rakshak.exceptions.unauthorized_status_code', 403);
        $json    = (bool) config('rakshak.exceptions.json_response', true);

        if ($request->expectsJson() || $json) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'code'    => $status,
            ], $status);
        }

        abort($status, $message);
    }
}
