<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Middleware\JwtMiddleware;

// Public routes (no authentication required)
Route::post('/users', [UserController::class, 'store']); // For auth-service sync
Route::get('/users/validate/{id}', [UserController::class, 'validateUser']); // For order-service validation

// Protected routes (authentication required)
Route::middleware([JwtMiddleware::class])->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
    Route::get('/profile', [UserController::class, 'profile']);
});
