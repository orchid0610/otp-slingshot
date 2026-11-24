<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\OtpController;
use App\Http\Middleware\AdminAuth;
use Illuminate\Support\Facades\Route;

Route::controller(OtpController::class)->group(function () {
    Route::get('/', 'index')->name('home');
    Route::post('/send', 'send')->middleware('throttle:6,1')->name('otp.send');
    Route::get('/leaderboard', 'leaderboard')->name('leaderboard');
    Route::get('/verify', 'show')->name('verify.show');
    Route::post('/verify/throw', 'throwDigit')->name('verify.throw');
});

Route::controller(AdminController::class)->prefix('admin')->group(function () {
    Route::get('/login', 'login')->name('admin.login');
    Route::post('/login', 'authenticate')->middleware('throttle:10,1')->name('admin.authenticate');

    Route::middleware(AdminAuth::class)->group(function () {
        Route::get('/', 'index')->name('admin.index');
        Route::post('/logout', 'logout')->name('admin.logout');
        Route::patch('/smtp', 'saveSmtp')->name('admin.smtp.save');
        Route::post('/smtp/test', 'testSmtp')->name('admin.smtp.test');
        Route::delete('/leaderboard', 'clearBoard')->name('admin.leaderboard.clear');
    });
});
