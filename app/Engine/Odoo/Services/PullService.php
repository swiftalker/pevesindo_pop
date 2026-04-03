<?php

namespace App\Engine\Odoo\Services;

use App\Engine\Odoo\Client\OdooGateway;
use App\Engine\Odoo\Exceptions\OdooApiException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Generic pull service — orchestrates pulling data from Odoo into local models.
 *
 * Usage:
 *   $result = app(PullService::class)
 *       ->model('res.partner')
 *       ->fields(['name', 'email', 'phone'])
 *       ->domain([['is_company', '=', true]])
 *       ->into(Partner::class)
 *       ->mapWith(fn ($data) => [...])
 *       ->run();
 *
 * Or simple pull by ID:
 *   $data = app(PullService::class)->fromModel('sale.order', 123)->first();
 */
class PullService
{
    protected string $odooModel;

    /** @var array<int, array> */
    protected array $domain = [];

    /** @var array<int, string> */
    protected array $fields = ['*'];

    /** @var array<string, mixed> */
    protected array $options = [];

    protected ?string $intoModel = null;

    protected ?\Closure $mapper = null;

    protected ?int $limit = null;

    protected ?int $offset = null;

    public function __construct(
        protected OdooGateway $gateway,
    ) {}

    /**
     * Set the Odoo model to pull from.
     */
    public function model(string $odooModel): self
    {
        $this->odooModel = $odooModel;

        return $this;
    }

    /**
     * Set the domain filter.
     *
     * @param  array<int, array>  $domain
     */
    public function domain(array $domain): self
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * Set which fields to read from Odoo.
     *
     * @param  array<int, string>  $fields
     */
    public function fields(array $fields): self
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * Additional options (limit, offset, order).
     *
     * @param  array<string, mixed>  $options
     */
    public function options(array $options): self
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Set a limit on the number of results.
     */
    public function limit(?int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * Set the offset for pagination.
     */
    public function offset(?int $offset): self
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * Set the local Eloquent model class to upsert into.
     *
     * @param  class-string<Model>  $modelClass
     */
    public function into(string $modelClass): self
    {
        $this->intoModel = $modelClass;

        return $this;
    }

    /**
     * Set a custom mapper closure. Receives Odoo data, returns local model attributes.
     */
    public function mapWith(?\Closure $mapper): self
    {
        $this->mapper = $mapper;

        return $this;
    }

    /**
     * Pull a single record from Odoo by its Odoo ID.
     *
     * @return array<string, mixed> Raw Odoo data
     *
     * @throws OdooApiException
     */
    public function pullById(int $odooId, array $fields = []): array
    {
        $this->resolveModelOrFail();

        $readFields = empty($fields) ? $this->fields : $fields;
        $records = $this->gateway->read($this->odooModel, [$odooId], $readFields);

        if (empty($records)) {
            throw new OdooApiException("Record #{$odooId} not found in Odoo ({$this->odooModel})");
        }

        return $records[0];
    }

    /**
     * Pull all records matching the domain.
     *
     * @return array<int, array<string, mixed>>
     */
    public function get(): array
    {
        $this->resolveModelOrFail();

        $options = $this->options;

        if ($this->limit) {
            $options['limit'] = $this->limit;
        }

        if ($this->offset) {
            $options['offset'] = $this->offset;
        }

        $records = $this->gateway->searchRead($this->odooModel, $this->domain, $this->fields, $options);

        Log::info("Odoo Pull [get] {$this->odooModel}", [
            'domain' => $this->domain,
            'fields' => $this->fields,
            'count' => count($records),
        ]);

        return $records;
    }

    /**
     * Pull and upsert each matching record into the local model.
     *
     * @return array<array<string, mixed>> Synced local model results
     */
    public function run(): array
    {
        $this->resolveModelOrFail();

        $records = $this->get();
        $synced = [];

        foreach ($records as $data) {
            $odooId = $data['id'] ?? null;

            if (! $odooId) {
                Log::warning("Odoo Pull [run] Missing 'id' in data, skipping", ['record' => $data]);

                continue;
            }

            $attributes = $this->mapper ? ($this->mapper)($data) : $this->defaultMap($data);
            $attributes['odoo_id'] = $odooId;
            $attributes['synced_at'] = now();

            $model = $this->intoModel::updateOrCreate(
                ['odoo_id' => $odooId],
                $attributes,
            );

            $synced[] = $model;
        }

        Log::info("Odoo Pull [run] Completed {$this->odooModel}", [
            'synced' => count($synced),
        ]);

        return $synced;
    }

    /**
     * Build the pull query result with custom mapping applied.
     *
     * @return Collection<int, mixed>
     */
    public function getMapped(): Collection
    {
        $records = $this->get();

        return collect($records)->map(fn ($data) => $this->mapper
            ? ($this->mapper)($data)
            : $this->defaultMap($data));
    }

    /**
     * Default mapper — maps common Odoo field names to local attributes.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function defaultMap(array $data): array
    {
        // Map Many2one tuples to plain values
        $mapped = [];

        foreach ($data as $key => $value) {
            // Odoo Many2one: [id, display_name]
            if (is_array($value) && count($value) === 2 && is_int($value[0])) {
                $mapped[$key] = $value[1] ?? null;
                $mapped[$key.'_id'] = $value[0];
            } else {
                $mapped[$key] = $value;
            }
        }

        return $mapped;
    }

    /**
     * Set the Odoo model and return $this for chaining.
     */
    public function from(string $odooModel): self
    {
        return $this->model($odooModel);
    }

    protected function resolveModelOrFail(): void
    {
        if (! isset($this->odooModel)) {
            throw new \RuntimeException(
                'Odoo model not set. Call ->model() or ->from() before run().'
            );
        }
    }
}
