<?php

namespace App\Engine\Odoo;

use App\Engine\Odoo\Exceptions\OdooApiException;
use App\Engine\Odoo\Models\OdooSyncTask;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * HTTP client for the Odoo External JSON-2 API.
 *
 * Pure HTTP transport layer — NO business logic allowed here.
 * Uses Laravel's Http facade — NO third-party XML-RPC/JSON-RPC packages.
 *
 * Authentication: Bearer token (API Key) with X-Odoo-Database header.
 */
class OdooClient
{
    /**
     * Generic JSON-2 API call to Odoo.
     *
     * @param  array<string, mixed>  $params
     *
     * @throws OdooApiException
     */
    public function call(string $model, string $method, array $params = []): mixed
    {
        $url = $this->buildUrl($model, $method);

        $params = $this->injectContext($params);

        $startTime = microtime(true);

        $response = $this->makeClient()
            ->post($url, $params);

        $elapsedMs = round((microtime(true) - $startTime) * 1000);

        $this->logApiCall($model, $method, $response, $elapsedMs);

        if ($response->failed()) {
            if ($response->status() === 429) {
                throw new OdooApiException(
                    "Rate limited by Odoo ({$model}/{$method})",
                    429
                );
            }

            throw new OdooApiException(
                "Odoo API error {$model}/{$method}: HTTP {$response->status()} — {$response->body()}",
                $response->status()
            );
        }

        return $response->json();
    }

    /**
     * Search records by domain filter.
     *
     * @param  array<int, array<int, mixed>>  $domain
     * @param  array<string, mixed>  $options
     * @return array<int, int>
     */
    public function search(string $model, array $domain, array $options = []): array
    {
        $params = ['domain' => $domain];

        foreach (['limit', 'offset', 'order'] as $key) {
            if (isset($options[$key])) {
                $params[$key] = $options[$key];
            }
        }

        return $this->call($model, 'search', $params);
    }

    /**
     * Combined search + read in a single Odoo call.
     *
     * @param  array<int, array<int, mixed>>  $domain
     * @param  array<int, string>  $fields
     * @param  array<string, mixed>  $options
     * @return array<int, array<string, mixed>>
     */
    public function searchRead(string $model, array $domain, array $fields, array $options = []): array
    {
        $params = ['domain' => $domain, 'fields' => $fields];

        foreach (['limit', 'offset', 'order'] as $key) {
            if (isset($options[$key])) {
                $params[$key] = $options[$key];
            }
        }

        return $this->call($model, 'search_read', $params);
    }

    /**
     * Read specific records by IDs.
     *
     * @param  array<int, int>  $ids
     * @param  array<int, string>  $fields
     * @return array<int, array<string, mixed>>
     */
    public function read(string $model, array $ids, array $fields): array
    {
        return $this->call($model, 'read', [
            'ids' => $ids,
            'fields' => $fields,
        ]);
    }

    /**
     * Create a new record in Odoo.
     *
     * Before creating, checks if pop_app_ref already exists to prevent duplicates on retry.
     *
     * @param  array<string, mixed>  $values
     */
    public function create(string $model, array $values, ?string $popAppRef = null): int
    {
        if ($popAppRef) {
            $existing = $this->findByPopAppRef($model, $popAppRef);

            if ($existing) {
                Log::info("Odoo: Idempotent create — {$model} already exists with pop_app_ref={$popAppRef}, odoo_id={$existing}");

                return $existing;
            }
        }

        $result = $this->call($model, 'create', ['vals_list' => [$values]]);

        if (is_array($result) && isset($result[0]) && is_int($result[0])) {
            return $result[0];
        }

        if (is_int($result)) {
            return $result;
        }

        throw new OdooApiException(
            "Unexpected create response for {$model}: ".json_encode($result)
        );
    }

    /**
     * Update existing records in Odoo.
     *
     * @param  array<int, int>  $ids
     * @param  array<string, mixed>  $values
     */
    public function write(string $model, array $ids, array $values): bool
    {
        $result = $this->call($model, 'write', [
            'ids' => $ids,
            'vals' => $values,
        ]);

        return $result === true || $result === [true];
    }

    /**
     * Count records matching a domain filter.
     *
     * @param  array<int, array<int, mixed>>  $domain
     */
    public function searchCount(string $model, array $domain): int
    {
        $result = $this->call($model, 'search_count', ['domain' => $domain]);

        return is_int($result) ? $result : 0;
    }

    /**
     * Fetch and cache reference data (e.g., journals, pricelists).
     *
     * @param  array<int, array<int, mixed>>  $domain
     * @param  array<int, string>  $fields
     * @param  array<string, mixed>  $options
     * @return array<int, array<string, mixed>>
     */
    public function cachedSearchRead(
        string $model,
        array $domain,
        array $fields,
        int $ttlSeconds = 3600,
        array $options = [],
    ): array {
        $cacheKey = 'odoo:ref:'.md5($model.json_encode($domain).json_encode($fields));

        return Cache::remember($cacheKey, $ttlSeconds, function () use ($model, $domain, $fields, $options) {
            return $this->searchRead($model, $domain, $fields, $options);
        });
    }

    /**
     * Find an existing Odoo record by pop_app_ref custom field.
     */
    protected function findByPopAppRef(string $model, string $popAppRef): ?int
    {
        $task = OdooSyncTask::where('model', $model)
            ->where('pop_app_ref', $popAppRef)
            ->whereNotNull('odoo_id')
            ->first();

        return $task?->odoo_id;
    }

    /**
     * Build the JSON-2 API URL.
     */
    protected function buildUrl(string $model, string $method): string
    {
        $baseUrl = rtrim(config('odoo.base_url'), '/');

        return "{$baseUrl}/json/2/{$model}/{$method}";
    }

    /**
     * Create a configured Http client.
     */
    protected function makeClient(): PendingRequest
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer '.config('odoo.api_key'),
            'X-Odoo-Database' => config('odoo.database'),
            'Accept-Language' => config('odoo.default_lang').','.config('odoo.default_lang').';q=0.9,en-US;q=0.8,en;q=0.7',
            'User-Agent' => config('odoo.user_agent'),
        ])
            ->timeout(config('odoo.timeout'))
            ->connectTimeout(config('odoo.connect_timeout'))
            ->retry(
                config('odoo.retry_times'),
                config('odoo.retry_sleep_ms'),
                function (\Exception $exception, PendingRequest $request): bool {
                    if ($exception instanceof RequestException) {
                        $status = $exception->response->status();

                        return $status !== 429 && in_array($status, [408, 500, 502, 503, 504]);
                    }

                    return true;
                },
                throw: false,
            );
    }

    /**
     * Inject the default language context into params.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    protected function injectContext(array $params): array
    {
        if (! isset($params['context'])) {
            $params['context'] = ['lang' => config('odoo.default_lang')];
        } elseif (is_array($params['context'])) {
            $params['context']['lang'] ??= config('odoo.default_lang');
        }

        return $params;
    }

    /**
     * Log the API call for debugging and monitoring.
     */
    protected function logApiCall(string $model, string $method, Response $response, float $elapsedMs): void
    {
        $level = $response->successful() ? 'debug' : 'error';

        Log::$level("Odoo API {$model}/{$method}", [
            'status' => $response->status(),
            'elapsed_ms' => $elapsedMs,
            'url' => $this->buildUrl($model, $method),
        ]);
    }
}
