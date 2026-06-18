<?php

use App\Enums\FixtureStage;
use App\Enums\FixtureStatus;
use App\Models\Fixture;
use App\Models\Team;
use App\Models\User;
use App\Predictions\Importing\WorldCupImporter;
use Database\Seeders\PredictionMarketSeeder;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-06-01 12:00:00', 'UTC'));

    $this->seed(PredictionMarketSeeder::class);
    WorldCupImporter::fromDefaultPath()->import();
});

afterEach(function () {
    Carbon::setTestNow();
});

it('detects a fixture as live from status or kickoff window', function () {
    $fixture = Fixture::factory()->create([
        'kickoff_at' => now()->subMinutes(30),
        'lock_at' => now()->subMinutes(90),
        'status' => FixtureStatus::Scheduled,
    ]);

    expect($fixture->isLive())->toBeTrue();

    $fixture->update(['status' => FixtureStatus::Live]);

    expect($fixture->fresh()->isLive())->toBeTrue();

    $fixture->update(['status' => FixtureStatus::Final]);

    expect($fixture->fresh()->isLive())->toBeFalse();
});

it('stays live past the assumed window when status is live', function () {
    $fixture = Fixture::factory()->create([
        'kickoff_at' => now()->subMinutes(150),
        'lock_at' => now()->subMinutes(210),
        'status' => FixtureStatus::Scheduled,
    ]);

    expect($fixture->isLive())->toBeFalse();

    $fixture->update(['status' => FixtureStatus::Live]);

    expect($fixture->fresh()->isLive())->toBeTrue();
});

it('passes open group fixtures so live teams can be highlighted on the bracket', function () {
    $home = Team::query()->where('group_code', 'C')->orderBy('id')->first();
    $away = Team::query()
        ->where('group_code', 'C')
        ->where('id', '!=', $home->id)
        ->orderBy('id')
        ->first();

    Fixture::factory()->create([
        'stage' => FixtureStage::Group,
        'group_code' => 'C',
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
        'kickoff_at' => now()->subMinutes(20),
        'lock_at' => now()->subMinutes(80),
        'status' => FixtureStatus::Live,
    ]);

    $this->get(route('bracket'))->assertInertia(fn (Assert $page) => $page
        ->has('groups', 12, fn (Assert $group) => $group->etc())
        ->where('groups.2.code', 'C')
        ->has('groups.2.fixtures', 7)
        ->where('groups.2.fixtures.6.status', 'live')
        ->where('groups.2.fixtures.6.homeTeamId', $home->id)
        ->where('groups.2.fixtures.6.awayTeamId', $away->id)
    );
});

it('passes live status on knockout matches and predict fixtures', function () {
    $fixture = Fixture::factory()->create([
        'external_id' => '9999',
        'stage' => FixtureStage::Round32,
        'group_code' => null,
        'kickoff_at' => now()->subMinutes(15),
        'lock_at' => now()->subMinutes(75),
        'status' => FixtureStatus::Live,
    ]);

    actingAs(User::factory()->create())
        ->get(route('predict', ['date' => $fixture->watDate()]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('fixtures')
            ->where('fixtures', fn ($fixtures) => collect($fixtures)->contains(
                fn (array $row): bool => $row['id'] === $fixture->id && $row['status'] === 'live',
            ))
        );

    $this->get(route('bracket'))->assertInertia(fn (Assert $page) => $page
        ->has('knockout', 33)
        ->where('knockout.32.code', 'M9999')
        ->where('knockout.32.status', 'live')
    );
});
