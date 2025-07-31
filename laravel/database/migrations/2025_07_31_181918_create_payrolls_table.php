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
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->date('pay_period_start');
            $table->date('pay_period_end');
            $table->decimal('total_service_hours', 8, 2);
            $table->decimal('total_service_amount', 10, 2);
            $table->decimal('total_leave_hours', 8, 2)->default(0);
            $table->decimal('total_deductions', 10, 2)->default(0);
            $table->decimal('final_payout', 10, 2);
            $table->enum('status', ['generated', 'paid'])->default('generated');
            $table->unsignedBigInteger('generated_by'); # 由誰生成這份薪資單 (Admin/Supervisor)
            $table->timestamp('generated_at');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('generated_by')->references('id')->on('users')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
