<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;

class SsoAuthService
{
    public function resolveUserFromToken(?string $token): ?User
    {
        if (! is_string($token) || trim($token) === '') {
            return null;
        }

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
