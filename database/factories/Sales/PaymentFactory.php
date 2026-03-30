<?php

namespace Database\Factories\Sales;

use App\Models\Invoice;
use App\Models\OdooJournal;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'center_app_ref' => fake()->uuid(),
            'invoice_id' => Invoice::factory(),
            'odoo_id' => fake()->numberBetween(-10000, 10000),
            'odoo_journal_id' => OdooJournal::factory(),
            'amount' => fake()->randomFloat(2, 0, 9999999999999.99),
            'payment_date' => fake()->date(),
            'memo' => fake()->word(),
            'payment_state' => fake()->regexify('[A-Za-z0-9]{20}'),
            'sync_state' => fake()->regexify('[A-Za-z0-9]{20}'),
        ];
    }
}
