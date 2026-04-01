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

        Schema::create('rabs', function (Blueprint $table) {
            $table->id();
            $table->uuid('pop_app_ref')->unique();
            $table->foreignId('project_id');
            $table->foreignId('intent_id');
            $table->integer('odoo_invoice_id')->nullable()->index();
            $table->foreignId('team_id')->nullable();
            $table->decimal('total', 15, 2)->default(0);
            $table->string('rab_state', 20)->default('draft');
            $table->text('note')->nullable();
            $table->string('sync_state', 20)->default('local_draft');
            $table->json('x_studio_many2many_field_4jv_1jeesssc3')->nullable();
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rabs');
    }
};
