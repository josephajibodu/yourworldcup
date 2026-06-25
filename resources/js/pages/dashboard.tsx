import type { ReactNode } from 'react';
import { Link } from '@inertiajs/react';
import {
    ArrowRight,
    CalendarDays,
    ListChecks,
    Network,
    Target,
    Trophy,
    Users,
} from 'lucide-react';
import Heading from '@/components/heading';
import { StandingsTable } from '@/components/leaderboard/standings-table';
import { SeoHead } from '@/components/seo-head';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { privatePageRobots } from '@/lib/seo';
import { dashboard } from '@/routes';
import { index as bracketSlotsIndex } from '@/routes/admin/bracket-slots';
import { index as fixturesIndex } from '@/routes/admin/fixtures';
import { leaderboard as adminLeaderboard } from '@/routes/admin';
import { index as usersIndex } from '@/routes/admin/users';
import type { AdminDashboardSummary } from '@/types/admin';

type PageProps = AdminDashboardSummary;

function formatKickoff(value: string): string {
    return new Date(value).toLocaleString('en-GB', {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function StatCard({
    title,
    value,
    description,
    href,
}: {
    title: string;
    value: string | number;
    description: string;
    href?: string;
}) {
    const content = (
        <Card className="h-full transition-colors hover:border-wc-ink/20">
            <CardHeader className="pb-2">
                <CardDescription>{title}</CardDescription>
                <CardTitle className="text-3xl font-bold tabular-nums">
                    {value}
                </CardTitle>
            </CardHeader>
            <CardContent>
                <p className="text-sm text-muted-foreground">{description}</p>
            </CardContent>
        </Card>
    );

    if (href === undefined) {
        return content;
    }

    return (
        <Link href={href} className="block h-full">
            {content}
        </Link>
    );
}

function FixtureRow({
    fixture,
    trailing,
}: {
    fixture: PageProps['liveFixtures'][number];
    trailing?: ReactNode;
}) {
    return (
        <li className="flex items-start justify-between gap-3 border-b py-3 last:border-b-0">
            <div className="min-w-0">
                <p className="font-medium">
                    {fixture.homeTeam}{' '}
                    <span className="text-muted-foreground">vs</span>{' '}
                    {fixture.awayTeam}
                </p>
                <p className="mt-0.5 text-xs text-muted-foreground">
                    M{fixture.externalId ?? fixture.id} · {fixture.stageLabel}
                    {fixture.groupCode ? ` · Group ${fixture.groupCode}` : ''}
                </p>
                <p className="mt-1 text-xs text-muted-foreground">
                    {formatKickoff(fixture.kickoffAt)}
                </p>
            </div>
            <div className="flex shrink-0 flex-col items-end gap-1">
                {fixture.status === 'live' && (
                    <Badge variant="destructive">Live</Badge>
                )}
                {fixture.homeScore !== null && fixture.awayScore !== null && (
                    <span className="font-mono text-sm font-semibold tabular-nums">
                        {fixture.homeScore}-{fixture.awayScore}
                    </span>
                )}
                {trailing}
            </div>
        </li>
    );
}

export default function Dashboard({
    stats,
    leaderboardTop,
    liveFixtures,
    upcomingFixtures,
    fixturesNeedingSettlement,
    unassignedBracketSlots,
}: PageProps) {
    const unassignedSlots =
        stats.bracketSlots.total - stats.bracketSlots.assigned;
    const playerProgress = Math.min(
        100,
        Math.round(
            (stats.users.total / stats.users.grandPrizeTarget) * 100,
        ),
    );

    return (
        <>
            <SeoHead
                title="Dashboard"
                description="Site admin dashboard for YourWorldCup."
                path="/dashboard"
                robots={privatePageRobots}
            />

            <div className="flex flex-col gap-6">
                <Heading
                    title="Dashboard"
                    description="Tournament operations at a glance — players, fixtures, bracket slots, and leaderboard."
                />

                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <StatCard
                        title="Players"
                        value={stats.users.total}
                        description={`${stats.users.withPredictions} with predictions · ${stats.users.verified} verified`}
                        href={usersIndex().url}
                    />
                    <StatCard
                        title="Predictions"
                        value={stats.predictions.total}
                        description={`${stats.predictions.unscored} awaiting score`}
                    />
                    <StatCard
                        title="Live now"
                        value={stats.fixtures.live}
                        description={`${stats.fixtures.today} fixtures today`}
                        href={fixturesIndex().url}
                    />
                    <StatCard
                        title="Bracket slots"
                        value={`${stats.bracketSlots.assigned}/${stats.bracketSlots.total}`}
                        description={`${unassignedSlots} still unassigned`}
                        href={bracketSlotsIndex().url}
                    />
                </div>

                <div className="grid gap-4 lg:grid-cols-3">
                    <Card className="lg:col-span-2">
                        <CardHeader className="flex flex-row items-start justify-between gap-4">
                            <div>
                                <CardTitle>Grand prize unlock</CardTitle>
                                <CardDescription>
                                    Overall prize unlocks at{' '}
                                    {stats.users.grandPrizeTarget.toLocaleString()}{' '}
                                    players.
                                </CardDescription>
                            </div>
                            <span className="font-mono text-sm font-semibold tabular-nums text-wc-gold">
                                {playerProgress}%
                            </span>
                        </CardHeader>
                        <CardContent>
                            <div className="h-2 overflow-hidden rounded-full bg-muted">
                                <div
                                    className="h-full rounded-full bg-wc-gold transition-all"
                                    style={{ width: `${playerProgress}%` }}
                                />
                            </div>
                            <div className="mt-4 flex flex-wrap gap-4 text-sm text-muted-foreground">
                                <span>
                                    {stats.referrals.total} referral credits
                                </span>
                                <span>
                                    {stats.fixtures.final} /{' '}
                                    {stats.fixtures.total} fixtures final
                                </span>
                                <span>
                                    {stats.fixtures.unsettledFinal} finals with
                                    unsettled markets
                                </span>
                                <span>
                                    Groups complete:{' '}
                                    {stats.tournament.allGroupsComplete
                                        ? 'Yes'
                                        : 'No'}
                                </span>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Quick links</CardTitle>
                            <CardDescription>
                                Jump to common admin tasks.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-2">
                            {[
                                {
                                    title: 'Manage fixtures',
                                    href: fixturesIndex(),
                                    icon: CalendarDays,
                                },
                                {
                                    title: 'Bracket slots',
                                    href: bracketSlotsIndex(),
                                    icon: Network,
                                },
                                {
                                    title: 'Users',
                                    href: usersIndex(),
                                    icon: Users,
                                },
                                {
                                    title: 'Leaderboard',
                                    href: adminLeaderboard(),
                                    icon: Trophy,
                                },
                            ].map((item) => (
                                <Button
                                    key={item.title}
                                    variant="outline"
                                    className="justify-between"
                                    asChild
                                >
                                    <Link href={item.href}>
                                        <span className="inline-flex items-center gap-2">
                                            <item.icon className="size-4" />
                                            {item.title}
                                        </span>
                                        <ArrowRight className="size-4" />
                                    </Link>
                                </Button>
                            ))}
                        </CardContent>
                    </Card>
                </div>

                <div className="grid gap-4 xl:grid-cols-2">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between gap-4">
                            <div>
                                <CardTitle>Overall leaderboard</CardTitle>
                                <CardDescription>Top five right now.</CardDescription>
                            </div>
                            <Button variant="ghost" size="sm" asChild>
                                <Link href={adminLeaderboard()}>
                                    View all
                                    <ArrowRight className="size-4" />
                                </Link>
                            </Button>
                        </CardHeader>
                        <CardContent>
                            <StandingsTable
                                rows={leaderboardTop}
                                currentUserId={null}
                                emptyMessage="No participants yet."
                            />
                        </CardContent>
                    </Card>

                    <div className="grid gap-4">
                        <Card>
                            <CardHeader>
                                <CardTitle>Live matches</CardTitle>
                                <CardDescription>
                                    Fixtures in progress right now.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                {liveFixtures.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        No live matches at the moment.
                                    </p>
                                ) : (
                                    <ul>
                                        {liveFixtures.map((fixture) => (
                                            <FixtureRow
                                                key={fixture.id}
                                                fixture={fixture}
                                            />
                                        ))}
                                    </ul>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Up next</CardTitle>
                                <CardDescription>
                                    Next five scheduled fixtures.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                {upcomingFixtures.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        No upcoming fixtures scheduled.
                                    </p>
                                ) : (
                                    <ul>
                                        {upcomingFixtures.map((fixture) => (
                                            <FixtureRow
                                                key={fixture.id}
                                                fixture={fixture}
                                            />
                                        ))}
                                    </ul>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <div className="grid gap-4 xl:grid-cols-2">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between gap-4">
                            <div>
                                <CardTitle className="inline-flex items-center gap-2">
                                    <ListChecks className="size-4" />
                                    Needs settlement
                                </CardTitle>
                                <CardDescription>
                                    Final fixtures with open markets.
                                </CardDescription>
                            </div>
                            <Button variant="ghost" size="sm" asChild>
                                <Link href={fixturesIndex()}>
                                    Fixtures
                                    <ArrowRight className="size-4" />
                                </Link>
                            </Button>
                        </CardHeader>
                        <CardContent>
                            {fixturesNeedingSettlement.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    All final fixtures are fully settled.
                                </p>
                            ) : (
                                <ul>
                                    {fixturesNeedingSettlement.map((fixture) => (
                                        <FixtureRow
                                            key={fixture.id}
                                            fixture={fixture}
                                            trailing={
                                                <Badge variant="outline">
                                                    {fixture.unsettledMarketsCount}
                                                    /{fixture.marketsCount}{' '}
                                                    open
                                                </Badge>
                                            }
                                        />
                                    ))}
                                </ul>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between gap-4">
                            <div>
                                <CardTitle className="inline-flex items-center gap-2">
                                    <Target className="size-4" />
                                    Unassigned bracket slots
                                </CardTitle>
                                <CardDescription>
                                    Knockout feeder slots still waiting for a
                                    team.
                                </CardDescription>
                            </div>
                            <Button variant="ghost" size="sm" asChild>
                                <Link
                                    href={bracketSlotsIndex({
                                        query: { assignment: 'unassigned' },
                                    })}
                                >
                                    View all
                                    <ArrowRight className="size-4" />
                                </Link>
                            </Button>
                        </CardHeader>
                        <CardContent>
                            {unassignedBracketSlots.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    Every bracket slot has a team assigned.
                                </p>
                            ) : (
                                <ul className="divide-y">
                                    {unassignedBracketSlots.map((slot) => (
                                        <li
                                            key={slot.id}
                                            className="flex items-center justify-between gap-3 py-3 first:pt-0 last:pb-0"
                                        >
                                            <div className="min-w-0">
                                                <p className="truncate font-medium">
                                                    {slot.label}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {slot.displayCode} · M
                                                    {slot.feedsFixtureExternalId}{' '}
                                                    ·{' '}
                                                    {slot.feedsFixtureStageLabel}
                                                </p>
                                            </div>
                                            <Badge variant="outline">
                                                Open
                                            </Badge>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
