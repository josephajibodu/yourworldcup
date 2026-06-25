<?php

namespace App\Bracket;

use App\Cache\TournamentCache;
use App\Enums\BracketSlotSide;
use App\Models\BracketSlot;
use App\Models\Team;
use Illuminate\Support\Facades\DB;

class BracketSlotAdminAssigner
{
    public function __construct(
        private BracketSlotEligibility $eligibility,
        private TournamentCache $cache,
    ) {}

    public function assign(BracketSlot $slot, ?Team $team): void
    {
        if ($team !== null && ! $this->eligibility->isEligible($slot, $team)) {
            throw new \InvalidArgumentException('Team is not eligible for this bracket slot.');
        }

        DB::transaction(function () use ($slot, $team): void {
            $slot->update(['resolved_team_id' => $team?->id]);

            $feedsFixture = $slot->feedsFixture;

            if ($feedsFixture === null) {
                return;
            }

            $column = $slot->side === BracketSlotSide::Home ? 'home_team_id' : 'away_team_id';
            $feedsFixture->update([$column => $team?->id]);
        });

        $this->cache->bump('bracket');
    }
}
