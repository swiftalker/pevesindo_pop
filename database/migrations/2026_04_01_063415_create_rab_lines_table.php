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

        Schema::create('rab_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rab_id');
            $table->integer('odoo_invoice_line_id')->nullable()->index();
            $table->integer('sequence')->default(10);
            $table->string('display_type', 20)->nullable();
            $table->foreignId('product_id')->nullable();
            $table->text('name');
            $table->decimal('quantity', 15, 2)->default(1);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rab_lines');
    }
};
