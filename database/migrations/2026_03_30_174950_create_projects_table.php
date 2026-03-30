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

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->uuid('center_app_ref')->unique();
            $table->foreignId('sale_order_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->integer('odoo_id')->nullable()->index();
            $table->string('name');
            $table->foreignId('odoo_company_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('odoo_partner_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->date('date_start')->nullable();
            $table->date('date_end')->nullable();
            $table->string('project_state', 20)->default('draft');
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
        Schema::dropIfExists('projects');
    }
};
