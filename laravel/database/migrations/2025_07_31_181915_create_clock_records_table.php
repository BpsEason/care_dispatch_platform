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
        Schema::create('clock_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('assignment_id')->nullable(); # 可以綁定任務，也可以是單純上下班打卡
            $table->enum('clock_type', ['in', 'out']);
            $table->dateTime('clocked_at');
            $table->enum('method', ['app', 'manual'])->default('app'); # 打卡方式
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('assignment_id')->references('id')->on('assignments')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clock_records');
    }
};
