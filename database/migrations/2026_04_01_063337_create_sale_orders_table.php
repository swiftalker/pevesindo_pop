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

        Schema::create('sale_orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('pop_app_ref')->unique();
            $table->unsignedBigInteger('intent_id')->nullable();
            $table->integer('odoo_id')->nullable()->index();
            $table->string('name')->nullable();
            $table->foreignId('company_id');
            $table->foreignId('partner_id');
            $table->foreignId('team_id')->nullable();
            $table->date('date_order')->nullable();
            $table->decimal('amount_total', 15, 2)->default(0);
            $table->string('sale_state', 20)->default('draft');
            $table->string('payment_state', 20)->default('not_paid');
            $table->text('note')->nullable();
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
        Schema::dropIfExists('sale_orders');
    }
};
