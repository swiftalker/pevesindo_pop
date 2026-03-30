<?php

namespace Database\Factories;

use App\Models\Odoo\OdooCompany;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppNotificationFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'odoo_company_id' => OdooCompany::factory(),
            'notification_type' => fake()->regexify('[A-Za-z0-9]{50}'),
            'title' => fake()->sentence(4),
            'body' => fake()->text(),
            'data' => '{}',
            'channel' => fake()->regexify('[A-Za-z0-9]{30}'),
            'read_at' => fake()->dateTime(),
        ];
    }
}
