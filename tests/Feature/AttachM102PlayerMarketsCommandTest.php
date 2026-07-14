<?php

use App\Enums\FixtureStage;
use App\Enums\MarketStatus;
use App\Models\Fixture;
use App\Models\FixtureMarket;
use App\Models\Prediction;
use App\Models\PredictionMarket;
use App\Models\Team;
use App\Models\User;
use App\Predictions\Markets\M102PlayerMarkets;
use App\Predictions\Settlement\SettlementService;

it('attaches M102 player markets only to match 102', function () {
    $home = Team::factory()->create(['name' => 'England']);
    $away = Team::factory()->create(['name' => 'Argentina']);
    $otherHome = Team::factory()->create(['name' => 'France']);
    $otherAway = Team::factory()->create(['name' => 'Spain']);

    $target = Fixture::factory()->create([
        'external_id' => '102',
        'stage' => FixtureStage::SemiFinal,
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
    ]);

    Fixture::factory()->create([
        'external_id' => '101',
        'home_team_id' => $otherHome->id,
        'away_team_id' => $otherAway->id,
    ]);

    $this->artisan('markets:attach-m102-players')
        ->expectsOutputToContain('Attached 9 player markets')
        ->assertSuccessful();

    expect(PredictionMarket::query()->whereIn('key', M102PlayerMarkets::keys())->count())->toBe(9)
        ->and(
            FixtureMarket::query()
                ->where('fixture_id', $target->id)
                ->whereIn('prediction_market_id', PredictionMarket::query()->whereIn('key', M102PlayerMarkets::keys())->pluck('id'))
                ->count(),
        )->toBe(9)
        ->and(FixtureMarket::query()->where('fixture_id', '!=', $target->id)->count())->toBe(0);

    $catalogue = PredictionMarket::query()->where('key', 'player_messi_score_or_assist')->firstOrFail();

    expect($catalogue->is_enabled)->toBeFalse();
});

it('refuses to attach when teams do not match England and Argentina', function () {
    Fixture::factory()->create([
        'external_id' => '102',
        'home_team_id' => Team::factory()->create(['name' => 'France'])->id,
        'away_team_id' => Team::factory()->create(['name' => 'Spain'])->id,
    ]);

    $this->artisan('markets:attach-m102-players')
        ->expectsOutputToContain('Expected teams [Argentina, England]')
        ->assertFailed();
});

it('can override the team check with force', function () {
    Fixture::factory()->create([
        'external_id' => '102',
        'home_team_id' => Team::factory()->create(['name' => 'France'])->id,
        'away_team_id' => Team::factory()->create(['name' => 'Spain'])->id,
    ]);

    $this->artisan('markets:attach-m102-players', ['--force' => true])
        ->assertSuccessful();
});

it('settles player markets from recorded player outcomes', function () {
    $home = Team::factory()->create(['name' => 'England']);
    $away = Team::factory()->create(['name' => 'Argentina']);
    $kickoff = now()->subHours(3);

    $fixture = Fixture::factory()->final(1, 2)->create([
        'external_id' => '102',
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
        'kickoff_at' => $kickoff,
        'lock_at' => $kickoff->copy()->subHour(),
        'player_outcomes' => [
            'player_messi_score_or_assist' => true,
            'player_kane_score' => false,
        ],
    ]);

    $this->artisan('markets:attach-m102-players')->assertSuccessful();

    $messiMarket = FixtureMarket::query()
        ->where('fixture_id', $fixture->id)
        ->whereHas('market', fn ($query) => $query->where('key', 'player_messi_score_or_assist'))
        ->firstOrFail();

    $kaneMarket = FixtureMarket::query()
        ->where('fixture_id', $fixture->id)
        ->whereHas('market', fn ($query) => $query->where('key', 'player_kane_score'))
        ->firstOrFail();

    $user = User::factory()->create();

    Prediction::factory()->for($user)->create([
        'fixture_market_id' => $messiMarket->id,
        'value' => ['answer' => true],
    ]);

    Prediction::factory()->for($user)->create([
        'fixture_market_id' => $kaneMarket->id,
        'value' => ['answer' => true],
    ]);

    app(SettlementService::class)->settleFixture($fixture);

    expect($messiMarket->fresh()->status)->toBe(MarketStatus::Settled)
        ->and($messiMarket->fresh()->settlement_value)->toBe(['answer' => true])
        ->and($kaneMarket->fresh()->settlement_value)->toBe(['answer' => false])
        ->and(Prediction::query()->where('fixture_market_id', $messiMarket->id)->sole()->points_awarded)->toBe(1)
        ->and(Prediction::query()->where('fixture_market_id', $kaneMarket->id)->sole()->points_awarded)->toBe(0);
});

it('is idempotent when run twice', function () {
    Fixture::factory()->create([
        'external_id' => '102',
        'home_team_id' => Team::factory()->create(['name' => 'England'])->id,
        'away_team_id' => Team::factory()->create(['name' => 'Argentina'])->id,
    ]);

    $this->artisan('markets:attach-m102-players')->assertSuccessful();
    $this->artisan('markets:attach-m102-players')->assertSuccessful();

    expect(FixtureMarket::query()->count())->toBe(9);
});
