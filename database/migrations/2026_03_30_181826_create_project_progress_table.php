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

        Schema::create('project_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_task_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('reported_by')->constrained('employees', 'by')->cascadeOnDelete()->cascadeOnUpdate();
            $table->unsignedInteger('progress_percentage')->default(0);
            $table->text('notes')->nullable();
            $table->json('photos')->nullable();
            $table->string('milestone_status', 30)->nullable();
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_progress');
    }
};
