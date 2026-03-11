<?php

use App\Http\Controllers\Api\V1\ContributorController;
use App\Http\Controllers\Api\V1\CurrentUserController;
use App\Http\Controllers\Api\V1\FilterOptionsController;
use App\Http\Controllers\Api\V1\GoogleAuthController;
use App\Http\Controllers\Api\V1\LogoutController;
use App\Http\Controllers\Api\V1\QuestionController;
use App\Http\Controllers\Api\V1\QuickUploadController;
use App\Http\Controllers\Api\V1\SubmissionController;
use App\Http\Controllers\Api\V1\UserAccessTokenController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/auth/user', CurrentUserController::class)->middleware('auth:sanctum');

    Route::get('/filter-options', FilterOptionsController::class);
    Route::get('/contributors', [ContributorController::class, 'index']);
    Route::get('/contributors/{contributor}', [ContributorController::class, 'show']);
    Route::post('/auth/google', [GoogleAuthController::class, 'store']);
    Route::post('/auth/logout', [LogoutController::class, 'store'])->middleware('auth:sanctum');
    Route::post('/quick-uploads/upload-url', [QuickUploadController::class, 'createUploadUrl'])->middleware('auth:sanctum');
    Route::post('/quick-uploads', [QuickUploadController::class, 'store'])->middleware('auth:sanctum');
    Route::get('/questions', [QuestionController::class, 'index']);
    Route::get('/questions/{question}', [QuestionController::class, 'show']);
    Route::post('/questions/{question}/views', [QuestionController::class, 'incrementViews']);
    Route::post('/submissions/{submission}/views', [SubmissionController::class, 'incrementViews']);

    if (app()->environment('local')) {
        Route::post('/users/{user}/access-token', UserAccessTokenController::class);
    }
});
