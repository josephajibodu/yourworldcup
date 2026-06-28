<?php

use App\Enums\FixtureStage;
use App\Enums\FixtureStatus;
use App\Models\Fixture;
use App\Models\Team;
use App\Predictions\Importing\WorldCupImporter;
use Database\Seeders\PredictionMarketSeeder;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-06-01 12:00:00', 'UTC'));

    $this->seed(PredictionMarketSeeder::class);
    WorldCupImporter::fromDefaultPath()->import();
});

afterEach(function () {
    Carbon::setTestNow();
});

it('renders the bracket page publicly', function () {
    $this->get(route('bracket'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('bracket')
            ->has('focusNodeId')
            ->where('predictions.matchDurationMinutes', config('predictions.match_duration_minutes'))
        );
});

it('passes the twelve group tables with four teams each', function () {
    $this->get(route('bracket'))->assertInertia(fn (Assert $page) => $page
        ->has('groups', 12)
        ->has('groups.0', fn (Assert $group) => $group
            ->where('code', 'A')
            ->has('teams', 4)
            ->has('fixtures')
            ->has('teams.0', fn (Assert $team) => $team
                ->hasAll(['id', 'name', 'code', 'flag', 'played', 'gd', 'points'])
                ->etc()
            )
        )
    );
});

it('passes the full thirty-two match knockout tree', function () {
    $this->get(route('bracket'))->assertInertia(fn (Assert $page) => $page
        ->has('knockout', 32)
        ->has('knockout.0', fn (Assert $match) => $match
            ->where('code', 'M73')
            ->where('stage', 'r32')
            ->hasAll(['kickoffAt', 'stageLabel', 'home', 'away', 'feeders'])
            ->etc()
        )
    );
});

it('resolves feeder labels for later rounds and the final feeds the trophy', function () {
    $this->get(route('bracket'))->assertInertia(fn (Assert $page) => $page
        ->has('knockout', 32, fn (Assert $match) => $match->etc())
        // The final (M104) is fed by the two semi-finals (101, 102).
        ->where('knockout.31.code', 'M104')
        ->where('knockout.31.feeders', [101, 102])
        ->where('knockout.31.home.label', 'W101')
        ->where('knockout.31.away.label', 'W102')
    );
});

it('includes showpiece details for the final and third-place play-off', function () {
    $this->get(route('bracket'))->assertInertia(function (Assert $page) {
        $knockout = $page->toArray()['props']['knockout'];
        $byCode = collect($knockout)->keyBy('code');

        expect($byCode['M104']['headline'])->toBe('2026 FIFA World Cup Final')
            ->and($byCode['M104']['stadium'])->toBe('MetLife Stadium')
            ->and($byCode['M104']['location'])->toBe('East Rutherford, New Jersey')
            ->and($byCode['M104']['broadcast'])->toBe('FOX/Tele')
            ->and($byCode['M103']['headline'])->toBe('3rd-Place Match')
            ->and($byCode['M103']['stadium'])->toBe('Hard Rock Stadium')
            ->and($byCode['M103']['location'])->toBe('Miami Gardens, Florida');
    });
});

it('derives knockout pathway edges from bracket slots', function () {
    $this->get(route('bracket'))->assertInertia(function (Assert $page) {
        $knockout = $page->toArray()['props']['knockout'];
        $byCode = collect($knockout)->keyBy('code');

        expect($byCode['M89']['feeders'])->toBe([74, 77])
            ->and($byCode['M90']['feeders'])->toBe([73, 75])
            ->and($byCode['M91']['feeders'])->toBe([76, 78]);
    });
});

it('passes final scores for finished knockout matches', function () {
    $fixture = Fixture::query()
        ->where('external_id', '73')
        ->firstOrFail();

    $fixture->update([
        'status' => FixtureStatus::Final,
        'home_score' => 3,
        'away_score' => 2,
    ]);

    $this->get(route('bracket'))->assertInertia(fn (Assert $page) => $page
        ->where('knockout.0.code', 'M73')
        ->where('knockout.0.status', 'final')
        ->where('knockout.0.homeScore', 3)
        ->where('knockout.0.awayScore', 2)
    );
});

it('focuses the bracket on the group with a live match', function () {
    Fixture::query()->update([
        'kickoff_at' => now()->addDays(2),
        'lock_at' => now()->addDays(2)->subHour(),
    ]);

    $home = Team::query()->where('group_code', 'B')->orderBy('id')->first();
    $away = Team::query()
        ->where('group_code', 'B')
        ->where('id', '!=', $home->id)
        ->orderBy('id')
        ->first();

    Fixture::factory()->create([
        'external_id' => '998',
        'stage' => FixtureStage::Group,
        'group_code' => 'B',
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
        'kickoff_at' => now()->subMinutes(20),
        'lock_at' => now()->subMinutes(80),
        'status' => FixtureStatus::Live,
    ]);

    $this->get(route('bracket'))->assertInertia(fn (Assert $page) => $page
        ->where('focusNodeId', 'gB')
    );
});

it('focuses the bracket on the group with the next match when nothing is live', function () {
    Fixture::query()->update([
        'status' => FixtureStatus::Final,
        'kickoff_at' => now()->subDay(),
        'lock_at' => now()->subDay()->subHour(),
    ]);

    $home = Team::query()->where('group_code', 'H')->orderBy('id')->first();
    $away = Team::query()
        ->where('group_code', 'H')
        ->where('id', '!=', $home->id)
        ->orderBy('id')
        ->first();

    $kickoff = now()->addHours(3);

    Fixture::factory()->create([
        'external_id' => '999',
        'stage' => FixtureStage::Group,
        'group_code' => 'H',
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
        'kickoff_at' => $kickoff,
        'lock_at' => $kickoff->copy()->subHour(),
        'status' => FixtureStatus::Scheduled,
    ]);

    $this->get(route('bracket'))->assertInertia(fn (Assert $page) => $page
        ->where('focusNodeId', 'gH')
    );
});
