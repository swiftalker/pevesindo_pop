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

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('center_app_ref')->unique();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->integer('odoo_id')->nullable()->index();
            $table->foreignId('odoo_journal_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->decimal('amount', 15, 2)->default(0);
            $table->date('payment_date');
            $table->string('memo')->nullable();
            $table->string('payment_state', 20)->default('draft');
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
        Schema::dropIfExists('payments');
    }
};
