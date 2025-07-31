<?php

use Illuminate->Database->Migrations\Migration;
use Illuminate->Database->Schema->Blueprint;
use Illuminate->Support->Facades->Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('care_plan_id');
            $table->unsignedBigInteger('assigned_to_user_id');
            $table->dateTime('scheduled_start_at');
            $table->dateTime('scheduled_end_at');
            $table->dateTime('actual_start_at')->nullable();
            $table->dateTime('actual_end_at')->nullable();
            $table->enum('status', ['scheduled', 'completed', 'cancelled', 'missed'])->default('scheduled');
            $table->boolean('is_confirmed')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('care_plan_id')->references('id')->on('care_plans')->onDelete('cascade');
            $table->foreign('assigned_to_user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
