<?php

use App\Bracket\GroupStandingsService;
use App\Cache\TournamentCache;
use App\Enums\FixtureStage;
use App\Enums\FixtureStatus;
use App\Fixtures\FixtureResult;
use App\Fixtures\FixtureResultRecorder;
use App\Models\Fixture;
use App\Models\Prediction;
use App\Models\User;
use App\Predictions\Importing\WorldCupImporter;
use App\Predictions\Leaderboard\LeaderboardService;
use App\Predictions\Settlement\SettlementService;
use Database\Seeders\PredictionMarketSeeder;
use Illuminate\Support\Facades\DB;

it('resolves as a singleton', function () {
    expect(app(TournamentCache::class))->toBe(app(TournamentCache::class));
});

it('returns cached value on second remember call', function () {
    $cache = app(TournamentCache::class);
    $calls = 0;

    $cache->remember('bracket', 'test', function () use (&$calls) {
        $calls++;

        return ['a' => 1];
    });
    $cache->remember('bracket', 'test', function () use (&$calls) {
        $calls++;

        return ['a' => 2];
    });

    expect($calls)->toBe(1);
});

it('bump invalidates remembered values', function () {
    $cache = app(TournamentCache::class);
    $calls = 0;

    $first = $cache->remember('leaderboard', 'overall', function () use (&$calls) {
        $calls++;

        return ['rank' => 1];
    });

    $cache->bump('leaderboard');

    $second = $cache->remember('leaderboard', 'overall', function () use (&$calls) {
        $calls++;

        return ['rank' => 2];
    });

    expect($first)->toBe(['rank' => 1])
        ->and($second)->toBe(['rank' => 2])
        ->and($calls)->toBe(2);
});

it('caches group standings across repeated reads', function () {
    $this->seed(PredictionMarketSeeder::class);
    WorldCupImporter::fromDefaultPath()->import();

    $service = app(GroupStandingsService::class);

    DB::enableQueryLog();
    $service->standingsForGroup('A');
    $firstCount = count(DB::getQueryLog());

    DB::flushQueryLog();
    $service->standingsForGroup('A');
    $secondCount = count(DB::getQueryLog());

    expect($firstCount)->toBeGreaterThan(0)
        ->and($secondCount)->toBe(0);
});

it('busts standings cache when a group fixture result is recorded', function () {
    $this->seed(PredictionMarketSeeder::class);
    WorldCupImporter::fromDefaultPath()->import();

    $service = app(GroupStandingsService::class);
    $before = $service->standingsForGroup('A');

    $fixture = Fixture::query()
        ->where('stage', FixtureStage::Group)
        ->where('group_code', 'A')
        ->where('status', '!=', FixtureStatus::Final)
        ->firstOrFail();

    app(FixtureResultRecorder::class)->record($fixture, new FixtureResult(2, 1));

    $after = $service->standingsForGroup('A');

    expect($before)->not->toBe($after);
});

it('includes live group fixtures with scores in standings', function () {
    $this->seed(PredictionMarketSeeder::class);
    WorldCupImporter::fromDefaultPath()->import();

    $service = app(GroupStandingsService::class);
    $before = $service->standingsForGroup('A');

    $fixture = Fixture::query()
        ->where('stage', FixtureStage::Group)
        ->where('group_code', 'A')
        ->where('status', '!=', FixtureStatus::Final)
        ->firstOrFail();

    app(FixtureResultRecorder::class)->recordLive($fixture, new FixtureResult(2, 1));

    $after = $service->standingsForGroup('A');

    expect($before)->not->toBe($after);
});

it('busts leaderboard cache when predictions are settled', function () {
    ['fixture' => $fixture, 'winner' => $winner] = finalFixtureWithMarkets(2, 1);
    $user = User::factory()->create(['name' => 'Ada']);

    Prediction::factory()->for($user)->create([
        'fixture_market_id' => $winner->id,
        'value' => ['selected' => 'home'],
    ]);

    $leaderboard = app(LeaderboardService::class);
    $calls = 0;

    $leaderboard->overall();
    $cache = app(TournamentCache::class);

    $cache->remember('leaderboard', 'overall:50', function () use (&$calls) {
        $calls++;

        return collect();
    });

    expect($calls)->toBe(0);

    app(SettlementService::class)->settleFixture($fixture);

    $cache->remember('leaderboard', 'overall:50', function () use (&$calls) {
        $calls++;

        return collect();
    });

    expect($calls)->toBe(1);
});

it('caches leaderboard overall across repeated reads', function () {
    ['fixture' => $fixture, 'winner' => $winner] = finalFixtureWithMarkets(2, 1);
    $user = User::factory()->create();

    Prediction::factory()->for($user)->create([
        'fixture_market_id' => $winner->id,
        'value' => ['selected' => 'home'],
    ]);

    app(SettlementService::class)->settleFixture($fixture);

    $service = app(LeaderboardService::class);

    DB::enableQueryLog();
    $service->overall();
    $firstCount = count(DB::getQueryLog());

    DB::flushQueryLog();
    $service->overall();
    $secondCount = count(DB::getQueryLog());

    expect($firstCount)->toBeGreaterThan(0)
        ->and($secondCount)->toBe(0);
});
