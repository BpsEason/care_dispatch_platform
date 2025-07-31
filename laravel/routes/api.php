<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Public routes
Route::post('/login', [LoginController::class, 'login']);

// Authenticated routes
Route::middleware('sanctum:auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // --- Admin Routes ---
    Route::middleware('can:isSuperAdmin')->prefix('admin')->group(function () {
        Route::apiResource('users', \App\Http\Controllers\Admin\UserController::class);
        Route::apiResource('compensation-rules', \App\Http\Controllers\Admin\CompensationRuleController::class);
        Route::get('reports/monthly-payroll', [\App\Http\Controllers\Admin\ReportController::class, 'monthlyPayroll']);
        // ... 其他 Admin 路由
    });

    // --- Supervisor Routes ---
    Route::middleware('can:isSupervisor')->prefix('supervisor')->group(function () {
        Route::apiResource('patients', \App\Http\Controllers\Supervisor\PatientController::class);
        Route::apiResource('care-plans', \App\Http\Controllers\Supervisor\CarePlanController::class);
        Route::apiResource('assignments', \App\Http\Controllers\Supervisor\AssignmentController::class);
        Route::apiResource('leave-requests', \App\Http\Controllers\Supervisor\LeaveRequestController::class)->only(['index', 'show', 'update']); // 審批功能
        Route::get('payrolls', [\App\Http\Controllers\Supervisor\PayrollController::class, 'index']); // 查看下屬薪資
        // ... 其他 Supervisor 路由
    });

    // --- Caregiver Routes ---
    Route::middleware('can:isCaregiver')->prefix('caregiver')->group(function () {
        Route::get('schedule', [\App\Http\Controllers\Caregiver\ScheduleController::class, 'index']);
        Route::post('clock-in', [\App\Http\Controllers\Caregiver\ClockController::class, 'clockIn']);
        Route::post('clock-out', [\App\Http\Controllers\Caregiver\ClockController::class, 'clockOut']);
        Route::apiResource('leave-requests', \App\Http\Controllers\Caregiver\LeaveRequestController::class)->only(['index', 'store', 'show']); // 提交和查看請假
        Route::get('payrolls', [\App\Http\Controllers\Caregiver\PayrollController::class, 'index']); // 查看自己薪資
        // ... 其他 Caregiver 路由
    });
});
