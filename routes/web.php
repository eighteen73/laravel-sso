<?php

use Eighteen73\SSO\Http\Controllers\SSOController;
use Illuminate\Support\Facades\Route;

Route::middleware(config('sso.routes.middleware', ['web']))
    ->prefix(config('sso.routes.prefix', 'sso'))
    ->group(function () {
        Route::get('/login', [SSOController::class, 'login'])->name('sso.login');
        Route::get('/callback', [SSOController::class, 'callback'])->name('sso.callback');
    });
