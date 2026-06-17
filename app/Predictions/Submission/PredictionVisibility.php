<?php

namespace App\Predictions\Submission;

use Illuminate\Support\Carbon;

class PredictionVisibility
{
    public function timezone(): string
    {
        return (string) config('predictions.timezone');
    }

    public function today(): Carbon
    {
        return Carbon::now($this->timezone())->startOfDay();
    }

    /**
     * The last WAT calendar day users may browse or submit predictions for.
     */
    public function latestVisibleDate(): string
    {
        $daysAhead = (int) config('predictions.visible_days_ahead', 1);

        return $this->today()->copy()->addDays($daysAhead)->toDateString();
    }

    public function isDateVisible(string $watDate): bool
    {
        return $watDate <= $this->latestVisibleDate();
    }

    /**
     * @param  array<int, string>  $dates
     * @return array<int, string>
     */
    public function filterVisibleDates(array $dates): array
    {
        $latest = $this->latestVisibleDate();

        return array_values(array_filter(
            $dates,
            static fn (string $date): bool => $date <= $latest,
        ));
    }
}
