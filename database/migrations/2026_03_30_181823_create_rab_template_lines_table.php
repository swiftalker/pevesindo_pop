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

        Schema::create('rab_template_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rab_template_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('odoo_product_id')->nullable()->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('description');
            $table->decimal('default_quantity', 15, 2)->default(1);
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rab_template_lines');
    }
};
