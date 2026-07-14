<?php

use App\Enums\FixtureStage;
use App\Enums\MarketStatus;
use App\Models\Fixture;
use App\Models\FixtureMarket;
use App\Models\Prediction;
use App\Models\PredictionMarket;
use App\Models\Team;
use App\Models\User;
use App\Predictions\Markets\M101PlayerMarkets;
use App\Predictions\Settlement\SettlementService;

it('attaches M101 player markets only to match 101', function () {
    $home = Team::factory()->create(['name' => 'France']);
    $away = Team::factory()->create(['name' => 'Spain']);
    $otherHome = Team::factory()->create(['name' => 'Germany']);
    $otherAway = Team::factory()->create(['name' => 'Portugal']);

    $target = Fixture::factory()->create([
        'external_id' => '101',
        'stage' => FixtureStage::SemiFinal,
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
    ]);

    Fixture::factory()->create([
        'external_id' => '102',
        'home_team_id' => $otherHome->id,
        'away_team_id' => $otherAway->id,
    ]);

    $this->artisan('markets:attach-m101-players')
        ->expectsOutputToContain('Attached 7 player markets')
        ->assertSuccessful();

    expect(PredictionMarket::query()->whereIn('key', M101PlayerMarkets::keys())->count())->toBe(7)
        ->and(
            FixtureMarket::query()
                ->where('fixture_id', $target->id)
                ->whereIn('prediction_market_id', PredictionMarket::query()->whereIn('key', M101PlayerMarkets::keys())->pluck('id'))
                ->count(),
        )->toBe(7)
        ->and(FixtureMarket::query()->where('fixture_id', '!=', $target->id)->count())->toBe(0);

    $catalogue = PredictionMarket::query()->where('key', 'player_mbappe_score')->firstOrFail();

    expect($catalogue->is_enabled)->toBeFalse();
});

it('refuses to attach when teams do not match France and Spain', function () {
    Fixture::factory()->create([
        'external_id' => '101',
        'home_team_id' => Team::factory()->create(['name' => 'Germany'])->id,
        'away_team_id' => Team::factory()->create(['name' => 'Portugal'])->id,
    ]);

    $this->artisan('markets:attach-m101-players')
        ->expectsOutputToContain('Expected teams [France, Spain]')
        ->assertFailed();
});

it('can override the team check with force', function () {
    Fixture::factory()->create([
        'external_id' => '101',
        'home_team_id' => Team::factory()->create(['name' => 'Germany'])->id,
        'away_team_id' => Team::factory()->create(['name' => 'Portugal'])->id,
    ]);

    $this->artisan('markets:attach-m101-players', ['--force' => true])
        ->assertSuccessful();
});

it('settles player markets from recorded player outcomes', function () {
    $home = Team::factory()->create(['name' => 'France']);
    $away = Team::factory()->create(['name' => 'Spain']);
    $kickoff = now()->subHours(3);

    $fixture = Fixture::factory()->final(2, 1)->create([
        'external_id' => '101',
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
        'kickoff_at' => $kickoff,
        'lock_at' => $kickoff->copy()->subHour(),
        'player_outcomes' => [
            'player_mbappe_score' => true,
            'player_dembele_score' => false,
        ],
    ]);

    $this->artisan('markets:attach-m101-players')->assertSuccessful();

    $mbappeMarket = FixtureMarket::query()
        ->where('fixture_id', $fixture->id)
        ->whereHas('market', fn ($query) => $query->where('key', 'player_mbappe_score'))
        ->firstOrFail();

    $dembeleMarket = FixtureMarket::query()
        ->where('fixture_id', $fixture->id)
        ->whereHas('market', fn ($query) => $query->where('key', 'player_dembele_score'))
        ->firstOrFail();

    $user = User::factory()->create();

    Prediction::factory()->for($user)->create([
        'fixture_market_id' => $mbappeMarket->id,
        'value' => ['answer' => true],
    ]);

    Prediction::factory()->for($user)->create([
        'fixture_market_id' => $dembeleMarket->id,
        'value' => ['answer' => true],
    ]);

    app(SettlementService::class)->settleFixture($fixture);

    expect($mbappeMarket->fresh()->status)->toBe(MarketStatus::Settled)
        ->and($mbappeMarket->fresh()->settlement_value)->toBe(['answer' => true])
        ->and($dembeleMarket->fresh()->settlement_value)->toBe(['answer' => false])
        ->and(Prediction::query()->where('fixture_market_id', $mbappeMarket->id)->sole()->points_awarded)->toBe(1)
        ->and(Prediction::query()->where('fixture_market_id', $dembeleMarket->id)->sole()->points_awarded)->toBe(0);
});

it('is idempotent when run twice', function () {
    Fixture::factory()->create([
        'external_id' => '101',
        'home_team_id' => Team::factory()->create(['name' => 'France'])->id,
        'away_team_id' => Team::factory()->create(['name' => 'Spain'])->id,
    ]);

    $this->artisan('markets:attach-m101-players')->assertSuccessful();
    $this->artisan('markets:attach-m101-players')->assertSuccessful();

    expect(FixtureMarket::query()->count())->toBe(7);
});
