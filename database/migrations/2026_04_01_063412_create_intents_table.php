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

        Schema::create('intents', function (Blueprint $table) {
            $table->id();
            $table->uuid('pop_app_ref')->unique();
            $table->foreignId('user_id');
            $table->foreignId('company_id');
            $table->string('sales_type', 20)->default('closed');
            $table->string('intent_state', 30)->default('prospect');
            $table->string('pipeline_stage', 50)->nullable();
            $table->string('customer_name');
            $table->string('customer_phone', 30)->nullable();
            $table->text('project_address')->nullable();
            $table->foreignId('pricelist_id')->nullable();
            $table->decimal('expected_revenue', 15, 2)->default(0);
            $table->text('note')->nullable();
            $table->integer('odoo_lead_id')->nullable()->index();
            $table->integer('odoo_order_id')->nullable()->index();
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
        Schema::dropIfExists('intents');
    }
};
