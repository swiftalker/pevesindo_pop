<?php

namespace App\Engine\Odoo\Pipeline;

use Illuminate\Support\Collection;

/**
 * Payload accumulator untuk 4 stage Pipeline.
 *
 * - defaults: data mentah dari model lokal
 * - odoo: hasil mapping ke format Odoo API
 * - bind: data terkait (analytic, bank, contact, dll)
 * - pop: trigger untuk domain lain
 */
class Stages
{
    protected array $defaults = [];

    protected array $odoo = [];

    protected array $bind = [];

    protected array $pop = [];

    public function defaults(array $data): self
    {
        $this->defaults = array_merge($this->defaults, $data);

        return $this;
    }

    public function odoo(array $data): self
    {
        $this->odoo = array_merge($this->odoo, $data);

        return $this;
    }

    public function bind(string $key, mixed $value): self
    {
        $this->bind[$key] = $value;

        return $this;
    }

    public function pop(string $key, mixed $value): self
    {
        $this->pop[$key] = $value;

        return $this;
    }

    public function getDefaults(): array
    {
        return $this->defaults;
    }

    public function getOdoo(): array
    {
        return $this->odoo;
    }

    public function getBind(): array
    {
        return $this->bind;
    }

    public function getPop(): array
    {
        return $this->pop;
    }

    /**
     * Gabung semua stage jadi satu payload final.
     */
    public function all(): array
    {
        return array_merge($this->defaults, $this->odoo, $this->bind, $this->pop);
    }

    public function toCollection(): Collection
    {
        return collect($this->all());
    }

    public function toArray(): array
    {
        return [
            'defaults' => $this->defaults,
            'odoo' => $this->odoo,
            'bind' => $this->bind,
            'pop' => $this->pop,
        ];
    }
}
