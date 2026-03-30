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

        Schema::create('odoo_analytic_accounts', function (Blueprint $table) {
            $table->id();
            $table->integer('odoo_id')->nullable()->index();
            $table->foreignId('odoo_analytic_plan_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('odoo_company_id')->nullable()->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('name');
            $table->string('code', 20)->nullable();
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('odoo_analytic_accounts');
    }
};
