<?php

use App\Http\Controllers\Admin\BracketSlotController as AdminBracketSlotController;
use App\Http\Controllers\Admin\FixtureController as AdminFixtureController;
use App\Http\Controllers\Admin\LeaderboardController as AdminLeaderboardController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\UserPredictionController as AdminUserPredictionController;
use App\Http\Controllers\BestThirdController;
use App\Http\Controllers\BracketController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\PlayerCountController;
use App\Http\Controllers\PredictController;
use App\Http\Controllers\ReferralController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::get('bracket', [BracketController::class, 'index'])->name('bracket');
Route::get('best-thirds', [BestThirdController::class, 'index'])->name('best-thirds');
Route::get('leaderboard', [LeaderboardController::class, 'index'])->name('leaderboard');
Route::get('referrals', [ReferralController::class, 'index'])->name('referrals');
Route::get('players/count', PlayerCountController::class)->name('players.count');

Route::get('predict', [PredictController::class, 'index'])->name('predict');
Route::post('predict/return-url', [PredictController::class, 'rememberReturnUrl'])->name('predict.return-url');

Route::middleware('auth')->group(function () {
    Route::post('predict', [PredictController::class, 'store'])->name('predict.store');
});

Route::middleware(['auth', 'verified', 'admin'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('leaderboard', [AdminLeaderboardController::class, 'overall'])->name('leaderboard');
        Route::get('leaderboard/weekly', [AdminLeaderboardController::class, 'weekly'])->name('leaderboard.weekly');
        Route::get('leaderboard/daily', [AdminLeaderboardController::class, 'daily'])->name('leaderboard.daily');

        Route::get('fixtures', [AdminFixtureController::class, 'index'])->name('fixtures.index');
        Route::patch('fixtures/{fixture}', [AdminFixtureController::class, 'update'])->name('fixtures.update');

        Route::get('bracket-slots', [AdminBracketSlotController::class, 'index'])->name('bracket-slots.index');
        Route::patch('bracket-slots/{bracketSlot}', [AdminBracketSlotController::class, 'update'])->name('bracket-slots.update');

        Route::get('users', [AdminUserController::class, 'index'])->name('users.index');
        Route::get('users/{user}', [AdminUserController::class, 'show'])->name('users.show');
        Route::patch('users/{user}', [AdminUserController::class, 'update'])->name('users.update');
        Route::get('users/{user}/predictions', [AdminUserPredictionController::class, 'index'])->name('users.predictions.index');
    });
});

require __DIR__.'/settings.php';
