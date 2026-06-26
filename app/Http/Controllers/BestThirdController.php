<?php

namespace App\Http\Controllers;

use App\Bracket\Contracts\BestThirdQualifier;
use App\Bracket\GroupStandingsService;
use Inertia\Inertia;
use Inertia\Response;

class BestThirdController extends Controller
{
    public function __construct(
        private GroupStandingsService $standings,
        private BestThirdQualifier $bestThirdQualifier,
    ) {}

    public function index(): Response
    {
        $rankings = $this->bestThirdQualifier
            ->rankAll($this->standings->provisionalThirdPlaceTeams())
            ->map(fn (array $entry): array => [
                'rank' => $entry['rank'],
                'qualifies' => $entry['qualifies'],
                'groupCode' => $entry['team']->group_code,
                'groupComplete' => $entry['groupComplete'],
                'matchesLeft' => $entry['matchesLeft'],
                'team' => [
                    'id' => $entry['team']->id,
                    'name' => $entry['team']->name,
                    'code' => $entry['team']->code,
                    'flag' => $entry['team']->flag,
                ],
                'played' => $entry['row']['played'],
                'won' => $entry['row']['won'],
                'drawn' => $entry['row']['drawn'],
                'lost' => $entry['row']['lost'],
                'gf' => $entry['row']['gf'],
                'ga' => $entry['row']['ga'],
                'gd' => $entry['row']['gd'],
                'points' => $entry['row']['points'],
            ])
            ->values()
            ->all();

        return Inertia::render('best-thirds', [
            'rankings' => $rankings,
            'allGroupsComplete' => $this->standings->allGroupsComplete(),
        ]);
    }
}
