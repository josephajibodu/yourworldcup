<?php

use App\Enums\HighestBookingOutcome;
use App\Enums\LastGoalOutcome;
use App\Enums\MarketStatus;
use App\Enums\ResultDuration;
use App\Models\Fixture;
use App\Models\FixtureMarket;
use App\Models\Prediction;
use App\Models\PredictionMarket;
use App\Models\Team;
use App\Models\User;
use App\Predictions\Settlement\AwayCleanSheetSettler;
use App\Predictions\Settlement\HighestBookingSettler;
use App\Predictions\Settlement\HomeCleanSheetSettler;
use App\Predictions\Settlement\LastGoalSettler;
use App\Predictions\Settlement\PenaltyShootoutSettler;
use App\Predictions\Settlement\SettlementService;
use App\Predictions\Settlement\ToQualifySettler;
use Database\Seeders\PredictionMarketSeeder;

it('settles to qualify from the winning team', function () {
    $home = Team::factory()->create();
    $away = Team::factory()->create();
    $fixture = Fixture::factory()->final(1, 1)->create([
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
        'winner_team_id' => $away->id,
        'penalties_home' => 3,
        'penalties_away' => 4,
        'result_duration' => ResultDuration::Penalties,
    ]);

    expect((new ToQualifySettler)->settle($fixture))->toBe(['selected' => 'away']);
});

it('does not settle to qualify without a winner', function () {
    $fixture = Fixture::factory()->final(1, 1)->create([
        'winner_team_id' => null,
    ]);

    expect((new ToQualifySettler)->settle($fixture))->toBeNull();
});

it('settles penalty shootout from result duration or penalty scores', function () {
    $penalties = Fixture::factory()->final(1, 1)->create([
        'result_duration' => ResultDuration::Penalties,
    ]);
    $regular = Fixture::factory()->final(2, 1)->create([
        'result_duration' => ResultDuration::Regular,
    ]);
    $withScores = Fixture::factory()->final(0, 0)->create([
        'penalties_home' => 4,
        'penalties_away' => 5,
        'result_duration' => null,
    ]);

    $settler = new PenaltyShootoutSettler;

    expect($settler->settle($penalties))->toBe(['answer' => true])
        ->and($settler->settle($regular))->toBe(['answer' => false])
        ->and($settler->settle($withScores))->toBe(['answer' => true]);
});

it('settles clean sheets from regular time only', function () {
    $homeSheet = Fixture::factory()->final(1, 0)->create([
        'extra_time_home' => 1,
        'extra_time_away' => 0,
    ]);
    $awaySheetDespiteExtraTime = Fixture::factory()->final(0, 0)->create([
        'extra_time_home' => 1,
        'extra_time_away' => 0,
    ]);
    $noHomeSheet = Fixture::factory()->final(0, 1)->create([
        'extra_time_home' => 2,
        'extra_time_away' => 0,
    ]);

    expect((new HomeCleanSheetSettler)->settle($homeSheet))->toBe(['answer' => true])
        ->and((new AwayCleanSheetSettler)->settle($homeSheet))->toBe(['answer' => false])
        ->and((new AwayCleanSheetSettler)->settle($awaySheetDespiteExtraTime))->toBe(['answer' => true])
        ->and((new HomeCleanSheetSettler)->settle($noHomeSheet))->toBe(['answer' => false]);
});

it('waits for last goal and highest booking until admin values are set', function () {
    $fixture = Fixture::factory()->final(2, 1)->create([
        'last_goal' => null,
        'highest_booking' => null,
    ]);

    expect((new LastGoalSettler)->settle($fixture))->toBeNull()
        ->and((new HighestBookingSettler)->settle($fixture))->toBeNull();

    $fixture->update([
        'last_goal' => LastGoalOutcome::Away,
        'highest_booking' => HighestBookingOutcome::Draw,
    ]);

    expect((new LastGoalSettler)->settle($fixture->fresh()))->toBe(['selected' => 'away'])
        ->and((new HighestBookingSettler)->settle($fixture->fresh()))->toBe(['selected' => 'draw']);
});

it('scores and settles all new markets on a finished fixture', function () {
    $this->seed(PredictionMarketSeeder::class);

    $home = Team::factory()->create();
    $away = Team::factory()->create();
    $kickoff = now()->subHours(3);
    $fixture = Fixture::factory()->final(2, 0)->create([
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
        'winner_team_id' => $home->id,
        'kickoff_at' => $kickoff,
        'lock_at' => $kickoff->copy()->subHour(),
        'result_duration' => ResultDuration::Regular,
        'last_goal' => LastGoalOutcome::Home,
        'highest_booking' => HighestBookingOutcome::Away,
    ]);

    $keys = [
        'to_qualify',
        'penalty_shootout',
        'home_clean_sheet',
        'away_clean_sheet',
        'last_goal',
        'highest_booking',
    ];

    $markets = PredictionMarket::query()->whereIn('key', $keys)->get()->keyBy('key');
    $fixtureMarkets = [];

    foreach ($keys as $key) {
        $fixtureMarkets[$key] = FixtureMarket::factory()->create([
            'fixture_id' => $fixture->id,
            'prediction_market_id' => $markets[$key]->id,
        ]);
    }

    $user = User::factory()->create();

    Prediction::factory()->for($user)->create([
        'fixture_market_id' => $fixtureMarkets['to_qualify']->id,
        'value' => ['selected' => 'home'],
    ]);
    Prediction::factory()->for($user)->create([
        'fixture_market_id' => $fixtureMarkets['penalty_shootout']->id,
        'value' => ['answer' => false],
    ]);
    Prediction::factory()->for($user)->create([
        'fixture_market_id' => $fixtureMarkets['home_clean_sheet']->id,
        'value' => ['answer' => true],
    ]);
    Prediction::factory()->for($user)->create([
        'fixture_market_id' => $fixtureMarkets['away_clean_sheet']->id,
        'value' => ['answer' => false],
    ]);
    Prediction::factory()->for($user)->create([
        'fixture_market_id' => $fixtureMarkets['last_goal']->id,
        'value' => ['selected' => 'home'],
    ]);
    Prediction::factory()->for($user)->create([
        'fixture_market_id' => $fixtureMarkets['highest_booking']->id,
        'value' => ['selected' => 'away'],
    ]);

    app(SettlementService::class)->settleFixture($fixture);

    foreach ($fixtureMarkets as $market) {
        expect($market->fresh()->status)->toBe(MarketStatus::Settled);
    }

    expect(Prediction::query()->where('user_id', $user->id)->sum('points_awarded'))->toBe(8);
});
