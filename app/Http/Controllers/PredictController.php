<?php

namespace App\Http\Controllers;

use App\Enums\FixtureStatus;
use App\Http\Requests\SubmitPredictionsRequest;
use App\Models\Fixture;
use App\Models\FixtureMarket;
use App\Models\Prediction;
use App\Models\Team;
use App\Predictions\Submission\PredictionService;
use App\Predictions\Submission\PredictionVisibility;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class PredictController extends Controller
{
    public function index(Request $request, PredictionVisibility $visibility): Response
    {
        $dates = $this->predictableDates($visibility);
        $selected = $this->selectedDate($request->query('date'), $dates);

        return Inertia::render('predict', [
            'dates' => $dates,
            'selectedDate' => $selected,
            'fixtures' => $selected === null ? [] : $this->fixturesForDay($request, $selected),
        ]);
    }

    public function store(SubmitPredictionsRequest $request, PredictionService $service): RedirectResponse
    {
        $service->submitDay(
            $request->user(),
            $request->validated('date'),
            $request->entries(),
            $request->bankerFixtureMarketId(),
        );

        return back()->with('status', 'Your picks are in.');
    }

    /**
     * Distinct WAT match days that have at least one predictable fixture
     * (both teams resolved), ordered chronologically.
     *
     * @return array<int, string>
     */
    private function predictableDates(PredictionVisibility $visibility): array
    {
        $dates = Fixture::query()
            ->whereNotNull('home_team_id')
            ->whereNotNull('away_team_id')
            ->orderBy('kickoff_at')
            ->get(['kickoff_at'])
            ->map(fn (Fixture $fixture): string => $fixture->watDate())
            ->unique()
            ->values()
            ->all();

        return $visibility->filterVisibleDates($dates);
    }

    /**
     * @param  array<int, string>  $dates
     */
    private function selectedDate(?string $requested, array $dates): ?string
    {
        if ($requested !== null && in_array($requested, $dates, true)) {
            return $requested;
        }

        $next = Fixture::query()
            ->whereNotNull('home_team_id')
            ->whereNotNull('away_team_id')
            ->where('lock_at', '>', now())
            ->orderBy('kickoff_at')
            ->get()
            ->first(fn (Fixture $fixture): bool => in_array($fixture->watDate(), $dates, true));

        if ($next !== null) {
            return $next->watDate();
        }

        return $dates === [] ? null : end($dates);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fixturesForDay(Request $request, string $watDate): array
    {
        $start = Carbon::parse($watDate, config('predictions.timezone'))->startOfDay();
        $window = [$start->copy()->utc(), $start->copy()->addDay()->utc()];

        $fixtures = Fixture::query()
            ->whereNotNull('home_team_id')
            ->whereNotNull('away_team_id')
            ->whereBetween('kickoff_at', $window)
            ->with([
                'homeTeam',
                'awayTeam',
                'stadium',
                'markets' => fn ($query) => $query->where('is_enabled', true)->with('market'),
            ])
            ->orderBy('kickoff_at')
            ->get();

        $predictions = $this->userPredictions($request, $fixtures);

        return $fixtures
            ->map(fn (Fixture $fixture): array => $this->presentFixture($fixture, $predictions))
            ->all();
    }

    /**
     * The current user's predictions on the day's markets, keyed by market id.
     *
     * @param  Collection<int, Fixture>  $fixtures
     * @return Collection<int, Prediction>
     */
    private function userPredictions(Request $request, Collection $fixtures): Collection
    {
        $marketIds = $fixtures
            ->flatMap(fn (Fixture $fixture): Collection => $fixture->markets)
            ->pluck('id')
            ->all();

        if ($request->user() === null || $marketIds === []) {
            return new Collection;
        }

        return Prediction::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('fixture_market_id', $marketIds)
            ->get()
            ->keyBy('fixture_market_id');
    }

    /**
     * @param  Collection<int, Prediction>  $predictions
     * @return array<string, mixed>
     */
    private function presentFixture(Fixture $fixture, Collection $predictions): array
    {
        return [
            'id' => $fixture->id,
            'stage' => $fixture->stage->value,
            'stageLabel' => $fixture->stage->label(),
            'group' => $fixture->group_code,
            'status' => $fixture->status->value,
            'kickoffAt' => $fixture->kickoff_at->toIso8601String(),
            'lockAt' => $fixture->lock_at->toIso8601String(),
            'locked' => $fixture->isLocked(),
            'stadium' => $fixture->stadium?->name,
            'city' => $fixture->stadium?->city,
            'home' => $this->team($fixture->homeTeam),
            'away' => $this->team($fixture->awayTeam),
            'homeScore' => $fixture->status === FixtureStatus::Final ? $fixture->home_score : null,
            'awayScore' => $fixture->status === FixtureStatus::Final ? $fixture->away_score : null,
            'markets' => $fixture->markets
                ->sortBy(fn (FixtureMarket $market): int => $market->market->sort_order)
                ->values()
                ->map(fn (FixtureMarket $market): array => $this->presentMarket($market, $fixture, $predictions->get($market->id)))
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentMarket(FixtureMarket $market, Fixture $fixture, ?Prediction $prediction): array
    {
        return [
            'id' => $market->id,
            'key' => $market->market->key,
            'name' => $market->market->name,
            'description' => $market->market->description,
            'inputType' => $market->market->input_type->value,
            'options' => $this->options($market, $fixture),
            'points' => $market->effectiveBasePoints(),
            'locked' => ! $market->isOpenForPrediction(),
            'value' => $prediction?->value,
            'isBanker' => (bool) $prediction?->is_banker,
        ];
    }

    /**
     * Display options for a single-select market, with the match-winner market
     * relabelled to the actual team names.
     *
     * @return array<int, array{value: string, label: string}>|null
     */
    private function options(FixtureMarket $market, Fixture $fixture): ?array
    {
        $options = $market->resolvedOptions();

        if ($options === null) {
            return null;
        }

        $labels = $market->market->key === 'match_winner'
            ? [
                'home' => $fixture->homeTeam->name,
                'draw' => 'Draw',
                'away' => $fixture->awayTeam->name,
            ]
            : [];

        return array_map(
            static fn (array $option): array => [
                'value' => (string) $option['value'],
                'label' => $labels[$option['value']] ?? (string) ($option['label'] ?? $option['value']),
            ],
            $options,
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private function team(?Team $team): ?array
    {
        if ($team === null) {
            return null;
        }

        return [
            'name' => $team->name,
            'code' => $team->code,
            'flag' => $team->flag,
        ];
    }
}
