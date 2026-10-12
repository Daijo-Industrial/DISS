<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\Project\CheckpointController;
use App\Http\Controllers\Api\V1\Project\ProjectController;
use App\Http\Controllers\Api\V1\Project\ProjectDashboardController;
use App\Http\Controllers\Api\V1\Project\ProjectExtensionController;
use App\Http\Controllers\Api\V1\Project\ProjectWorkflowController;
use App\Http\Controllers\Api\V1\PurchaseRequestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->as('api.v1.')->group(function () {
    // Public routes
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        // Auth
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        // Purchase Requests
        Route::apiResource('purchase-requests', PurchaseRequestController::class);
        Route::post('/purchase-requests/{purchaseRequest}/approve', [PurchaseRequestController::class, 'approve']);
        Route::post('/purchase-requests/{purchaseRequest}/reject', [PurchaseRequestController::class, 'reject']);
        Route::get('/purchase-requests/{id}/history', [PurchaseRequestController::class, 'history']);

        // IT Project & KPI Management
        Route::prefix('projects')->as('projects.')->group(function () {
            Route::get('/dashboard/summary', [ProjectDashboardController::class, 'summary'])->name('dashboard.summary');
            Route::get('/dashboard/stage-distribution', [ProjectDashboardController::class, 'stageDistribution'])->name('dashboard.stage-distribution');
            Route::get('/dashboard/pic-leaderboard', [ProjectDashboardController::class, 'picLeaderboard'])->name('dashboard.pic-leaderboard');
            Route::get('/dashboard/upcoming-deadlines', [ProjectDashboardController::class, 'upcomingDeadlines'])->name('dashboard.upcoming-deadlines');

            Route::get('/', [ProjectController::class, 'index'])->name('index');
            Route::post('/', [ProjectController::class, 'store'])->name('store');
            Route::get('/{id}', [ProjectController::class, 'show'])->name('show');
            Route::put('/{id}', [ProjectController::class, 'update'])->name('update');
            Route::delete('/{id}', [ProjectController::class, 'destroy'])->name('destroy');

            Route::post('/{id}/transition-stage', [ProjectWorkflowController::class, 'transitionStage'])->name('transition-stage');
            Route::post('/{id}/hold', [ProjectWorkflowController::class, 'hold'])->name('hold');
            Route::post('/{id}/resume', [ProjectWorkflowController::class, 'resume'])->name('resume');
            Route::post('/{id}/cancel', [ProjectWorkflowController::class, 'cancel'])->name('cancel');
            Route::post('/{id}/approve-closure', [ProjectWorkflowController::class, 'approveClosure'])->name('approve-closure');
            Route::post('/{id}/reject-closure', [ProjectWorkflowController::class, 'rejectClosure'])->name('reject-closure');

            Route::get('/{projectId}/checkpoints', [CheckpointController::class, 'index'])->name('checkpoints.index');
            Route::post('/{projectId}/checkpoints', [CheckpointController::class, 'store'])->name('checkpoints.store');

            Route::get('/{projectId}/extensions', [ProjectExtensionController::class, 'index'])->name('extensions.index');
            Route::post('/{projectId}/extensions', [ProjectExtensionController::class, 'store'])->name('extensions.store');
        });

        Route::prefix('checkpoints')->as('checkpoints.')->group(function () {
            Route::put('/{id}', [CheckpointController::class, 'update'])->name('update');
            Route::delete('/{id}', [CheckpointController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/complete', [CheckpointController::class, 'complete'])->name('complete');
            Route::post('/{id}/attachments', [CheckpointController::class, 'uploadAttachment'])->name('attachments.upload');
            Route::get('/attachments/{attachmentId}/download', [CheckpointController::class, 'downloadAttachment'])->name('attachments.download');
        });
    });
});
