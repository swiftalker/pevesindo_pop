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
        Schema::disableForeignKeyConstraints();

        Schema::create('sale_order_lines', function (Blueprint $table) {
            $table->id();
            $table->uuid('center_app_ref')->unique();
            $table->foreignId('sale_order_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->integer('odoo_id')->nullable()->index();
            $table->foreignId('odoo_product_id')->nullable()->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('name');
            $table->decimal('product_uom_qty', 12, 2)->default(1);
            $table->decimal('price_unit', 15, 2)->default(0);
            $table->decimal('price_subtotal', 15, 2)->default(0);
            $table->decimal('discount', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->string('sync_state', 20)->default('local_draft');
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_order_lines');
    }
};
