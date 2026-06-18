<?php

use App\Bracket\BracketResolverService;
use App\Bracket\BracketSlotLabelParser;
use App\Enums\BracketSlotSide;
use App\Enums\BracketSlotType;
use App\Enums\FixtureStage;
use App\Enums\FixtureStatus;
use App\Jobs\ResolveBracketSlots;
use App\Models\BracketSlot;
use App\Models\Fixture;
use App\Models\Team;
use App\Predictions\Importing\WorldCupImporter;
use App\Predictions\Settlement\SettlementService;
use Database\Seeders\PredictionMarketSeeder;
use Illuminate\Support\Facades\Bus;

beforeEach(function () {
    $this->seed(PredictionMarketSeeder::class);
    WorldCupImporter::fromDefaultPath()->import();
});

it('imports two bracket slots for every knockout fixture', function () {
    expect(BracketSlot::count())->toBe(64);
});

it('parses group and best-third labels into slot specs', function () {
    $parser = app(BracketSlotLabelParser::class);

    expect($parser->parse('Winner Group E'))
        ->toMatchArray([
            'type' => BracketSlotType::GroupWinner,
            'spec' => ['group' => 'E'],
            'label' => 'Winner Group E',
        ]);

    expect($parser->parse('3rd Group D/E/I/J/L')['spec']['groups'])
        ->toBe(['D', 'E', 'I', 'J', 'L']);
});

it('stores round-of-32 slot codes on the bracket page', function () {
    $this->get(route('bracket'))
        ->assertInertia(fn ($page) => $page
            ->where('knockout.0.code', 'M73')
            ->where('knockout.0.home.label', '2A')
            ->where('knockout.0.away.label', '2B')
            ->where('knockout.1.away.label', '3rd A/B/C/D/F')
        );
});

it('resolves runner-up slots onto round-of-32 fixtures when groups finish', function () {
    finishGroup('A');
    finishGroup('B');

    $resolved = app(BracketResolverService::class)->resolveGroup('A')
        + app(BracketResolverService::class)->resolveGroup('B');

    expect($resolved)->toBeGreaterThan(0);

    $fixture = Fixture::query()->where('external_id', '73')->firstOrFail();

    expect($fixture->home_team_id)->not->toBeNull()
        ->and($fixture->away_team_id)->not->toBeNull();

    $homeSlot = BracketSlot::query()
        ->where('feeds_fixture_id', $fixture->id)
        ->where('side', BracketSlotSide::Home)
        ->firstOrFail();

    expect($homeSlot->resolved_team_id)->toBe($fixture->home_team_id)
        ->and($homeSlot->displayCode())->toBe('2A');
});

it('dispatches bracket resolution after a group fixture settles', function () {
    Bus::fake();

    $fixture = Fixture::query()
        ->where('stage', FixtureStage::Group)
        ->where('group_code', 'A')
        ->firstOrFail();

    $fixture->update([
        'status' => FixtureStatus::Final,
        'home_score' => 2,
        'away_score' => 1,
        'winner_team_id' => $fixture->home_team_id,
    ]);

    app(SettlementService::class)->settleFixture($fixture->fresh());

    Bus::assertDispatched(ResolveBracketSlots::class);
});

function finishGroup(string $groupCode): void
{
    $teams = Team::query()
        ->where('group_code', $groupCode)
        ->orderByExternalId()
        ->get()
        ->values();

    $fixtures = Fixture::query()
        ->where('stage', FixtureStage::Group)
        ->where('group_code', $groupCode)
        ->get();

    foreach ($fixtures as $index => $fixture) {
        $homeWins = $fixture->home_team_id === $teams[0]->id;

        $fixture->update([
            'status' => FixtureStatus::Final,
            'home_score' => $homeWins ? 2 : 0,
            'away_score' => $homeWins ? 0 : 2,
            'winner_team_id' => $homeWins ? $fixture->home_team_id : $fixture->away_team_id,
        ]);
    }
}
