<?php

namespace Database\Factories\Sales\Order;

use App\Models\Odoo\Core\Odoo\Core\Company;
use App\Models\Odoo\Core\Odoo\Core\Partner;
use App\Models\Odoo\Sales\Order\Odoo\Sales\Crm\Team;
use App\Models\Sales\Intent\Sales\Intent\Intent;
use App\Models\Sales\Order\Odoo\Sales\Crm\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

class SaleOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'center_app_ref' => fake()->uuid(),
            'intent_id' => Sales\Intent\Intent::factory(),
            'odoo_id' => fake()->numberBetween(-10000, 10000),
            'name' => fake()->name(),
            'company_id' => Odoo\Core\Company::factory(),
            'partner_id' => Odoo\Core\Partner::factory(),
            'team_id' => Odoo\Sales\Crm\Team::factory(),
            'date_order' => fake()->date(),
            'amount_total' => fake()->randomFloat(2, 0, 9999999999999.99),
            'sale_state' => fake()->regexify('[A-Za-z0-9]{20}'),
            'payment_state' => fake()->regexify('[A-Za-z0-9]{20}'),
            'note' => fake()->text(),
            'sync_state' => fake()->regexify('[A-Za-z0-9]{20}'),
        ];
    }
}
