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

        Schema::create('sales_intents', function (Blueprint $table) {
            $table->id();
            $table->uuid('center_app_ref')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('odoo_company_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('sales_type', 10);
            $table->string('customer_name');
            $table->string('customer_phone', 30)->nullable();
            $table->text('project_address')->nullable();
            $table->foreignId('odoo_pricelist_id')->nullable()->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->text('note')->nullable();
            $table->string('state', 30)->default('draft');
            $table->string('sync_state', 20)->default('local_draft');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_intents');
    }
};
