<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Prediction;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserPredictionController extends Controller
{
    public function index(Request $request, User $user): Response
    {
        $predictions = Prediction::query()
            ->whereBelongsTo($user)
            ->with([
                'fixtureMarket:id,fixture_id,prediction_market_id,status',
                'fixtureMarket.market:id,name',
                'fixtureMarket.fixture:id,stage,status,home_team_id,away_team_id,home_score,away_score,kickoff_at',
                'fixtureMarket.fixture.homeTeam:id,name',
                'fixtureMarket.fixture.awayTeam:id,name',
            ])
            ->orderByDesc('submitted_at')
            ->paginate(50)
            ->withQueryString()
            ->through(fn (Prediction $prediction): array => $this->formatPrediction($prediction));

        return Inertia::render('admin/users/predictions', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'predictions' => $predictions,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatPrediction(Prediction $prediction): array
    {
        $fixture = $prediction->fixtureMarket->fixture;
        $isScored = $prediction->scored_at !== null;

        return [
            'id' => $prediction->id,
            'isBanker' => $prediction->is_banker,
            'submittedAt' => $prediction->submitted_at->toISOString(),
            'isScored' => $isScored,
            'outcome' => $isScored
                ? ($prediction->points_awarded > 0 ? 'won' : 'lost')
                : null,
            'pointsAwarded' => $prediction->points_awarded,
            'marketName' => $prediction->fixtureMarket->market->name,
            'value' => $prediction->value,
            'fixture' => [
                'id' => $fixture->id,
                'stageLabel' => $fixture->stage->label(),
                'status' => $fixture->status->value,
                'kickoffAt' => $fixture->kickoff_at->toISOString(),
                'homeTeam' => $fixture->homeTeam === null ? null : $fixture->homeTeam->name,
                'awayTeam' => $fixture->awayTeam === null ? null : $fixture->awayTeam->name,
                'homeScore' => $fixture->status->isComplete() ? $fixture->home_score : null,
                'awayScore' => $fixture->status->isComplete() ? $fixture->away_score : null,
            ],
        ];
    }
}
