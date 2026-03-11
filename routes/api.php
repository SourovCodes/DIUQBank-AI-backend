<?php

use App\Http\Controllers\Api\ContributorController;
use App\Http\Controllers\Api\FilterOptionsController;
use App\Http\Controllers\Api\GoogleAuthController;
use App\Http\Controllers\Api\LogoutController;
use App\Http\Controllers\Api\QuestionController;
use App\Http\Controllers\Api\QuickUploadController;
use App\Http\Controllers\Api\SubmissionController;
use App\Http\Controllers\Api\UserAccessTokenController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

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
