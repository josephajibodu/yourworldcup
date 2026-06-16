<?php

use App\Http\Controllers\BracketController;
use App\Http\Controllers\PredictController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::get('bracket', [BracketController::class, 'index'])->name('bracket');

Route::middleware('auth')->group(function () {
    Route::get('predict', [PredictController::class, 'index'])->name('predict');
    Route::post('predict', [PredictController::class, 'store'])->name('predict.store');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
