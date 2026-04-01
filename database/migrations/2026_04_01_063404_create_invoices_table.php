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

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->uuid('pop_app_ref')->unique();
            $table->foreignId('order_id')->nullable();
            $table->foreignId('project_id')->nullable();
            $table->integer('odoo_id')->nullable()->index();
            $table->string('name')->nullable();
            $table->string('invoice_type', 20)->default('out_invoice');
            $table->foreignId('team_id')->nullable();
            $table->foreignId('analytic_account_id')->nullable();
            $table->decimal('amount_total', 15, 2)->default(0);
            $table->string('invoice_state', 20)->default('draft');
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
        Schema::dropIfExists('invoices');
    }
};
