<?php

namespace App\Fixtures;

use App\Enums\HighestBookingOutcome;
use App\Enums\LastGoalOutcome;
use App\Enums\ResultDuration;
use App\Models\Fixture;

final class FixtureResult
{
    public function __construct(
        public int $homeScore,
        public int $awayScore,
        public ?int $winnerTeamId = null,
        public ?int $extraTimeHome = null,
        public ?int $extraTimeAway = null,
        public ?int $penaltiesHome = null,
        public ?int $penaltiesAway = null,
        public ?ResultDuration $resultDuration = null,
        public ?LastGoalOutcome $lastGoal = null,
        public ?HighestBookingOutcome $highestBooking = null,
    ) {}

    public function matchesFixture(Fixture $fixture): bool
    {
        return $fixture->home_score === $this->homeScore
            && $fixture->away_score === $this->awayScore
            && $fixture->extra_time_home === $this->extraTimeHome
            && $fixture->extra_time_away === $this->extraTimeAway
            && $fixture->penalties_home === $this->penaltiesHome
            && $fixture->penalties_away === $this->penaltiesAway
            && $fixture->winner_team_id === $this->winnerTeamId
            && $fixture->result_duration === $this->resultDuration
            && $fixture->last_goal === $this->lastGoal
            && $fixture->highest_booking === $this->highestBooking;
    }

    /**
     * @return array<string, int|string|null>
     */
    public function toAttributes(): array
    {
        return [
            'home_score' => $this->homeScore,
            'away_score' => $this->awayScore,
            'extra_time_home' => $this->extraTimeHome,
            'extra_time_away' => $this->extraTimeAway,
            'penalties_home' => $this->penaltiesHome,
            'penalties_away' => $this->penaltiesAway,
            'winner_team_id' => $this->winnerTeamId,
            'result_duration' => $this->resultDuration?->value,
            'last_goal' => $this->lastGoal?->value,
            'highest_booking' => $this->highestBooking?->value,
        ];
    }
}
