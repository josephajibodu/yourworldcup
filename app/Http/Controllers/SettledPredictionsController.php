<?php

namespace App\Http\Controllers;

use App\Fixtures\FixtureScorePresenter;
use App\Models\Fixture;
use App\Models\Prediction;
use App\Models\Referral;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettledPredictionsController extends Controller
{
    public function __construct(private FixtureScorePresenter $scores) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        $summary = [
            'predictionPoints' => (int) Prediction::query()
                ->whereBelongsTo($user)
                ->whereNotNull('scored_at')
                ->sum('points_awarded'),
            'referralPoints' => (int) Referral::query()
                ->where('referrer_id', $user->id)
                ->sum('points'),
        ];

        $fixtures = Fixture::query()
            ->select('fixtures.*')
            ->whereHas('markets.predictions', fn ($query) => $query
                ->where('user_id', $user->id)
                ->whereNotNull('scored_at'))
            ->selectSub(
                Prediction::query()
                    ->selectRaw('MAX(predictions.scored_at)')
                    ->join('fixture_markets', 'fixture_markets.id', '=', 'predictions.fixture_market_id')
                    ->whereColumn('fixture_markets.fixture_id', 'fixtures.id')
                    ->where('predictions.user_id', $user->id)
                    ->whereNotNull('predictions.scored_at'),
                'latest_scored_at',
            )
            ->with([
                'homeTeam:id,name',
                'awayTeam:id,name',
                'markets' => fn ($query) => $query
                    ->whereHas('predictions', fn ($predictionQuery) => $predictionQuery
                        ->where('user_id', $user->id)
                        ->whereNotNull('scored_at'))
                    ->with([
                        'market:id,name,sort_order',
                        'predictions' => fn ($predictionQuery) => $predictionQuery
                            ->where('user_id', $user->id)
                            ->whereNotNull('scored_at'),
                    ]),
            ])
            ->orderByDesc('kickoff_at')
            ->orderByDesc('latest_scored_at')
            ->orderByDesc('fixtures.id')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Fixture $fixture): array => $this->formatFixture($fixture));

        return Inertia::render('predict/history', [
            'summary' => $summary,
            'fixtures' => $fixtures,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatFixture(Fixture $fixture): array
    {
        $predictions = $fixture->markets
            ->sortBy(fn ($market) => $market->market->sort_order)
            ->flatMap(fn ($market) => $market->predictions->map(
                fn (Prediction $prediction): array => $this->formatPrediction($prediction, $market->market->name),
            ))
            ->values()
            ->all();

        return [
            'id' => $fixture->id,
            'stageLabel' => $fixture->stage->label(),
            'kickoffAt' => $fixture->kickoff_at->toIso8601String(),
            'homeTeam' => $fixture->homeTeam?->name,
            'awayTeam' => $fixture->awayTeam?->name,
            'totalPoints' => collect($predictions)->sum('pointsAwarded'),
            ...$this->scores->present($fixture, onlyWhenVisible: $fixture->status->isComplete()),
            'predictions' => $predictions,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatPrediction(Prediction $prediction, string $marketName): array
    {
        return [
            'id' => $prediction->id,
            'isBanker' => $prediction->is_banker,
            'outcome' => $prediction->points_awarded > 0 ? 'won' : 'lost',
            'pointsAwarded' => $prediction->points_awarded,
            'marketName' => $marketName,
            'value' => $prediction->value,
        ];
    }
}
