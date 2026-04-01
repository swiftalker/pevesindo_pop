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

        Schema::create('analytic_accounts', function (Blueprint $table) {
            $table->id();
            $table->integer('odoo_id')->nullable()->index();
            $table->foreignId('plan_id');
            $table->foreignId('company_id')->nullable();
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
        Schema::dropIfExists('analytic_accounts');
    }
};
