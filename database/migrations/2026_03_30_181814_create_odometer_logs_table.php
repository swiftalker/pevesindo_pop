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

        Schema::create('odometer_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('center_app_ref')->unique();
            $table->integer('odoo_id')->nullable()->index();
            $table->foreignId('odoo_vehicle_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('driver_id')->constrained('employees')->cascadeOnDelete()->cascadeOnUpdate();
            $table->date('date');
            $table->decimal('value', 15, 2);
            $table->string('unit', 5)->default('km');
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
        Schema::dropIfExists('odometer_logs');
    }
};
