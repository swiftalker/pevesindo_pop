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
        Schema::create('odoo_sync_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('type', 10)->comment('push or pull');
            $table->string('model')->comment('Odoo model name, e.g. sale.order');
            $table->string('pop_app_ref')->nullable()->comment('Unique reference from Pop-App');
            $table->unsignedBigInteger('odoo_id')->nullable()->comment('Resulting Odoo record ID');
            $table->json('payload')->nullable()->comment('Request payload sent to Odoo');
            $table->json('response_data')->nullable()->comment('Response data from Odoo');
            $table->string('status', 20)->default('pending')->index();
            $table->text('error_log')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->unsignedSmallInteger('max_attempts')->default(5);
            $table->timestamp('last_attempted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('model');
            $table->index('pop_app_ref');
            $table->index(['status', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('odoo_sync_tasks');
    }
};
