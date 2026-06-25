import { Form, Link } from '@inertiajs/react';
import { Network, Pencil } from 'lucide-react';
import { useState } from 'react';
import { BracketSlotEditDialogContent } from '@/components/admin/bracket-slot-edit-dialog';
import Heading from '@/components/heading';
import { SeoHead } from '@/components/seo-head';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { privatePageRobots } from '@/lib/seo';
import { dashboard } from '@/routes';
import { index as bracketSlotsIndex } from '@/routes/admin/bracket-slots';
import type { AdminBracketSlotSummary } from '@/types/admin';

type PageProps = {
    slots: AdminBracketSlotSummary[];
    filters: {
        type: string;
        assignment: string;
    };
    filterOptions: {
        types: Array<{ value: string; label: string }>;
    };
};

const ASSIGNMENT_OPTIONS = [
    { value: '', label: 'All slots' },
    { value: 'assigned', label: 'Assigned' },
    { value: 'unassigned', label: 'Unassigned' },
] as const;

function slotTypeLabel(
    value: string,
    options: PageProps['filterOptions']['types'],
): string {
    return options.find((option) => option.value === value)?.label ?? value;
}

function sideLabel(side: string): string {
    return side === 'home' ? 'Home' : 'Away';
}

export default function AdminBracketSlotsIndex({
    slots,
    filters,
    filterOptions,
}: PageProps) {
    const [editingSlot, setEditingSlot] =
        useState<AdminBracketSlotSummary | null>(null);
    const hasFilters = filters.type !== '' || filters.assignment !== '';

    return (
        <>
            <SeoHead
                title="Bracket slots"
                description="Assign teams to knockout bracket slots for site admins."
                path="/admin/bracket-slots"
                robots={privatePageRobots}
            />

            <div className="space-y-6">
                <Heading
                    title="Bracket slots"
                    description="Assign teams to knockout feeder slots. Eligible teams are filtered by each slot's group or match requirements."
                />

                <Form
                    action={bracketSlotsIndex().url}
                    method="get"
                    className="grid gap-4 rounded-xl border bg-card p-4 md:grid-cols-3"
                >
                    <div className="grid gap-2">
                        <Label htmlFor="filter-type">Slot type</Label>
                        <select
                            id="filter-type"
                            name="type"
                            defaultValue={filters.type}
                            className="border-input flex h-9 w-full rounded-md border bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        >
                            <option value="">All types</option>
                            {filterOptions.types.map((option) => (
                                <option key={option.value} value={option.value}>
                                    {option.label}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="filter-assignment">Assignment</Label>
                        <select
                            id="filter-assignment"
                            name="assignment"
                            defaultValue={filters.assignment}
                            className="border-input flex h-9 w-full rounded-md border bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        >
                            {ASSIGNMENT_OPTIONS.map((option) => (
                                <option key={option.value} value={option.value}>
                                    {option.label}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div className="flex items-end gap-2">
                        <Button type="submit">Apply filters</Button>
                        {hasFilters && (
                            <Button variant="outline" asChild>
                                <Link href={bracketSlotsIndex()}>Clear</Link>
                            </Button>
                        )}
                    </div>
                </Form>

                <div className="overflow-hidden rounded-xl border bg-card">
                    <table className="w-full text-sm">
                        <thead className="border-b bg-muted/40 text-left">
                            <tr>
                                <th className="px-4 py-3 font-medium">Slot</th>
                                <th className="px-4 py-3 font-medium">Feeds</th>
                                <th className="px-4 py-3 font-medium">Team</th>
                                <th className="px-4 py-3 font-medium">
                                    Eligible
                                </th>
                                <th className="px-4 py-3 font-medium" />
                            </tr>
                        </thead>
                        <tbody>
                            {slots.length === 0 ? (
                                <tr>
                                    <td
                                        colSpan={5}
                                        className="px-4 py-10 text-center text-muted-foreground"
                                    >
                                        {hasFilters
                                            ? 'No bracket slots match these filters.'
                                            : 'No bracket slots found.'}
                                    </td>
                                </tr>
                            ) : (
                                slots.map((slot) => (
                                    <tr
                                        key={slot.id}
                                        className="border-b last:border-b-0"
                                    >
                                        <td className="px-4 py-3">
                                            <div className="font-medium">
                                                {slot.label}
                                            </div>
                                            <div className="mt-1 flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                                                <span className="font-mono">
                                                    {slot.displayCode}
                                                </span>
                                                <Badge
                                                    variant="outline"
                                                    className="text-[10px] capitalize"
                                                >
                                                    {slotTypeLabel(
                                                        slot.slotType,
                                                        filterOptions.types,
                                                    )}
                                                </Badge>
                                            </div>
                                        </td>
                                        <td className="px-4 py-3">
                                            <div className="inline-flex items-center gap-1.5">
                                                <Network className="size-3.5 text-muted-foreground" />
                                                M
                                                {slot.feedsFixture
                                                    ?.externalId ??
                                                    slot.feedsFixture?.id}
                                            </div>
                                            <div className="text-xs text-muted-foreground">
                                                {slot.feedsFixture?.stageLabel}{' '}
                                                · {sideLabel(slot.side)}
                                            </div>
                                        </td>
                                        <td className="px-4 py-3">
                                            {slot.resolvedTeam ? (
                                                <div className="flex items-center gap-2">
                                                    {slot.resolvedTeam.flag ? (
                                                        <img
                                                            src={
                                                                slot.resolvedTeam
                                                                    .flag
                                                            }
                                                            alt=""
                                                            className="h-4 w-6 rounded-[2px] object-cover"
                                                        />
                                                    ) : (
                                                        <span className="h-4 w-6 rounded-[2px] bg-muted" />
                                                    )}
                                                    <span>
                                                        {slot.resolvedTeam.name}
                                                    </span>
                                                </div>
                                            ) : (
                                                <span className="text-muted-foreground">
                                                    Unassigned
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-muted-foreground">
                                            {slot.eligibleTeams.length}
                                        </td>
                                        <td className="px-4 py-3">
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                onClick={() =>
                                                    setEditingSlot(slot)
                                                }
                                                data-test={`edit-bracket-slot-${slot.id}`}
                                            >
                                                <Pencil className="size-4" />
                                                {slot.resolvedTeam
                                                    ? 'Edit'
                                                    : 'Assign'}
                                            </Button>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            </div>

            <Dialog
                open={editingSlot !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setEditingSlot(null);
                    }
                }}
            >
                <DialogContent className="sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle>
                            {editingSlot?.resolvedTeam
                                ? 'Edit bracket slot'
                                : 'Assign bracket slot'}
                        </DialogTitle>
                        <DialogDescription>
                            Only teams that match this slot&apos;s requirements
                            and are not already assigned elsewhere are shown.
                        </DialogDescription>
                    </DialogHeader>

                    {editingSlot && (
                        <BracketSlotEditDialogContent
                            slot={editingSlot}
                            onCancel={() => setEditingSlot(null)}
                        />
                    )}
                </DialogContent>
            </Dialog>
        </>
    );
}

AdminBracketSlotsIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Bracket slots', href: bracketSlotsIndex() },
    ],
};
