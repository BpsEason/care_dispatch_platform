<?php

use Illuminate->Database->Migrations\Migration;
use Illuminate->Database->Schema->Blueprint;
use Illuminate->Support->Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('care_plans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('service_item_id');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('frequency');
            $table->time('preferred_time_start')->nullable();
            $table->time('preferred_time_end')->nullable();
            $table->smallInteger('duration_minutes');
            $table->enum('status', ['active', 'inactive', 'paused'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('service_item_id')->references('id')->on('service_items')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('care_plans');
    }
};
