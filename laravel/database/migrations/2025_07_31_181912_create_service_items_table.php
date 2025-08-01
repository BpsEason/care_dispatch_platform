<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate->Database->Schema->Blueprint;
use Illuminate->Support->Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('service_items', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('unit', 50)->nullable();
            $table->decimal('price_per_unit', 8, 2);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_items');
    }
};
