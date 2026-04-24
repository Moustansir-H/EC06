<?php

namespace App\Http\Middleware;

use App\Services\SsoAuthService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class JwtTokenMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Validates JWT tokens issued by skillhub-auth service.
     * Tokens must be provided in the Authorization header as "Bearer <token>"
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function __construct(
        private SsoAuthService $ssoAuthService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            return $next($request);
        }

        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['error' => 'Token absent'], 401);
        }

        $user = $this->ssoAuthService->resolveUserFromToken($token);

        if (!$user) {
            return response()->json(['error' => 'Token invalide'], 401);
        }

        auth()->setUser($user);

        return $next($request);
    }
}
