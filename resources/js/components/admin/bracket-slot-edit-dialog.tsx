import { Form } from '@inertiajs/react';
import { useState } from 'react';
import BracketSlotController from '@/actions/App/Http/Controllers/Admin/BracketSlotController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { DialogClose, DialogFooter } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import type { AdminBracketSlotSummary } from '@/types/admin';

interface BracketSlotEditDialogContentProps {
    slot: AdminBracketSlotSummary;
    onCancel: () => void;
}

function formatTeamLabel(
    team: AdminBracketSlotSummary['eligibleTeams'][number],
): string {
    const group = team.groupCode ? `Group ${team.groupCode}` : null;
    const position =
        team.standingPosition !== null
            ? `${team.standingPosition}${['st', 'nd', 'rd', 'th'][Math.min(team.standingPosition - 1, 3)]}`
            : null;

    return [team.name, group, position].filter(Boolean).join(' · ');
}

export function BracketSlotEditDialogContent({
    slot,
    onCancel,
}: BracketSlotEditDialogContentProps) {
    const [teamId, setTeamId] = useState(
        slot.resolvedTeam?.id ? String(slot.resolvedTeam.id) : '',
    );

    return (
        <Form
            {...BracketSlotController.update.form(slot.id)}
            options={{
                preserveScroll: true,
            }}
            className="space-y-4"
            onSuccess={onCancel}
        >
            {({ processing, errors }) => (
                <>
                    <div className="rounded-lg border bg-muted/30 px-3 py-2 text-sm">
                        <p className="font-medium">{slot.label}</p>
                        <p className="text-muted-foreground">
                            {slot.displayCode} · M
                            {slot.feedsFixture?.externalId ??
                                slot.feedsFixture?.id}{' '}
                            · {slot.side}
                        </p>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor={`bracket-slot-team-${slot.id}`}>
                            Team
                        </Label>
                        <select
                            id={`bracket-slot-team-${slot.id}`}
                            name="team_id"
                            value={teamId}
                            onChange={(event) => {
                                setTeamId(event.target.value);
                            }}
                            className="border-input flex h-9 w-full rounded-md border bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        >
                            <option value="">Unassigned</option>
                            {slot.eligibleTeams.map((team) => (
                                <option key={team.id} value={team.id}>
                                    {formatTeamLabel(team)}
                                </option>
                            ))}
                        </select>
                        <InputError message={errors.team_id} />
                        {slot.eligibleTeams.length === 0 && (
                            <p className="text-sm text-muted-foreground">
                                No eligible teams are available for this slot.
                            </p>
                        )}
                    </div>

                    <DialogFooter className="gap-2 sm:gap-0">
                        <DialogClose asChild>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={onCancel}
                            >
                                Cancel
                            </Button>
                        </DialogClose>
                        <Button type="submit" disabled={processing}>
                            Save
                        </Button>
                    </DialogFooter>
                </>
            )}
        </Form>
    );
}
