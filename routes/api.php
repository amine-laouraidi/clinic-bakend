<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;

// Public
Route::prefix('auth')->group(function () {
    Route::post('/register',                [AuthController::class, 'register']);
    Route::post('/login',                   [AuthController::class, 'login']);
    Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->middleware(['signed'])
        ->name('verification.verify');
});

// Protected
Route::prefix('auth')->middleware('auth:sanctum')->group(function () {
    Route::post('/logout',                  [AuthController::class, 'logout']);
    Route::post('/logout-all',              [AuthController::class, 'logoutAll']);
    Route::post('/email/resend',     [AuthController::class, 'resendVerification'])
        ->middleware(['auth:api', 'throttle:6,1'])
        ->name('verification.send');
    Route::get('/me',                       [AuthController::class, 'me']);
    Route::get('/sessions',                 [AuthController::class, 'sessions']);
    Route::delete('/sessions/{tokenId}',    [AuthController::class, 'revokeDevice']);
});
