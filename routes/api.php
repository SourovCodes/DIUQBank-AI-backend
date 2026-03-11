<?php

use App\Http\Controllers\Api\FilterOptionsController;
use App\Http\Controllers\Api\QuestionController;
use App\Http\Controllers\Api\SubmissionController;
use App\Http\Controllers\Api\UserAccessTokenController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/filter-options', FilterOptionsController::class);
Route::get('/questions', [QuestionController::class, 'index']);
Route::get('/questions/{question}', [QuestionController::class, 'show']);
Route::post('/questions/{question}/views', [QuestionController::class, 'incrementViews']);
Route::post('/submissions/{submission}/views', [SubmissionController::class, 'incrementViews']);

if (app()->environment('local')) {
    Route::post('/users/{user}/access-token', UserAccessTokenController::class);
}
