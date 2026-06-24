<?php

namespace App\Http\Controllers;

use App\Cache\TournamentCache;
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
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

class PredictController extends Controller
{
    public function __construct(private TournamentCache $cache) {}

    public function index(Request $request, PredictionVisibility $visibility): Response
    {
        $dates = $this->predictableDates($visibility);
        $selected = $visibility->selectedDate($request->query('date'), $dates);

        return Inertia::render('predict', [
            'dates' => $dates,
            'selectedDate' => $selected,
            'fixtures' => $selected === null ? [] : $this->fixturesForDay($request, $selected),
            'canResetPassword' => Features::enabled(Features::resetPasswords()),
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]);
    }

    public function rememberReturnUrl(Request $request): HttpResponse
    {
        $request->validate([
            'return_url' => ['required', 'string', 'starts_with:/predict'],
        ]);

        $request->session()->put('url.intended', $request->input('return_url'));
        $request->session()->put('predict_auth_flow', true);

        return response()->noContent();
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
        $dates = $this->cache->remember(
            'predict',
            'dates',
            fn (): array => Fixture::query()
                ->whereNotNull('home_team_id')
                ->whereNotNull('away_team_id')
                ->orderBy('kickoff_at')
                ->get(['kickoff_at'])
                ->map(fn (Fixture $fixture): string => $fixture->watDate())
                ->unique()
                ->values()
                ->all(),
        );

        return $visibility->filterVisibleDates($dates);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fixturesForDay(Request $request, string $watDate): array
    {
        $shared = $this->cache->remember(
            'predict',
            "day:{$watDate}",
            fn (): array => $this->sharedFixturesForDay($watDate),
        );

        $marketIds = collect($shared)
            ->flatMap(fn (array $fixture): array => array_column($fixture['markets'], 'id'))
            ->all();

        $predictions = $this->userPredictions($request, $marketIds);

        return collect($shared)
            ->map(fn (array $fixture): array => $this->refreshMatchWinnerOptionLabels($fixture))
            ->map(fn (array $fixture): array => $this->mergeFixturePredictions($fixture, $predictions))
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function sharedFixturesForDay(string $watDate): array
    {
        $start = Carbon::parse($watDate, config('predictions.timezone'))->startOfDay();
        $window = [$start->copy()->utc(), $start->copy()->addDay()->utc()];

        return Fixture::query()
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
            ->get()
            ->map(fn (Fixture $fixture): array => $this->presentFixture($fixture))
            ->all();
    }

    /**
     * @param  array<int, int>  $marketIds
     * @return Collection<int, Prediction>
     */
    private function userPredictions(Request $request, array $marketIds): Collection
    {
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
     * @param  array<string, mixed>  $fixture
     * @param  Collection<int, Prediction>  $predictions
     * @return array<string, mixed>
     */
    private function mergeFixturePredictions(array $fixture, Collection $predictions): array
    {
        $fixture['markets'] = collect($fixture['markets'])
            ->map(function (array $market) use ($predictions): array {
                $prediction = $predictions->get($market['id']);

                $market['value'] = $prediction?->value;
                $market['isBanker'] = (bool) $prediction?->is_banker;

                return $market;
            })
            ->all();

        return $fixture;
    }

    /**
     * @return array<string, mixed>
     */
    private function presentFixture(Fixture $fixture): array
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
                ->map(fn (FixtureMarket $market): array => $this->presentMarket($market, $fixture))
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentMarket(FixtureMarket $market, Fixture $fixture): array
    {
        return [
            'id' => $market->id,
            'key' => $market->market->key,
            'name' => $market->market->name,
            'description' => $market->market->description,
            'inputType' => $market->market->input_type->value,
            'options' => $this->options($market),
            'points' => $market->effectiveBasePoints(),
            'locked' => ! $market->isOpenForPrediction(),
        ];
    }

    /**
     * Display options for a single-select market, with the match-winner market
     * labelled as Home, Draw, and Away.
     *
     * @return array<int, array{value: string, label: string}>|null
     */
    private function options(FixtureMarket $market): ?array
    {
        $options = $market->resolvedOptions();

        if ($options === null) {
            return null;
        }

        if ($market->market->key === 'match_winner') {
            return $this->relabelMatchWinnerOptions($options);
        }

        return array_map(
            static fn (array $option): array => [
                'value' => (string) $option['value'],
                'label' => (string) ($option['label'] ?? $option['value']),
            ],
            $options,
        );
    }

    /**
     * Cached predict payloads may still carry old team-name labels; always
     * normalize match-winner options when serving a day.
     *
     * @param  array<string, mixed>  $fixture
     * @return array<string, mixed>
     */
    private function refreshMatchWinnerOptionLabels(array $fixture): array
    {
        $fixture['markets'] = collect($fixture['markets'] ?? [])
            ->map(function (array $market): array {
                if (($market['key'] ?? '') !== 'match_winner' || ! is_array($market['options'] ?? null)) {
                    return $market;
                }

                $market['options'] = $this->relabelMatchWinnerOptions($market['options']);

                return $market;
            })
            ->all();

        return $fixture;
    }

    /**
     * @param  array<int, array{value: string, label?: string}>  $options
     * @return array<int, array{value: string, label: string}>
     */
    private function relabelMatchWinnerOptions(array $options): array
    {
        $labels = [
            'home' => 'Home',
            'draw' => 'Draw',
            'away' => 'Away',
        ];

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
