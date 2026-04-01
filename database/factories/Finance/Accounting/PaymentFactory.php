<?php

namespace Database\Factories\Finance\Accounting;

use App\Models\Finance\Accounting\Finance\Accounting\Invoice;
use App\Models\Odoo\Finance\Accounting\Odoo\Finance\Accounting\Invoice;
use App\Models\Odoo\Finance\Accounting\Odoo\Finance\Accounting\Journal;
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
            'invoice_id' => Finance\Accounting\Invoice::factory(),
            'odoo_id' => fake()->numberBetween(-10000, 10000),
            'journal_id' => Odoo\Finance\Accounting\Journal::factory(),
            'amount' => fake()->randomFloat(2, 0, 9999999999999.99),
            'payment_date' => fake()->date(),
            'payment_state' => fake()->regexify('[A-Za-z0-9]{20}'),
            'sync_state' => fake()->regexify('[A-Za-z0-9]{20}'),
        ];
    }
}
