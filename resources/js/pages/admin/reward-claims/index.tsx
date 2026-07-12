import { Link } from '@inertiajs/react';
import { Gift } from 'lucide-react';
import Heading from '@/components/heading';
import { DateNav } from '@/components/leaderboard/date-nav';
import { SeoHead } from '@/components/seo-head';
import { Badge } from '@/components/ui/badge';
import { formatTwitterHandle } from '@/lib/twitter-handle';
import { privatePageRobots } from '@/lib/seo';
import { dashboard } from '@/routes';
import { index as rewardClaimsIndex } from '@/routes/admin/reward-claims';
import type { AdminRewardClaimSummary, AdminRewardClaimsSummary, Paginated } from '@/types/admin';

interface PageProps {
    claims: Paginated<AdminRewardClaimSummary>;
    dates: string[];
    selectedWeek: string | null;
    summary: AdminRewardClaimsSummary;
}

function formatWeek(weekStart: string): string {
    const start = new Date(`${weekStart}T12:00:00`);
    const end = new Date(start);
    end.setDate(end.getDate() + 6);
    const fmt = (d: Date) =>
        d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short' });

    return `${fmt(start)} – ${fmt(end)}`;
}

function formatSubmittedAt(value: string): string {
    return new Date(value).toLocaleString('en-GB', {
        day: 'numeric',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function payoutDetails(claim: AdminRewardClaimSummary): string {
    if (claim.passedOn) {
        return claim.passOnMessage ?? '—';
    }

    if (claim.preference === 'cash') {
        return `${claim.accountHolderName ?? '—'} · ${claim.bankName ?? '—'} · ${claim.accountNumber ?? '—'}`;
    }

    if (claim.preference === 'airtime' || claim.preference === 'data') {
        return `${claim.phoneNumber ?? '—'} · ${claim.mobileNetworkLabel ?? '—'}`;
    }

    return '—';
}

export default function AdminRewardClaimsIndex({
    claims,
    dates,
    selectedWeek,
    summary,
}: PageProps) {
    return (
        <>
            <SeoHead
                title="Reward claims"
                description="Review weekly airtime reward submissions."
                path="/admin/reward-claims"
                robots={privatePageRobots}
            />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <Heading
                        title="Reward claims"
                        description="Review weekly airtime submissions, payout details, and pass-ons."
                    />
                    {dates.length > 0 && (
                        <DateNav
                            dates={dates}
                            selectedDate={selectedWeek}
                            formatLabel={formatWeek}
                            hrefForDate={(week) =>
                                rewardClaimsIndex({ query: { week } })
                            }
                        />
                    )}
                </div>

                <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
                    {[
                        { label: 'Responses', value: summary.total },
                        { label: 'Claimed', value: summary.claimed },
                        { label: 'Passed on', value: summary.passed },
                        { label: 'Airtime', value: summary.airtime },
                        { label: 'Data', value: summary.data },
                        { label: 'Cash', value: summary.cash },
                    ].map((stat) => (
                        <div
                            key={stat.label}
                            className="rounded-xl border bg-card px-4 py-3"
                        >
                            <p className="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                {stat.label}
                            </p>
                            <p className="mt-1 text-2xl font-bold tabular-nums">
                                {stat.value}
                            </p>
                        </div>
                    ))}
                </div>

                <div className="overflow-hidden rounded-xl border bg-card">
                    <div className="overflow-x-auto">
                        <table className="min-w-full text-sm">
                            <thead className="border-b bg-muted/40 text-left">
                                <tr>
                                    <th className="px-4 py-3 font-medium">
                                        Player
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Week
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Rank
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Response
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Payout / message
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Submitted
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {claims.data.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={6}
                                            className="px-4 py-10 text-center text-muted-foreground"
                                        >
                                            No reward submissions for this week
                                            yet.
                                        </td>
                                    </tr>
                                ) : (
                                    claims.data.map((claim) => (
                                        <tr
                                            key={claim.id}
                                            className="border-b last:border-b-0"
                                        >
                                            <td className="px-4 py-3 align-top">
                                                <div className="font-medium">
                                                    {formatTwitterHandle(
                                                        claim.user.name,
                                                    )}
                                                </div>
                                                <div className="text-xs text-muted-foreground">
                                                    {claim.user.email}
                                                </div>
                                            </td>
                                            <td className="px-4 py-3 align-top whitespace-nowrap">
                                                {formatWeek(claim.weekStart)}
                                            </td>
                                            <td className="px-4 py-3 align-top tabular-nums">
                                                #{claim.leaderboardRank}
                                            </td>
                                            <td className="px-4 py-3 align-top">
                                                {claim.passedOn ? (
                                                    <Badge variant="secondary">
                                                        Passed on
                                                    </Badge>
                                                ) : (
                                                    <Badge>
                                                        {claim.preferenceLabel}
                                                    </Badge>
                                                )}
                                            </td>
                                            <td className="max-w-md px-4 py-3 align-top text-muted-foreground">
                                                {payoutDetails(claim)}
                                            </td>
                                            <td className="px-4 py-3 align-top whitespace-nowrap text-muted-foreground">
                                                {claim.submittedAt
                                                    ? formatSubmittedAt(
                                                          claim.submittedAt,
                                                      )
                                                    : '—'}
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

                {claims.last_page > 1 && (
                    <div className="flex flex-wrap gap-2">
                        {claims.links.map((link) =>
                            link.url ? (
                                <Link
                                    key={link.label}
                                    href={link.url}
                                    className={`rounded-md border px-3 py-1 text-sm ${
                                        link.active
                                            ? 'border-primary bg-primary text-primary-foreground'
                                            : 'bg-card hover:bg-muted'
                                    }`}
                                    preserveScroll
                                >
                                    <span
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                    />
                                </Link>
                            ) : null,
                        )}
                    </div>
                )}
            </div>
        </>
    );
}

AdminRewardClaimsIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Reward claims', href: rewardClaimsIndex() },
    ],
};
