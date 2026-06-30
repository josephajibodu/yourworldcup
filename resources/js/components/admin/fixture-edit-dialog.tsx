import { Form } from '@inertiajs/react';
import { useState } from 'react';
import FixtureController from '@/actions/App/Http/Controllers/Admin/FixtureController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { DialogClose, DialogFooter } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { AdminFixtureSummary } from '@/types/admin';

const STATUS_OPTIONS = [
    { value: 'scheduled', label: 'Scheduled' },
    { value: 'live', label: 'Live' },
    { value: 'final', label: 'Final' },
    { value: 'void', label: 'Void' },
] as const;

const RESULT_DURATION_OPTIONS = [
    { value: '', label: 'Auto / not set' },
    { value: 'regular', label: 'Regular time (FT)' },
    { value: 'extra_time', label: 'After extra time (AET)' },
    { value: 'penalties', label: 'After penalties (PEN)' },
] as const;

interface FixtureEditDialogContentProps {
    fixture: AdminFixtureSummary;
    onCancel: () => void;
}

export function FixtureEditDialogContent({
    fixture,
    onCancel,
}: FixtureEditDialogContentProps) {
    const [status, setStatus] = useState(fixture.status);
    const [settle, setSettle] = useState(false);
    const showScores = status === 'final';
    const showSettle = status === 'final' || status === 'void';

    return (
        <Form
            {...FixtureController.update.form(fixture.id)}
            options={{
                preserveScroll: true,
            }}
            className="space-y-4"
            onSuccess={onCancel}
        >
            {({ processing, errors }) => (
                <>
                    <div className="rounded-lg border bg-muted/30 px-3 py-2 text-sm">
                        <p className="font-medium">
                            {fixture.homeTeam} vs {fixture.awayTeam}
                        </p>
                        <p className="text-muted-foreground">
                            M{fixture.externalId ?? fixture.id} ·{' '}
                            {fixture.stageLabel}
                        </p>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor={`fixture-status-${fixture.id}`}>
                            Status
                        </Label>
                        <select
                            id={`fixture-status-${fixture.id}`}
                            name="status"
                            value={status}
                            onChange={(event) => {
                                setStatus(event.target.value);
                            }}
                            className="border-input flex h-9 w-full rounded-md border bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        >
                            {STATUS_OPTIONS.map((option) => (
                                <option key={option.value} value={option.value}>
                                    {option.label}
                                </option>
                            ))}
                        </select>
                        <InputError message={errors.status} />
                    </div>

                    {showScores && (
                        <div className="space-y-4">
                            <div className="space-y-2">
                                <p className="text-sm font-medium">
                                    Regular time
                                </p>
                                <p className="text-xs text-muted-foreground">
                                    Used for match winner and exact score
                                    predictions.
                                </p>
                                <div className="grid grid-cols-2 gap-3">
                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor={`fixture-home-score-${fixture.id}`}
                                        >
                                            Home
                                        </Label>
                                        <Input
                                            id={`fixture-home-score-${fixture.id}`}
                                            name="home_score"
                                            type="number"
                                            min={0}
                                            max={30}
                                            defaultValue={
                                                fixture.homeScore ?? ''
                                            }
                                            required
                                        />
                                        <InputError
                                            message={errors.home_score}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor={`fixture-away-score-${fixture.id}`}
                                        >
                                            Away
                                        </Label>
                                        <Input
                                            id={`fixture-away-score-${fixture.id}`}
                                            name="away_score"
                                            type="number"
                                            min={0}
                                            max={30}
                                            defaultValue={
                                                fixture.awayScore ?? ''
                                            }
                                            required
                                        />
                                        <InputError
                                            message={errors.away_score}
                                        />
                                    </div>
                                </div>
                            </div>

                            <div className="space-y-2">
                                <p className="text-sm font-medium">
                                    Extra time
                                </p>
                                <p className="text-xs text-muted-foreground">
                                    Goals scored in extra time only. Leave blank
                                    if the match did not go to extra time.
                                </p>
                                <div className="grid grid-cols-2 gap-3">
                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor={`fixture-extra-home-${fixture.id}`}
                                        >
                                            Home
                                        </Label>
                                        <Input
                                            id={`fixture-extra-home-${fixture.id}`}
                                            name="extra_time_home"
                                            type="number"
                                            min={0}
                                            max={30}
                                            defaultValue={
                                                fixture.extraTimeHome ?? ''
                                            }
                                        />
                                        <InputError
                                            message={errors.extra_time_home}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor={`fixture-extra-away-${fixture.id}`}
                                        >
                                            Away
                                        </Label>
                                        <Input
                                            id={`fixture-extra-away-${fixture.id}`}
                                            name="extra_time_away"
                                            type="number"
                                            min={0}
                                            max={30}
                                            defaultValue={
                                                fixture.extraTimeAway ?? ''
                                            }
                                        />
                                        <InputError
                                            message={errors.extra_time_away}
                                        />
                                    </div>
                                </div>
                            </div>

                            <div className="space-y-2">
                                <p className="text-sm font-medium">
                                    Penalties
                                </p>
                                <p className="text-xs text-muted-foreground">
                                    Shootout score only, e.g. 2–3 for a 1(2)–1(3)
                                    display.
                                </p>
                                <div className="grid grid-cols-2 gap-3">
                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor={`fixture-pen-home-${fixture.id}`}
                                        >
                                            Home
                                        </Label>
                                        <Input
                                            id={`fixture-pen-home-${fixture.id}`}
                                            name="penalties_home"
                                            type="number"
                                            min={0}
                                            max={30}
                                            defaultValue={
                                                fixture.penaltiesHome ?? ''
                                            }
                                        />
                                        <InputError
                                            message={errors.penalties_home}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor={`fixture-pen-away-${fixture.id}`}
                                        >
                                            Away
                                        </Label>
                                        <Input
                                            id={`fixture-pen-away-${fixture.id}`}
                                            name="penalties_away"
                                            type="number"
                                            min={0}
                                            max={30}
                                            defaultValue={
                                                fixture.penaltiesAway ?? ''
                                            }
                                        />
                                        <InputError
                                            message={errors.penalties_away}
                                        />
                                    </div>
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label
                                    htmlFor={`fixture-result-duration-${fixture.id}`}
                                >
                                    Result type
                                </Label>
                                <select
                                    id={`fixture-result-duration-${fixture.id}`}
                                    name="result_duration"
                                    defaultValue={fixture.resultDuration ?? ''}
                                    className="border-input flex h-9 w-full rounded-md border bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                >
                                    {RESULT_DURATION_OPTIONS.map((option) => (
                                        <option
                                            key={option.value || 'auto'}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.result_duration} />
                            </div>
                        </div>
                    )}

                    {showSettle && (
                        <div className="flex items-start gap-3 rounded-lg border px-3 py-3">
                            <input
                                type="hidden"
                                name="settle"
                                value={settle ? '1' : '0'}
                            />
                            <Checkbox
                                id={`fixture-settle-${fixture.id}`}
                                checked={settle}
                                onCheckedChange={(checked) =>
                                    setSettle(checked === true)
                                }
                            />
                            <div className="grid gap-1">
                                <Label
                                    htmlFor={`fixture-settle-${fixture.id}`}
                                    className="font-medium"
                                >
                                    Settle predictions now
                                </Label>
                                <p className="text-sm text-muted-foreground">
                                    Score or void open markets for this fixture
                                    and award points immediately.
                                </p>
                            </div>
                        </div>
                    )}

                    <DialogFooter className="gap-2 sm:justify-end">
                        <DialogClose asChild>
                            <Button type="button" variant="secondary">
                                Cancel
                            </Button>
                        </DialogClose>
                        <Button
                            type="submit"
                            disabled={processing}
                            data-test="save-fixture-button"
                        >
                            Save fixture
                        </Button>
                    </DialogFooter>
                </>
            )}
        </Form>
    );
}
