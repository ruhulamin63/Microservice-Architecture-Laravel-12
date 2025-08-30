<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GatewayController;

// Health check
Route::get('/health', [GatewayController::class, 'health']);

// Authentication routes
Route::post('auth/register', [GatewayController::class, 'auth']);
Route::post('auth/login', [GatewayController::class, 'auth']);
Route::post('auth/validate-token', [GatewayController::class, 'auth']);
Route::get('auth/profile', [GatewayController::class, 'auth']);

// User routes
Route::get('users', [GatewayController::class, 'users']);
Route::post('users', [GatewayController::class, 'users']);
Route::get('users/{id}', [GatewayController::class, 'users']);
Route::put('users/{id}', [GatewayController::class, 'users']);
Route::delete('users/{id}', [GatewayController::class, 'users']);
Route::get('users/validate/{id}', [GatewayController::class, 'users']);

// Order routes
Route::get('orders', [GatewayController::class, 'orders']);
Route::post('orders', [GatewayController::class, 'orders']);
Route::get('orders/{id}', [GatewayController::class, 'orders']);
Route::put('orders/{id}', [GatewayController::class, 'orders']);
Route::delete('/orders/{id}', [GatewayController::class, 'orders']);
Route::get('orders/statistics', [GatewayController::class, 'orders']);
