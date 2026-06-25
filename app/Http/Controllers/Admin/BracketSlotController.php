<?php

namespace App\Http\Controllers\Admin;

use App\Bracket\BracketSlotAdminAssigner;
use App\Bracket\BracketSlotEligibility;
use App\Enums\BracketSlotType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminBracketSlotUpdateRequest;
use App\Models\BracketSlot;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class BracketSlotController extends Controller
{
    public function __construct(private BracketSlotEligibility $eligibility) {}

    public function index(Request $request): Response
    {
        $type = $request->string('type')->trim()->toString();
        $assignment = $request->string('assignment')->trim()->toString();

        $assignedTeamIds = $this->eligibility->assignedTeamIds();

        $slots = BracketSlot::query()
            ->with([
                'resolvedTeam:id,name,code,flag,group_code',
                'feedsFixture:id,external_id,stage,kickoff_at',
            ])
            ->whereNotNull('feeds_fixture_id')
            ->when($type !== '', fn ($query) => $query->where('slot_type', $type))
            ->when($assignment === 'assigned', fn ($query) => $query->whereNotNull('resolved_team_id'))
            ->when($assignment === 'unassigned', fn ($query) => $query->whereNull('resolved_team_id'))
            ->orderBy('feeds_fixture_id')
            ->orderBy('side')
            ->get()
            ->map(fn (BracketSlot $slot): array => $this->formatSlot($slot, $assignedTeamIds));

        return Inertia::render('admin/bracket-slots/index', [
            'slots' => $slots,
            'filters' => [
                'type' => $type,
                'assignment' => $assignment,
            ],
            'filterOptions' => [
                'types' => $this->slotTypeOptions(),
            ],
        ]);
    }

    public function update(
        AdminBracketSlotUpdateRequest $request,
        BracketSlot $bracketSlot,
        BracketSlotAdminAssigner $assigner,
    ): RedirectResponse {
        $assigner->assign($bracketSlot, $request->team());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Bracket slot updated.'),
        ]);

        return to_route('admin.bracket-slots.index', $request->only(['type', 'assignment']));
    }

    /**
     * @param  Collection<int, int>  $assignedTeamIds
     * @return array<string, mixed>
     */
    private function formatSlot(BracketSlot $slot, $assignedTeamIds): array
    {
        $eligibleTeams = $this->eligibility
            ->eligibleTeams($slot, $assignedTeamIds)
            ->map(fn (Team $team): array => $this->formatTeamOption($team));

        return [
            'id' => $slot->id,
            'label' => $slot->label,
            'displayCode' => $slot->displayCode(),
            'slotType' => $slot->slot_type->value,
            'side' => $slot->side->value,
            'feedsFixture' => $slot->feedsFixture === null
                ? null
                : [
                    'id' => $slot->feedsFixture->id,
                    'externalId' => $slot->feedsFixture->external_id,
                    'stageLabel' => $slot->feedsFixture->stage->label(),
                ],
            'resolvedTeam' => $slot->resolvedTeam === null
                ? null
                : [
                    'id' => $slot->resolvedTeam->id,
                    'name' => $slot->resolvedTeam->name,
                    'code' => $slot->resolvedTeam->code,
                    'flag' => $slot->resolvedTeam->flag,
                    'groupCode' => $slot->resolvedTeam->group_code,
                ],
            'eligibleTeams' => $eligibleTeams->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatTeamOption(Team $team): array
    {
        return [
            'id' => $team->id,
            'name' => $team->name,
            'code' => $team->code,
            'flag' => $team->flag,
            'groupCode' => $team->group_code,
            'standingPosition' => $this->eligibility->standingPosition($team),
        ];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function slotTypeOptions(): array
    {
        return [
            ['value' => BracketSlotType::GroupWinner->value, 'label' => 'Group winner'],
            ['value' => BracketSlotType::GroupRunnerUp->value, 'label' => 'Group runner-up'],
            ['value' => BracketSlotType::BestThird->value, 'label' => 'Best third'],
            ['value' => BracketSlotType::KnockoutWinner->value, 'label' => 'Knockout winner'],
            ['value' => BracketSlotType::KnockoutLoser->value, 'label' => 'Knockout loser'],
        ];
    }
}
