<?php

namespace App\Fixtures;

use App\Enums\FixtureStatus;
use App\Enums\ResultDuration;
use App\Models\Fixture;

class FixtureScorePresenter
{
    /**
     * @return array<string, mixed>
     */
    public function present(Fixture $fixture, bool $onlyWhenVisible = true): array
    {
        if ($onlyWhenVisible && ! $this->scoresAreVisible($fixture)) {
            return $this->emptyScores();
        }

        return [
            'homeScore' => $fixture->home_score,
            'awayScore' => $fixture->away_score,
            'extraTimeHome' => $fixture->extra_time_home,
            'extraTimeAway' => $fixture->extra_time_away,
            'penaltiesHome' => $fixture->penalties_home,
            'penaltiesAway' => $fixture->penalties_away,
            'resultDuration' => $fixture->result_duration?->value,
            'lastGoal' => $fixture->last_goal?->value,
            'highestBooking' => $fixture->highest_booking?->value,
            'scoreLabel' => $this->scoreLabel($fixture),
        ];
    }

    public function scoresAreVisible(Fixture $fixture): bool
    {
        return in_array($fixture->status, [FixtureStatus::Final, FixtureStatus::Live], true)
            && $fixture->home_score !== null
            && $fixture->away_score !== null;
    }

    public function scoreLabel(Fixture $fixture): ?string
    {
        if ($fixture->status !== FixtureStatus::Final) {
            return null;
        }

        return ($fixture->result_duration ?? ResultDuration::Regular)->statusLabel();
    }

    /**
     * @return array<string, null>
     */
    private function emptyScores(): array
    {
        return [
            'homeScore' => null,
            'awayScore' => null,
            'extraTimeHome' => null,
            'extraTimeAway' => null,
            'penaltiesHome' => null,
            'penaltiesAway' => null,
            'resultDuration' => null,
            'lastGoal' => null,
            'highestBooking' => null,
            'scoreLabel' => null,
        ];
    }
}
