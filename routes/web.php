<?php

use App\Http\Controllers\DreamController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test1', function () {
    return view('admin.layout.app');
});


Route::get('/ruyatabiri', [DreamController::class, 'index'])->name('dreamIndex');
Route::post('/ruyatabiri', [DreamController::class, 'analyze'])->name('dreamAnalyze');
Route::post('/ruyagorsel', [DreamController::class, 'generateImage'])->name('dreamImage');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('welcome');})->name('dashboard');
    Route::get('/profile', function () {
        return view('profile.show');})->name('profile');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/ruyatabiri', [DreamController::class, 'index'])->name('dreamIndex');
});
