<?php

use Illuminate\Support\Facades\Route;
use ProjectsManager\Modules\Auth\Controllers\Api\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group.
|
*/

Route::prefix('v1')->group(function (): void {
    Route::prefix('auth')->group(function (): void {
        Route::post('login', [AuthController::class, 'login'])->name('api.v1.auth.login');
        Route::post('register', [AuthController::class, 'register'])->name('api.v1.auth.register');

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::get('me', [AuthController::class, 'me'])->name('api.v1.auth.me');
            Route::put('profile', [AuthController::class, 'updateProfile'])->name('api.v1.auth.profile');
            Route::post('fcm-token', [AuthController::class, 'updateFcmToken'])->name('api.v1.auth.fcm-token');
            Route::post('logout', [AuthController::class, 'logout'])->name('api.v1.auth.logout');
        });
    });
});
