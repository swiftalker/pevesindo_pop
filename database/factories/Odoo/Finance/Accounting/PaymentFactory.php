<?php

namespace Database\Factories\Odoo\Finance\Accounting;

use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'odoo_id' => fake()->numberBetween(-10000, 10000),
            'invoice_id' => Odoo\Finance\Accounting\Invoice::factory(),
            'journal_id' => Odoo\Finance\Accounting\Journal::factory(),
            'amount' => fake()->randomFloat(2, 0, 9999999999999.99),
            'payment_date' => fake()->date(),
            'payment_state' => fake()->regexify('[A-Za-z0-9]{20}'),
        ];
    }
}
