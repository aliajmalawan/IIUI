<?php

declare(strict_types=1);

use App\Controllers\Auth\LoginController;
use App\Controllers\Auth\LogoutController;
use App\Controllers\Auth\PasswordResetController;
use App\Core\Router;

Router::group(['middleware' => ['csrf']], function (): void {

    Router::group(['middleware' => ['guest']], function (): void {
        Router::get('/login', [LoginController::class, 'show'])->name('login');
        Router::post('/login', [LoginController::class, 'store'])->name('login.attempt');

        Router::get('/forgot-password', [PasswordResetController::class, 'showRequest'])->name('password.request');
        Router::post('/forgot-password', [PasswordResetController::class, 'sendLink'])
            ->middleware('throttle:password_reset')
            ->name('password.email');

        Router::get('/reset-password/{token}', [PasswordResetController::class, 'showReset'])
            ->where('token', '[a-f0-9]{64}')
            ->name('password.reset');
        Router::post('/reset-password', [PasswordResetController::class, 'update'])->name('password.update');
    });

    Router::post('/logout', [LogoutController::class, 'destroy'])
        ->middleware('auth')
        ->name('logout');
});
