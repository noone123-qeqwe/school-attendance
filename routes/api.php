<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ParentApiController;

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    Route::post('/logout', [AuthController::class, 'logout']);

    // Parent specific mobile API endpoints
    Route::prefix('parent')->middleware('parent')->group(function () {
        Route::get('/dashboard', [ParentApiController::class, 'dashboard']);
        Route::get('/child/{child}', [ParentApiController::class, 'childDetail']);
    });
});
