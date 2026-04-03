<?php

namespace App\Engine\Odoo\Sessions;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Manages Odoo web session authentication via cookie-based login.
 *
 * The JSON-2 API uses Bearer tokens, but Odoo's web controllers
 * (like /report/pdf/) require session cookies. This manager
 * authenticates via ODOO_LOGIN/ODOO_PASSWORD and caches the
 * session_id cookie for the configured TTL.
 */
class SessionManager
{
    private const CACHE_KEY = 'odoo.web_session';

    private bool $freshLogin = false;

    /**
     * Get the current session cookie, refreshing if expired.
     */
    public function getCookie(): string
    {
        $cookie = Cache::get(self::CACHE_KEY);

        if (! $cookie) {
            $cookie = $this->refresh();
        }

        return $cookie;
    }

    /**
     * Force a new login and cache the session.
     */
    public function refresh(): string
    {
        if (! config('odoo.login') || ! config('odoo.password')) {
            throw new \RuntimeException(
                'ODOO_LOGIN and/or ODOO_PASSWORD not configured. '
              .'Required for web session access (PDF reports).'
            );
        }

        $cookie = $this->login(
            (string) config('odoo.login'),
            (string) config('odoo.password'),
        );

        $ttl = config('odoo.session_ttl_minutes', 50);

        Cache::put(self::CACHE_KEY, $cookie, now()->addMinutes($ttl));

        $this->freshLogin = true;

        return $cookie;
    }

    /**
     * Invalidate the cached session (e.g., after a 401).
     */
    public function invalidate(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Whether the last getCookie() call was a fresh login.
     */
    public function wasFreshLogin(): bool
    {
        return $this->freshLogin;
    }

    /**
     * Authenticate via Odoo's /web/session/authenticate endpoint.
     *
     * Returns the session_id cookie value.
     *
     * @throws \RuntimeException
     */
    protected function login(string $login, string $password): string
    {
        $baseUrl = rtrim(config('odoo.base_url'), '/');
        $db = (string) config('odoo.database');

        $response = Http::withOptions([
            'allow_redirects' => ['max' => 0],
        ])
            ->asForm()
            ->timeout(config('odoo.timeout', 30))
            ->post("{$baseUrl}/web/session/authenticate", [
                'db' => $db,
                'login' => $login,
                'password' => $password,
            ]);

        $cookies = $this->extractSetCookies($response);

        // Try to extract session_id from Set-Cookie header first.
        $sessionId = collect($cookies)
            ->map(fn (string $c) => explode(';', $c)[0])
            ->first(fn (string $c) => str_starts_with(trim($c), 'session_id='));

        // If no Set-Cookie header, check the JSON response body
        // (some Odoo versions return session_id in the authenticate result).
        if (! $sessionId || $sessionId === 'session_id=') {
            $result = $response->json('result');

            if (is_array($result) && isset($result['session_id'])) {
                $sessionId = 'session_id='.$result['session_id'];
            }
        }

        if (! $sessionId || $sessionId === 'session_id=') {
            if ($response->status() >= 400) {
                throw new \RuntimeException(
                    "Odoo web login failed (HTTP {$response->status()}). "
                  .'Check ODOO_LOGIN/ODOO_PASSWORD.'
                );
            }

            if (is_array($result ?? []) && ($result['error'] ?? null)) {
                throw new \RuntimeException(
                    'Odoo web login rejected: '.($result['error'] ?? 'unknown')
                );
            }

            throw new \RuntimeException(
                'Odoo web login succeeded but session_id not found.'
            );
        }

        // Strip whitespace for clean cookie header.
        return trim($sessionId);
    }

    /**
     * @return array<string>
     */
    protected function extractSetCookies(Response $response): array
    {
        $header = $response->header('Set-Cookie');

        if (is_array($header)) {
            return $header;
        }

        if (is_string($header) && $header !== '') {
            return [$header];
        }

        return [];
    }
}
