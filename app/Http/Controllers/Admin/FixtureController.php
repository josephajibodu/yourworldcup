<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BracketSlotSide;
use App\Enums\FixtureStatus;
use App\Enums\MarketStatus;
use App\Fixtures\FixtureAdminUpdater;
use App\Fixtures\FixtureParticipantPresenter;
use App\Fixtures\FixtureScorePresenter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminFixtureUpdateRequest;
use App\Models\Fixture;
use App\Models\Stadium;
use App\Models\Team;
use App\Predictions\Markets\SpecialPlayerMarkets;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class FixtureController extends Controller
{
    public function __construct(
        private FixtureParticipantPresenter $participants,
        private FixtureScorePresenter $scores,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('admin/fixtures/index', $this->pageProps($request));
    }

    public function update(
        AdminFixtureUpdateRequest $request,
        Fixture $fixture,
        FixtureAdminUpdater $updater,
    ): RedirectResponse {
        $validated = $request->validated();
        $settled = $updater->update(
            $fixture,
            FixtureStatus::from($validated['status']),
            $request->toFixtureResult(),
            $request->shouldSettle(),
        );

        $message = $settled > 0
            ? __('Fixture updated and :count predictions settled.', ['count' => $settled])
            : __('Fixture updated.');

        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return to_route('admin.fixtures.index', $request->only([
            'date',
            'team',
            'stadium',
            'referee',
            'page',
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    private function pageProps(Request $request): array
    {
        $date = $request->string('date')->trim()->toString();
        $team = $request->integer('team') ?: null;
        $stadium = $request->integer('stadium') ?: null;
        $referee = $request->string('referee')->trim()->toString();

        $fixtures = Fixture::query()
            ->with([
                'homeTeam:id,name',
                'awayTeam:id,name',
                'stadium:id,name,city',
                'bracketSlots',
            ])
            ->withCount([
                'markets',
                'markets as settled_markets_count' => fn ($query) => $query->where('status', MarketStatus::Settled),
            ])
            ->when($date !== '', fn ($query) => $query->onWatDate($date))
            ->when($team !== null, function ($query) use ($team): void {
                $query->where(function ($query) use ($team): void {
                    $query->where('home_team_id', $team)
                        ->orWhere('away_team_id', $team);
                });
            })
            ->when($stadium !== null, fn ($query) => $query->where('stadium_id', $stadium))
            ->when($referee !== '', fn ($query) => $query->where('referee', $referee))
            ->orderBy('kickoff_at')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (Fixture $fixture): array => $this->formatFixture($fixture));

        return [
            'fixtures' => $fixtures,
            'filters' => [
                'date' => $date,
                'team' => $team,
                'stadium' => $stadium,
                'referee' => $referee,
            ],
            'filterOptions' => [
                'dates' => $this->tournamentDates(),
                'teams' => Team::query()->orderBy('name')->get(['id', 'name']),
                'stadiums' => Stadium::query()->orderBy('name')->get(['id', 'name', 'city']),
                'referees' => Fixture::query()
                    ->whereNotNull('referee')
                    ->distinct()
                    ->orderBy('referee')
                    ->pluck('referee'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatFixture(Fixture $fixture): array
    {
        $data = [
            'id' => $fixture->id,
            'externalId' => $fixture->external_id,
            'stageLabel' => $fixture->stage->label(),
            'groupCode' => $fixture->group_code,
            'status' => $fixture->status->value,
            'kickoffAt' => $fixture->kickoff_at->toISOString(),
            'watDate' => $fixture->watDate(),
            'homeTeam' => $this->participants->name($fixture, BracketSlotSide::Home),
            'awayTeam' => $this->participants->name($fixture, BracketSlotSide::Away),
            'stadium' => $fixture->stadium === null
                ? null
                : [
                    'id' => $fixture->stadium->id,
                    'name' => $fixture->stadium->name,
                    'city' => $fixture->stadium->city,
                ],
            'referee' => $fixture->referee,
            ...$this->scores->present($fixture, onlyWhenVisible: false),
            'marketsCount' => $fixture->markets_count,
            'settledMarketsCount' => $fixture->settled_markets_count,
        ];

        $playerMarkets = SpecialPlayerMarkets::definitionsForMatch($fixture->external_id);

        if ($playerMarkets !== []) {
            $data['playerOutcomes'] = $fixture->player_outcomes ?? [];
            $data['playerMarkets'] = collect($playerMarkets)
                ->map(fn (array $definition): array => [
                    'key' => $definition['key'],
                    'name' => $definition['name'],
                    'description' => $definition['description'],
                ])
                ->values()
                ->all();
        }

        return $data;
    }

    /**
     * @return array<int, string>
     */
    private function tournamentDates(): array
    {
        $timezone = config('predictions.timezone');
        $start = Carbon::parse(config('predictions.tournament_start'), $timezone)->startOfDay();
        $end = Carbon::parse(config('predictions.tournament_end'), $timezone)->startOfDay();

        $dates = [];
        $day = $start->copy();

        while ($day->lte($end)) {
            $dates[] = $day->toDateString();
            $day->addDay();
        }

        return $dates;
    }
}
