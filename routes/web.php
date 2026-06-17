<?php

use App\Http\Controllers\BracketController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\PlayerCountController;
use App\Http\Controllers\PredictController;
use App\Http\Controllers\ReferralController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::get('bracket', [BracketController::class, 'index'])->name('bracket');
Route::get('leaderboard', [LeaderboardController::class, 'index'])->name('leaderboard');
Route::get('referrals', [ReferralController::class, 'index'])->name('referrals');
Route::get('players/count', PlayerCountController::class)->name('players.count');

Route::middleware('auth')->group(function () {
    Route::get('predict', [PredictController::class, 'index'])->name('predict');
    Route::post('predict', [PredictController::class, 'store'])->name('predict.store');
});

Route::middleware(['auth', 'verified', 'admin'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
