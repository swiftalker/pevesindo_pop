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

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->uuid('center_app_ref')->unique();
            $table->integer('odoo_id')->nullable()->index();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('partner_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('project_id')->nullable()->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('name');
            $table->foreignId('assigned_to')->nullable()->constrained('employees', 'to')->cascadeOnDelete()->cascadeOnUpdate();
            $table->text('worksheet_result')->nullable();
            $table->timestamp('reschedule_requested_at')->nullable();
            $table->text('reschedule_reason')->nullable();
            $table->timestamp('reassignment_requested_at')->nullable();
            $table->text('reassignment_reason')->nullable();
            $table->string('fsm_state', 20)->default('draft');
            $table->string('sync_state', 20)->default('local_draft');
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
