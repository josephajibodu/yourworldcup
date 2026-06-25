<?php

namespace App\Fixtures;

use App\Enums\BracketSlotSide;
use App\Enums\FixtureStage;
use App\Models\BracketSlot;
use App\Models\Fixture;
use App\Models\Team;

class FixtureParticipantPresenter
{
    public function name(Fixture $fixture, BracketSlotSide $side): string
    {
        $team = $side === BracketSlotSide::Home ? $fixture->homeTeam : $fixture->awayTeam;

        if ($team instanceof Team) {
            return $team->name;
        }

        $bracketSlot = $fixture->bracketSlots->first(
            fn (BracketSlot $slot): bool => $slot->side === $side,
        );

        if ($bracketSlot?->label !== null && $bracketSlot->label !== '') {
            return $bracketSlot->label;
        }

        if ($bracketSlot !== null) {
            return $bracketSlot->displayCode();
        }

        $feederLabel = $this->feederLabel($fixture, $side);

        return $feederLabel ?? 'TBD';
    }

    private function feederLabel(Fixture $fixture, BracketSlotSide $side): ?string
    {
        if ($fixture->external_id === null) {
            return null;
        }

        /** @var array<int, array{0: int, 1: int}> $feeders */
        $feeders = config('bracket.feeders', []);
        $matchId = (int) $fixture->external_id;
        $pair = $feeders[$matchId] ?? null;

        if ($pair === null) {
            return null;
        }

        $feederId = $side === BracketSlotSide::Home ? $pair[0] : $pair[1];
        $prefix = $fixture->stage === FixtureStage::ThirdPlace ? 'RU' : 'W';

        return $prefix.$feederId;
    }
}
