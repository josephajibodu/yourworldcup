<?php

use App\Bracket\PointsBestThirdQualifier;
use App\Enums\FixtureStage;
use App\Enums\FixtureStatus;
use App\Models\Fixture;
use App\Models\Team;
use App\Predictions\Importing\WorldCupImporter;
use Database\Seeders\PredictionMarketSeeder;

beforeEach(function () {
    $this->seed(PredictionMarketSeeder::class);
    WorldCupImporter::fromDefaultPath()->import();
});

it('ranks third-place teams by points, goal difference, then goals scored', function () {
    $qualifier = new PointsBestThirdQualifier;

    $entry = fn (string $name, string $group, int $points, int $gd, int $gf): array => [
        'team' => Team::make(['name' => $name, 'group_code' => $group]),
        'row' => ['points' => $points, 'gd' => $gd, 'gf' => $gf],
        'groupComplete' => true,
    ];

    $ranked = $qualifier->rankAll(collect([
        $entry('Third A', 'A', 3, 0, 2),
        $entry('Third B', 'B', 6, 2, 5),
        $entry('Third C', 'C', 4, 1, 3),
        $entry('Third D', 'D', 4, 1, 4),
        $entry('Third E', 'E', 2, -1, 1),
        $entry('Third F', 'F', 1, -2, 0),
        $entry('Third G', 'G', 1, -2, 0),
        $entry('Third H', 'H', 0, -3, 0),
        $entry('Third I', 'I', 0, -4, 0),
    ]));

    expect($ranked->pluck('team.name')->all())->toBe([
        'Third B',
        'Third D',
        'Third C',
        'Third A',
        'Third E',
        'Third F',
        'Third G',
        'Third H',
        'Third I',
    ])
        ->and($ranked->where('qualifies', true))->toHaveCount(8)
        ->and($ranked->last()['qualifies'])->toBeFalse();
});

it('displays the best third-place ranking page', function () {
    $this->get(route('best-thirds'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('best-thirds')
            ->has('rankings', 12)
            ->where('allGroupsComplete', false)
            ->where('rankings.0.rank', 1)
            ->has('rankings.0.team')
            ->has('rankings.0.points')
        );
});

it('includes matches left for teams in incomplete groups', function () {
    $this->get(route('best-thirds'))
        ->assertSuccessful()
        ->assertInertia(function ($page) {
            $rankings = collect($page->toArray()['props']['rankings']);

            expect($rankings->where('matchesLeft', '>', 0))->not->toBeEmpty()
                ->and($rankings->every(fn (array $row): bool => $row['matchesLeft'] === max(0, 3 - $row['played'])))->toBeTrue()
                ->and($rankings->where('groupComplete', true)->every(fn (array $row): bool => $row['matchesLeft'] === 0))->toBeTrue();
        });
});

it('passes open group fixtures for live team highlighting', function () {
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

    $this->get(route('best-thirds'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('openGroupFixtures')
            ->where('openGroupFixtures', fn ($fixtures): bool => collect($fixtures)->contains(
                fn (array $fixture): bool => $fixture['status'] === 'live'
                    && $fixture['homeTeamId'] === $home->id
                    && $fixture['awayTeamId'] === $away->id,
            )));
});

it('marks the top eight teams as qualifiers', function () {
    $this->get(route('best-thirds'))
        ->assertSuccessful()
        ->assertInertia(function ($page) {
            $rankings = collect($page->toArray()['props']['rankings']);

            expect($rankings->where('qualifies', true))->toHaveCount(8)
                ->and($rankings->where('qualifies', false))->toHaveCount(4);
        });
});
