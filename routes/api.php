<?php

use App\Http\Controllers\Api\V1\AdvanceRequestController;
use App\Http\Controllers\Api\V1\AnalyticsController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BranchController;
use App\Http\Controllers\Api\V1\DailyEntryController;
use App\Http\Controllers\Api\V1\DayClosureController;
use App\Http\Controllers\Api\V1\DocumentController;
use App\Http\Controllers\Api\V1\LedgerEntryController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\WebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::post('auth/refresh', [AuthController::class, 'refresh']);

    Route::middleware('auth.api')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);

        Route::get('users', [UserController::class, 'index']);
        Route::post('users', [UserController::class, 'store']);
        Route::get('users/{user}', [UserController::class, 'show']);
        Route::put('users/{user}', [UserController::class, 'update']);
        Route::delete('users/{user}', [UserController::class, 'destroy']);
        Route::post('users/{user}/change-password', [UserController::class, 'changePassword']);

        Route::get('branches', [BranchController::class, 'index']);
        Route::post('branches', [BranchController::class, 'store']);
        Route::get('branches/{branch}', [BranchController::class, 'show']);

        Route::get('daily-entries', [DailyEntryController::class, 'index']);
        Route::post('daily-entries', [DailyEntryController::class, 'store']);
        Route::get('daily-entries/{dailyEntry}', [DailyEntryController::class, 'show']);
        Route::put('daily-entries/{dailyEntry}', [DailyEntryController::class, 'update']);
        Route::delete('daily-entries/{dailyEntry}', [DailyEntryController::class, 'destroy']);
        Route::get('daily-entries/stats/employee/{employee}', [DailyEntryController::class, 'employeeStats']);

        Route::get('day-closures', [DayClosureController::class, 'index']);
        Route::post('day-closures', [DayClosureController::class, 'store']);
        Route::get('day-closures/{dayClosure}', [DayClosureController::class, 'show']);
        Route::get('day-closures/{dayClosure}/pdf', [DayClosureController::class, 'pdf']);

        Route::get('ledger-entries', [LedgerEntryController::class, 'index']);
        Route::post('ledger-entries', [LedgerEntryController::class, 'store']);
        Route::get('ledger-entries/balance/{party_type}/{party_id}', [LedgerEntryController::class, 'balance']);

        Route::get('advance-requests', [AdvanceRequestController::class, 'index']);
        Route::post('advance-requests', [AdvanceRequestController::class, 'store']);
        Route::post('advance-requests/{advanceRequest}/approve', [AdvanceRequestController::class, 'approve']);
        Route::post('advance-requests/{advanceRequest}/reject', [AdvanceRequestController::class, 'reject']);

        Route::get('documents', [DocumentController::class, 'index']);
        Route::post('documents', [DocumentController::class, 'store']);
        Route::put('documents/{document}', [DocumentController::class, 'update']);
        Route::post('documents/{document}/files', [DocumentController::class, 'addFile']);
        Route::get('documents/expiring-soon', [DocumentController::class, 'expiringSoon']);

        Route::get('notifications', [NotificationController::class, 'index']);
        Route::post('notifications/{notification}/read', [NotificationController::class, 'markRead']);
        Route::post('notifications/read-all', [NotificationController::class, 'markAllRead']);

        Route::get('reports/sales', [ReportController::class, 'sales']);
        Route::get('reports/employees', [ReportController::class, 'employees']);
        Route::get('reports/branches', [ReportController::class, 'branches']);
        Route::get('reports/ledger', [ReportController::class, 'ledger']);

        Route::get('analytics/dashboard', [AnalyticsController::class, 'dashboard']);
        Route::get('analytics/compare', [AnalyticsController::class, 'compare']);

        Route::post('webhooks', [WebhookController::class, 'store']);
    });
});
