<?php

use App\Enums\BracketSlotSide;
use App\Enums\BracketSlotType;
use App\Enums\FixtureStage;
use App\Enums\FixtureStatus;
use App\Models\BracketSlot;
use App\Models\Fixture;
use App\Models\Prediction;
use App\Models\Stadium;
use App\Models\Team;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected from admin fixtures index', function () {
    $this->get(route('admin.fixtures.index'))
        ->assertRedirect(route('login'));
});

test('non-admin users cannot access admin fixtures', function () {
    $user = User::factory()->create();
    $fixture = Fixture::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.fixtures.index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->patch(route('admin.fixtures.update', $fixture), [
            'status' => FixtureStatus::Final->value,
            'home_score' => 1,
            'away_score' => 0,
        ])
        ->assertForbidden();
});

test('site admins can view the fixtures index', function () {
    Fixture::factory()->count(2)->create();

    $this->actingAs(siteAdmin())
        ->get(route('admin.fixtures.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/fixtures/index')
            ->has('fixtures.data', 2)
            ->has('filters.date')
            ->has('filterOptions.teams')
            ->has('filterOptions.stadiums')
            ->has('filterOptions.referees')
        );
});

test('site admins can filter fixtures by date, team, stadium, and referee', function () {
    $stadium = Stadium::factory()->create(['name' => 'Azteca']);
    $home = Team::factory()->create(['name' => 'Mexico']);
    $away = Team::factory()->create(['name' => 'Spain']);
    ['fixture' => $target] = predictableFixture(1);

    $target->update([
        'stadium_id' => $stadium->id,
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
        'referee' => 'Wilton Sampaio',
    ]);

    Fixture::factory()->create([
        'home_team_id' => Team::factory()->create()->id,
        'away_team_id' => Team::factory()->create()->id,
        'referee' => 'Other Ref',
    ]);

    $this->actingAs(siteAdmin())
        ->get(route('admin.fixtures.index', [
            'date' => $target->watDate(),
            'team' => $home->id,
            'stadium' => $stadium->id,
            'referee' => 'Wilton Sampaio',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('fixtures.data', 1)
            ->where('fixtures.data.0.id', $target->id)
            ->where('filters.date', $target->watDate())
            ->where('filters.team', $home->id)
            ->where('filters.stadium', $stadium->id)
            ->where('filters.referee', 'Wilton Sampaio')
        );
});

test('site admins can update a fixture score without settling predictions', function () {
    ['fixture' => $fixture, 'winner' => $winner] = finalFixtureWithMarkets(2, 1);
    $user = User::factory()->create();
    Prediction::factory()->for($user)->create([
        'fixture_market_id' => $winner->id,
        'value' => ['selected' => 'home'],
    ]);

    $this->actingAs(siteAdmin())
        ->patch(route('admin.fixtures.update', $fixture), [
            'status' => FixtureStatus::Final->value,
            'home_score' => 2,
            'away_score' => 1,
            'settle' => false,
        ])
        ->assertRedirect(route('admin.fixtures.index'));

    $fixture->refresh();

    expect($fixture->status)->toBe(FixtureStatus::Final)
        ->and($fixture->home_score)->toBe(2)
        ->and($fixture->away_score)->toBe(1)
        ->and(Prediction::query()->sole()->points_awarded)->toBeNull();
});

test('site admins can update a fixture score and settle predictions', function () {
    ['fixture' => $fixture, 'winner' => $winner] = finalFixtureWithMarkets(2, 1);
    $user = User::factory()->create();
    Prediction::factory()->for($user)->create([
        'fixture_market_id' => $winner->id,
        'value' => ['selected' => 'home'],
    ]);

    $this->actingAs(siteAdmin())
        ->patch(route('admin.fixtures.update', $fixture), [
            'status' => FixtureStatus::Final->value,
            'home_score' => 2,
            'away_score' => 1,
            'settle' => true,
        ])
        ->assertRedirect(route('admin.fixtures.index'));

    expect(Prediction::query()->sole()->points_awarded)->toBe(1);
});

test('site admins can void a fixture and settle predictions', function () {
    ['fixture' => $fixture, 'winner' => $winner] = finalFixtureWithMarkets(1, 0);
    $user = User::factory()->create();
    Prediction::factory()->for($user)->create([
        'fixture_market_id' => $winner->id,
        'value' => ['selected' => 'home'],
    ]);

    $this->actingAs(siteAdmin())
        ->patch(route('admin.fixtures.update', $fixture), [
            'status' => FixtureStatus::Void->value,
            'settle' => true,
        ])
        ->assertRedirect(route('admin.fixtures.index'));

    $fixture->refresh();

    expect($fixture->status)->toBe(FixtureStatus::Void)
        ->and(Prediction::query()->sole()->points_awarded)->toBe(0);
});

test('site admins see bracket slot labels for unassigned knockout teams', function () {
    $fixture = Fixture::factory()->create([
        'external_id' => '73',
        'stage' => FixtureStage::Round32,
        'home_team_id' => null,
        'away_team_id' => null,
    ]);

    BracketSlot::query()->create([
        'feeds_fixture_id' => $fixture->id,
        'side' => BracketSlotSide::Home,
        'slot_type' => BracketSlotType::GroupRunnerUp,
        'slot_spec' => ['group' => 'A'],
        'label' => 'Runner-up Group A',
    ]);

    BracketSlot::query()->create([
        'feeds_fixture_id' => $fixture->id,
        'side' => BracketSlotSide::Away,
        'slot_type' => BracketSlotType::GroupRunnerUp,
        'slot_spec' => ['group' => 'B'],
        'label' => 'Runner-up Group B',
    ]);

    $this->actingAs(siteAdmin())
        ->get(route('admin.fixtures.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('fixtures.data', function ($rows) use ($fixture): bool {
                $row = collect($rows)->firstWhere('id', $fixture->id);

                return $row !== null
                    && $row['homeTeam'] === 'Runner-up Group A'
                    && $row['awayTeam'] === 'Runner-up Group B';
            })
        );
});

test('site admins can mark a fixture live', function () {
    $fixture = Fixture::factory()->create(['status' => FixtureStatus::Scheduled]);

    $this->actingAs(siteAdmin())
        ->patch(route('admin.fixtures.update', $fixture), [
            'status' => FixtureStatus::Live->value,
            'settle' => false,
        ])
        ->assertRedirect(route('admin.fixtures.index'));

    expect($fixture->fresh()->status)->toBe(FixtureStatus::Live);
});

test('site admins can record regular time, extra time, and penalty scores', function () {
    $fixture = Fixture::factory()->create([
        'home_team_id' => Team::factory()->create()->id,
        'away_team_id' => Team::factory()->create()->id,
    ]);

    $this->actingAs(siteAdmin())
        ->patch(route('admin.fixtures.update', $fixture), [
            'status' => FixtureStatus::Final->value,
            'home_score' => 1,
            'away_score' => 1,
            'extra_time_home' => 0,
            'extra_time_away' => 0,
            'penalties_home' => 2,
            'penalties_away' => 3,
            'result_duration' => 'penalties',
            'settle' => false,
        ])
        ->assertRedirect(route('admin.fixtures.index'));

    $fixture->refresh();

    expect($fixture->home_score)->toBe(1)
        ->and($fixture->away_score)->toBe(1)
        ->and($fixture->penalties_home)->toBe(2)
        ->and($fixture->penalties_away)->toBe(3)
        ->and($fixture->winner_team_id)->toBe($fixture->away_team_id);
});
