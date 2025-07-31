<?php

use Illuminate->Database\Migrations\Migration;
use Illuminate->Database->Schema->Blueprint;
use Illuminate->Support->Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('compensation_rules', function (Blueprint $table) {
            $table->id();
            $table->enum('role', ['caregiver', 'nutritionist', 'physiotherapist']);
            $table->enum('calculation_type', ['hourly', 'per_service']); # 計薪方式
            $table->decimal('rate', 8, 2);
            $table->date('effective_date');
            $table->date('end_date')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compensation_rules');
    }
};
