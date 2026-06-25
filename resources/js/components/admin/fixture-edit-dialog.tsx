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
                        <div className="grid grid-cols-2 gap-3">
                            <div className="grid gap-2">
                                <Label htmlFor={`fixture-home-score-${fixture.id}`}>
                                    Home score
                                </Label>
                                <Input
                                    id={`fixture-home-score-${fixture.id}`}
                                    name="home_score"
                                    type="number"
                                    min={0}
                                    max={30}
                                    defaultValue={fixture.homeScore ?? ''}
                                    required
                                />
                                <InputError message={errors.home_score} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor={`fixture-away-score-${fixture.id}`}>
                                    Away score
                                </Label>
                                <Input
                                    id={`fixture-away-score-${fixture.id}`}
                                    name="away_score"
                                    type="number"
                                    min={0}
                                    max={30}
                                    defaultValue={fixture.awayScore ?? ''}
                                    required
                                />
                                <InputError message={errors.away_score} />
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
