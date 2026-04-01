<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('intents', function (Blueprint $table) {
            $table->unsignedBigInteger('odoo_picking_id')->nullable()->after('odoo_order_id');
            $table->unsignedBigInteger('odoo_purchase_id')->nullable()->after('odoo_picking_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('intents', function (Blueprint $table) {
            $table->dropColumn(['odoo_picking_id', 'odoo_purchase_id']);
        });
    }
};
