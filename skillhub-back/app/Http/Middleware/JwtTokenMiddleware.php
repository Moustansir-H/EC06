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
            $authMeUrl = $this->resolveAuthMeUrl();
            if ($authMeUrl === null) {
                return null;
            }

            $response = Http::timeout(3)
                ->withToken($token)
                ->acceptJson()
                ->get($authMeUrl);

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

    private function resolveAuthMeUrl(): ?string
    {
        $baseUrl = (string) config('services.sso.base_url', '');
        if ($baseUrl === '') {
            return null;
        }

        $parts = parse_url($baseUrl);
        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        $scheme = strtolower((string) $parts['scheme']);
        if (! in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        $allowedHosts = config('services.sso.allowed_hosts', []);
        if (! is_array($allowedHosts)) {
            return null;
        }

        $normalizedHosts = array_map(static fn($host) => strtolower((string) $host), $allowedHosts);
        $host = strtolower((string) $parts['host']);

        if (! in_array($host, $normalizedHosts, true)) {
            return null;
        }

        return rtrim($baseUrl, '/') . '/me';
    }
}
