<?php

namespace App\Engine\Odoo\Client;

/**
 * Odoo HTTP gateway. Delegates to OdooClient.
 *
 * This is the canonical entry point for all Odoo API communication.
 * No business logic allowed here — only HTTP transport delegation.
 */
class OdooGateway
{
    public function __construct(
        protected OdooClient $client,
    ) {}

    /**
     * Generic JSON-2 API call.
     *
     * @param  array<string, mixed>  $params
     */
    public function call(string $model, string $method, array $params = []): mixed
    {
        return $this->client->call($model, $method, $params);
    }

    /**
     * @param  array<int, array<int, mixed>>  $domain
     * @param  array<int, string>  $fields
     * @param  array<string, mixed>  $options
     * @return array<int, array<string, mixed>>
     */
    public function searchRead(string $model, array $domain, array $fields, array $options = []): array
    {
        return $this->client->searchRead($model, $domain, $fields, $options);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function create(string $model, array $values, ?string $popAppRef = null): int
    {
        return $this->client->create($model, $values, $popAppRef);
    }

    /**
     * @param  array<int, int>  $ids
     * @param  array<string, mixed>  $values
     */
    public function write(string $model, array $ids, array $values): bool
    {
        return $this->client->write($model, $ids, $values);
    }

    /**
     * @param  array<int, int>  $ids
     * @param  array<int, string>  $fields
     * @return array<int, array<string, mixed>>
     */
    public function read(string $model, array $ids, array $fields): array
    {
        return $this->client->read($model, $ids, $fields);
    }

    /**
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
        return $this->client->cachedSearchRead($model, $domain, $fields, $ttlSeconds, $options);
    }
}
