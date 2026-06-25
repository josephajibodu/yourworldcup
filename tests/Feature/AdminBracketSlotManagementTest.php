<?php

use App\Enums\BracketSlotSide;
use App\Enums\BracketSlotType;
use App\Models\BracketSlot;
use App\Models\Fixture;
use App\Models\Team;
use App\Models\User;
use App\Predictions\Importing\WorldCupImporter;
use Database\Seeders\PredictionMarketSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(PredictionMarketSeeder::class);
    WorldCupImporter::fromDefaultPath()->import();
});

test('guests are redirected from admin bracket slots index', function () {
    $this->get(route('admin.bracket-slots.index'))
        ->assertRedirect(route('login'));
});

test('non-admin users cannot access admin bracket slots', function () {
    $user = User::factory()->create();
    $slot = BracketSlot::query()->firstOrFail();

    $this->actingAs($user)
        ->get(route('admin.bracket-slots.index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->patch(route('admin.bracket-slots.update', $slot), [
            'team_id' => Team::query()->firstOrFail()->id,
        ])
        ->assertForbidden();
});

test('site admins can view the bracket slots index', function () {
    $this->actingAs(siteAdmin())
        ->get(route('admin.bracket-slots.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/bracket-slots/index')
            ->has('slots', 64)
            ->has('filters.type')
            ->has('filterOptions.types', 5)
        );
});

test('group winner slots only offer teams from the required group', function () {
    $admin = siteAdmin();

    $slot = BracketSlot::query()
        ->where('slot_type', BracketSlotType::GroupWinner)
        ->where('slot_spec->group', 'F')
        ->firstOrFail();

    $groupFTeams = Team::query()->where('group_code', 'F')->pluck('id')->all();
    $otherTeam = Team::query()->where('group_code', '!=', 'F')->firstOrFail();

    $response = $this->actingAs($admin)
        ->get(route('admin.bracket-slots.index'));

    $formatted = collect($response->original->getData()['page']['props']['slots'])
        ->firstWhere('id', $slot->id);

    expect($formatted)->not->toBeNull()
        ->and(collect($formatted['eligibleTeams'])->pluck('id')->all())
        ->toEqualCanonicalizing($groupFTeams);

    $this->actingAs($admin)
        ->patch(route('admin.bracket-slots.update', $slot), [
            'team_id' => $otherTeam->id,
        ])
        ->assertSessionHasErrors('team_id');
});

test('best third slots only offer teams from allowed groups and not already assigned', function () {
    $slots = BracketSlot::query()
        ->where('slot_type', BracketSlotType::BestThird)
        ->get();

    $slot = $slots->first(fn (BracketSlot $candidate): bool => in_array('C', $candidate->slot_spec['groups'] ?? [], true));

    expect($slot)->not->toBeNull();

    $allowedGroups = $slot->slot_spec['groups'];
    $team = Team::query()->where('group_code', $allowedGroups[0])->firstOrFail();
    $otherSlot = $slots->first(fn (BracketSlot $candidate): bool => $candidate->isNot($slot));

    $otherSlot->update(['resolved_team_id' => $team->id]);

    $response = $this->actingAs(siteAdmin())
        ->get(route('admin.bracket-slots.index'));

    $formatted = collect($response->original->getData()['page']['props']['slots'])
        ->firstWhere('id', $otherSlot->id);

    expect(collect($formatted['eligibleTeams'])->pluck('id'))
        ->not->toContain($team->id);
});

test('site admins can assign a team to a bracket slot and update the feeder fixture', function () {
    $slot = BracketSlot::query()
        ->where('slot_type', BracketSlotType::GroupRunnerUp)
        ->where('slot_spec->group', 'I')
        ->firstOrFail();

    $team = Team::query()->where('group_code', 'I')->orderBy('name')->firstOrFail();
    $fixture = Fixture::query()->findOrFail($slot->feeds_fixture_id);

    $this->actingAs(siteAdmin())
        ->patch(route('admin.bracket-slots.update', $slot), [
            'team_id' => $team->id,
        ])
        ->assertRedirect(route('admin.bracket-slots.index'));

    $slot->refresh();
    $fixture->refresh();

    expect($slot->resolved_team_id)->toBe($team->id);

    $column = $slot->side === BracketSlotSide::Home ? 'home_team_id' : 'away_team_id';

    expect($fixture->{$column})->toBe($team->id);
});

test('site admins can clear a bracket slot assignment', function () {
    $slot = BracketSlot::query()
        ->where('slot_type', BracketSlotType::GroupWinner)
        ->where('slot_spec->group', 'A')
        ->firstOrFail();

    $team = Team::query()->where('group_code', 'A')->firstOrFail();
    $fixture = Fixture::query()->findOrFail($slot->feeds_fixture_id);
    $column = $slot->side === BracketSlotSide::Home ? 'home_team_id' : 'away_team_id';

    $slot->update(['resolved_team_id' => $team->id]);
    $fixture->update([$column => $team->id]);

    $this->actingAs(siteAdmin())
        ->patch(route('admin.bracket-slots.update', $slot), [
            'team_id' => null,
        ])
        ->assertRedirect(route('admin.bracket-slots.index'));

    $slot->refresh();
    $fixture->refresh();

    expect($slot->resolved_team_id)->toBeNull()
        ->and($fixture->{$column})->toBeNull();
});
