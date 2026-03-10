<?php

use App\Http\Controllers\Api\FilterOptionsController;
use App\Http\Controllers\Api\UserAccessTokenController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/filter-options', FilterOptionsController::class);

if (app()->environment('local')) {
    Route::post('/users/{user}/access-token', UserAccessTokenController::class);
}
