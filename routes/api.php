<?php

use App\Http\Controllers\Api\UserAccessTokenController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

if (app()->environment('local')) {
    Route::post('/users/{user}/access-token', UserAccessTokenController::class);
}
