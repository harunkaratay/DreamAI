<?php

use App\Http\Controllers\DreamController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Test amaçlı kalsın
Route::get('/test1', function () {
    return view('admin.layout.app');
});

// Sadece giriş yapmış kullanıcıların rüya işlemlerini yapmasını sağlıyoruz
Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/dashboard', function () {
        return view('welcome');
    })->name('dashboard');

    Route::get('/profile', function () {
        return view('profile.show');
    })->name('profile');

    // Rüya Analizi ve Görselleştirme Rotaları
    Route::get('/ruyatabiri', [DreamController::class, 'index'])->name('dreamIndex');
    Route::post('/ruyatabiri', [DreamController::class, 'analyze'])->name('dreamAnalyze');

    // BUTONA BASINCA ÇALIŞACAK ROTA
    Route::post('/ruyagorsel', [DreamController::class, 'generateImage'])->name('dreamImage');

    // Rüya Geçmişi ve Günlüğü
    Route::get('/dreamlog', [DreamController::class, 'dreamList'])->name('dreamlogList');
    Route::get('/dreamlog/{id}', [DreamController::class, 'dreamShow'])->name('dreamlogShow');
    Route::delete('/dreamlog/{id}', [DreamController::class, 'dreamLogDelete'])->name('dreamlogDelete');
});
