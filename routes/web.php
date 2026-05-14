<?php

use App\Http\Controllers\DreamController;
use Illuminate\Support\Facades\Route;

    Route::get('/', function () {
        return view('welcome');
    });

    // test 1
    Route::get('/test1', function () {
        return view('admin.layout.app');
    });

    // sadece giriş yapmış kullanıcıların rüya işlemlerini yapmasını sağlıyoruz
    Route::middleware(['auth', 'verified'])->group(function () {

        Route::get('/dashboard', function () {
            return view('welcome');
        })->name('dashboard');

        Route::get('/profile', function () {
            return view('profile.show');
        })->name('profile');

        // rüya analizi
        Route::get('/ruyatabiri', [DreamController::class, 'index'])->name('dreamIndex');
        Route::post('/ruyatabiri', [DreamController::class, 'analyze'])->name('dreamAnalyze');

        // görsel üretimi formu için post
        Route::post('/ruyagorsel', [DreamController::class, 'generateImage'])->name('dreamImage');

        // rüya günlüğü
        Route::get('/dreamlog', [DreamController::class, 'dreamList'])->name('dreamlogList');
        Route::get('/dreamlog/{id}', [DreamController::class, 'dreamShow'])->name('dreamlogShow');
        Route::delete('/dreamlog/{id}', [DreamController::class, 'dreamLogDelete'])->name('dreamlogDelete');
    });
