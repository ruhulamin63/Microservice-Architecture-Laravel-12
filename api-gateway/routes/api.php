<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GatewayController;

// Health check
Route::get('/health', [GatewayController::class, 'health']);

// Authentication routes
Route::post('auth/{endpoint}', [GatewayController::class, 'auth'])->where('endpoint', 'register|login|validate-token');
Route::get('auth/{endpoint}', [GatewayController::class, 'auth'])->where('endpoint', 'profile');

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
