import { Form, Link } from '@inertiajs/react';
import { CalendarDays, Pencil } from 'lucide-react';
import { useState } from 'react';
import { FixtureEditDialogContent } from '@/components/admin/fixture-edit-dialog';
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
import { cn } from '@/lib/utils';
import { formatFixtureCenterScore } from '@/lib/fixture-score';
import { privatePageRobots } from '@/lib/seo';
import { index as fixturesIndex } from '@/routes/admin/fixtures';
import { dashboard } from '@/routes';
import type {
    AdminFilterOption,
    AdminFixtureSummary,
    Paginated,
} from '@/types/admin';

type PageProps = {
    fixtures: Paginated<AdminFixtureSummary>;
    filters: {
        date: string;
        team: number | null;
        stadium: number | null;
        referee: string;
    };
    filterOptions: {
        dates: string[];
        teams: AdminFilterOption[];
        stadiums: AdminFilterOption[];
        referees: string[];
    };
};

function formatKickoff(value: string): string {
    return new Date(value).toLocaleString('en-GB', {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function formatDay(value: string): string {
    return new Date(`${value}T12:00:00`).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
    });
}

function statusVariant(
    status: string,
): 'default' | 'secondary' | 'outline' | 'destructive' {
    switch (status) {
        case 'final':
            return 'default';
        case 'live':
            return 'destructive';
        case 'void':
            return 'secondary';
        default:
            return 'outline';
    }
}

function buildFilterQuery(
    filters: PageProps['filters'],
): Record<string, string> {
    const query: Record<string, string> = {};

    if (filters.date) {
        query.date = filters.date;
    }

    if (filters.team) {
        query.team = String(filters.team);
    }

    if (filters.stadium) {
        query.stadium = String(filters.stadium);
    }

    if (filters.referee) {
        query.referee = filters.referee;
    }

    return query;
}

export default function AdminFixturesIndex({
    fixtures,
    filters,
    filterOptions,
}: PageProps) {
    const [editingFixture, setEditingFixture] =
        useState<AdminFixtureSummary | null>(null);
    const hasFilters =
        filters.date !== '' ||
        filters.team !== null ||
        filters.stadium !== null ||
        filters.referee !== '';

    return (
        <>
            <SeoHead
                title="Fixtures"
                description="Manage World Cup fixtures for site admins."
                path="/admin/fixtures"
                robots={privatePageRobots}
            />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <Heading
                        title="Fixtures"
                        description="Review matches, update scores and status, and settle predictions."
                    />
                </div>

                <Form
                    {...fixturesIndex.form({
                        query: buildFilterQuery(filters),
                    })}
                    method="get"
                    className="grid gap-3 rounded-xl border bg-card p-4 md:grid-cols-2 xl:grid-cols-5"
                >
                    <div className="grid gap-2">
                        <Label htmlFor="filter-date">Date</Label>
                        <select
                            id="filter-date"
                            name="date"
                            defaultValue={filters.date}
                            className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        >
                            <option value="">All dates</option>
                            {filterOptions.dates.map((date) => (
                                <option key={date} value={date}>
                                    {formatDay(date)}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="filter-team">Team</Label>
                        <select
                            id="filter-team"
                            name="team"
                            defaultValue={filters.team ?? ''}
                            className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        >
                            <option value="">All teams</option>
                            {filterOptions.teams.map((team) => (
                                <option key={team.id} value={team.id}>
                                    {team.name}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="filter-stadium">Stadium</Label>
                        <select
                            id="filter-stadium"
                            name="stadium"
                            defaultValue={filters.stadium ?? ''}
                            className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        >
                            <option value="">All stadiums</option>
                            {filterOptions.stadiums.map((stadium) => (
                                <option key={stadium.id} value={stadium.id}>
                                    {stadium.name}
                                    {stadium.city ? ` (${stadium.city})` : ''}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="filter-referee">Referee</Label>
                        <select
                            id="filter-referee"
                            name="referee"
                            defaultValue={filters.referee}
                            className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        >
                            <option value="">All referees</option>
                            {filterOptions.referees.map((referee) => (
                                <option key={referee} value={referee}>
                                    {referee}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div className="flex items-end gap-2">
                        <Button type="submit" className="w-full sm:w-auto">
                            Apply filters
                        </Button>
                        {hasFilters && (
                            <Button
                                type="button"
                                variant="secondary"
                                className="w-full sm:w-auto"
                                asChild
                            >
                                <Link href={fixturesIndex()}>Clear</Link>
                            </Button>
                        )}
                    </div>
                </Form>

                <div className="overflow-hidden rounded-xl border">
                    <table className="w-full min-w-[56rem] text-left text-sm">
                        <thead className="border-b bg-muted/40 text-xs text-muted-foreground uppercase">
                            <tr>
                                <th className="px-4 py-3 font-medium">Match</th>
                                <th className="px-4 py-3 font-medium">
                                    Kickoff
                                </th>
                                <th className="px-4 py-3 font-medium">Venue</th>
                                <th className="px-4 py-3 font-medium">
                                    Referee
                                </th>
                                <th className="px-4 py-3 font-medium">Score</th>
                                <th className="px-4 py-3 font-medium">
                                    Status
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    Markets
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {fixtures.data.length === 0 ? (
                                <tr>
                                    <td
                                        colSpan={8}
                                        className="px-4 py-10 text-center text-muted-foreground"
                                    >
                                        {hasFilters
                                            ? 'No fixtures match these filters.'
                                            : 'No fixtures found.'}
                                    </td>
                                </tr>
                            ) : (
                                fixtures.data.map((fixture) => (
                                    <tr
                                        key={fixture.id}
                                        className="border-b last:border-b-0"
                                    >
                                        <td className="px-4 py-3">
                                            <div className="font-medium">
                                                {fixture.homeTeam}{' '}
                                                <span className="px-1 text-muted-foreground">
                                                    vs
                                                </span>{' '}
                                                {fixture.awayTeam}
                                            </div>
                                            <div className="text-xs text-muted-foreground">
                                                M
                                                {fixture.externalId ??
                                                    fixture.id}{' '}
                                                · {fixture.stageLabel}
                                                {fixture.groupCode
                                                    ? ` · Group ${fixture.groupCode}`
                                                    : ''}
                                            </div>
                                        </td>
                                        <td className="px-4 py-3 whitespace-nowrap">
                                            <span className="inline-flex items-center gap-1.5">
                                                <CalendarDays className="size-3.5 text-muted-foreground" />
                                                {formatKickoff(
                                                    fixture.kickoffAt,
                                                )}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3">
                                            {fixture.stadium?.name ?? '—'}
                                        </td>
                                        <td className="px-4 py-3">
                                            {fixture.referee ?? '—'}
                                        </td>
                                        <td className="px-4 py-3 font-mono tabular-nums">
                                            {formatFixtureCenterScore(fixture) ??
                                                '—'}
                                        </td>
                                        <td className="px-4 py-3">
                                            <Badge
                                                variant={statusVariant(
                                                    fixture.status,
                                                )}
                                                className="capitalize"
                                            >
                                                {fixture.status}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-3 text-muted-foreground">
                                            {fixture.settledMarketsCount}/
                                            {fixture.marketsCount} settled
                                        </td>
                                        <td className="px-4 py-3">
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                onClick={() =>
                                                    setEditingFixture(fixture)
                                                }
                                                data-test={`edit-fixture-${fixture.id}`}
                                            >
                                                <Pencil className="size-4" />
                                                Edit
                                            </Button>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                {fixtures.last_page > 1 && (
                    <nav className="flex flex-wrap gap-1">
                        {fixtures.links.map((link, index) => {
                            if (link.url === null) {
                                return (
                                    <span
                                        key={index}
                                        className="rounded-md px-3 py-2 text-sm text-muted-foreground"
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                    />
                                );
                            }

                            return (
                                <Link
                                    key={index}
                                    href={link.url}
                                    preserveScroll
                                    className={cn(
                                        'rounded-md px-3 py-2 text-sm transition-colors',
                                        link.active
                                            ? 'bg-wc-ink text-wc-surface'
                                            : 'text-muted-foreground hover:bg-secondary hover:text-foreground',
                                    )}
                                    dangerouslySetInnerHTML={{
                                        __html: link.label,
                                    }}
                                />
                            );
                        })}
                    </nav>
                )}
            </div>

            <Dialog
                open={editingFixture !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setEditingFixture(null);
                    }
                }}
            >
                <DialogContent className="sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle>Edit fixture</DialogTitle>
                        <DialogDescription>
                            Update the match status and score. Optionally settle
                            predictions when saving a final or void result.
                        </DialogDescription>
                    </DialogHeader>

                    {editingFixture && (
                        <FixtureEditDialogContent
                            fixture={editingFixture}
                            onCancel={() => setEditingFixture(null)}
                        />
                    )}
                </DialogContent>
            </Dialog>
        </>
    );
}

AdminFixturesIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Fixtures', href: fixturesIndex() },
    ],
};
