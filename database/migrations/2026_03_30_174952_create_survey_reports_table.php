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

        Schema::create('survey_reports', function (Blueprint $table) {
            $table->id();
            $table->uuid('center_app_ref')->unique();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('surveyor_id')->constrained('employees')->cascadeOnDelete()->cascadeOnUpdate();
            $table->text('findings');
            $table->json('measurement_data');
            $table->json('recommended_products')->nullable();
            $table->json('photos')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_reports');
    }
};
