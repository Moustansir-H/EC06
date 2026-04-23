<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;
use Illuminate\Support\Facades\Http;

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
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            return $next($request);
        }

        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['error' => 'Token absent'], 401);
        }

        $user = $this->resolveUserFromAuthService($token);

        if (!$user) {
            return response()->json(['error' => 'Token invalide'], 401);
        }

        auth()->setUser($user);

        return $next($request);
    }

    private function resolveUserFromAuthService(string $token): ?User
    {
        try {
            $authBaseUrl = rtrim((string) env('AUTH_SERVICE_URL', 'http://127.0.0.1:8080/api'), '/');

            $response = Http::timeout(3)
                ->withToken($token)
                ->acceptJson()
                ->get($authBaseUrl . '/me');

            if (! $response->ok()) {
                return null;
            }

            $payload = $response->json();
            if (! is_array($payload) || empty($payload['email'])) {
                return null;
            }

            return User::where('email', (string) $payload['email'])->first();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
