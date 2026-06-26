import { Link, usePage } from '@inertiajs/react';
import { ArrowLeft, Medal } from 'lucide-react';
import { ProductShell } from '@/components/product-shell';
import { SeoHead } from '@/components/seo-head';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import { seo } from '@/lib/seo';
import { bestThirds, bracket } from '@/routes';

interface BestThirdRankingRow {
    rank: number;
    qualifies: boolean;
    groupCode: string;
    groupComplete: boolean;
    matchesLeft: number;
    team: {
        id: number;
        name: string;
        code: string;
        flag: string | null;
    };
    played: number;
    won: number;
    drawn: number;
    lost: number;
    gf: number;
    ga: number;
    gd: number;
    points: number;
}

interface BestThirdsPageProps {
    rankings: BestThirdRankingRow[];
    allGroupsComplete: boolean;
    [key: string]: unknown;
}

function formatGoalDifference(gd: number): string {
    if (gd > 0) {
        return `+${gd}`;
    }

    return String(gd);
}

function formatMatchesLeft(count: number): string {
    return `${count} match${count === 1 ? '' : 'es'} left`;
}

export default function BestThirds() {
    const { rankings, allGroupsComplete } =
        usePage<BestThirdsPageProps>().props;

    return (
        <>
            <SeoHead {...seo.bestThirds} />
            <ProductShell>
                <div className="mx-auto max-w-4xl px-6 sm:py-10 md:py-14">
                    <Link
                        href={bracket()}
                        className="inline-flex items-center gap-1.5 text-sm font-medium text-muted-foreground transition-colors hover:text-wc-ink"
                    >
                        <ArrowLeft className="size-4" />
                        Bracket
                    </Link>

                    <div className="mt-2 sm:mt-6 max-w-2xl">
                        <h1 className="text-3xl sm:text-4xl font-bold tracking-tight text-wc-ink md:text-5xl">
                            best third-place teams
                        </h1>
                    </div>

                    {!allGroupsComplete && (
                        <p className="mt-2 sm:mt-6 text-sm text-muted-foreground">
                            Rankings update as group matches finish. Third-place
                            teams from incomplete groups are shown based on
                            current standings and may change.
                        </p>
                    )}

                    <div className="mt-8 overflow-hidden rounded-2xl border border-wc-ink/10 bg-card shadow-sm">
                        <div className="grid grid-cols-[auto_1fr_auto] items-center gap-x-3 border-b border-wc-ink/10 bg-wc-ink px-4 py-3 text-wc-surface sm:grid-cols-[auto_1fr_repeat(5,auto)]">
                            <span className="w-8 font-mono text-[10px] tracking-wider uppercase">
                                #
                            </span>
                            <span className="font-mono text-[10px] tracking-wider uppercase">
                                Team
                            </span>
                            <span className="hidden font-mono text-[10px] tracking-wider uppercase sm:block">
                                Group
                            </span>
                            <span className="hidden w-8 text-right font-mono text-[10px] tracking-wider uppercase sm:block">
                                P
                            </span>
                            <span className="hidden w-10 text-right font-mono text-[10px] tracking-wider uppercase sm:block">
                                GD
                            </span>
                            <span className="hidden w-8 text-right font-mono text-[10px] tracking-wider uppercase sm:block">
                                GF
                            </span>
                            <span className="w-10 text-right font-mono text-[10px] tracking-wider uppercase">
                                Pts
                            </span>
                        </div>

                        <ul>
                            {rankings.map((row) => (
                                <li
                                    key={row.team.id}
                                    className={cn(
                                        'grid grid-cols-[auto_1fr_auto] items-center gap-x-3 border-t border-border px-4 py-3 text-sm first:border-t-0 sm:grid-cols-[auto_1fr_repeat(5,auto)]',
                                        row.qualifies && 'bg-wc-green/5',
                                    )}
                                >
                                    <span
                                        className={cn(
                                            'flex w-8 items-center gap-1 font-mono text-xs font-semibold',
                                            row.qualifies
                                                ? 'text-wc-green'
                                                : 'text-muted-foreground',
                                        )}
                                    >
                                        {row.qualifies && (
                                            <Medal
                                                className="size-3.5 shrink-0"
                                                aria-hidden
                                            />
                                        )}
                                        {row.rank}
                                    </span>

                                    <div className="flex min-w-0 items-center gap-2">
                                        {row.team.flag ? (
                                            <img
                                                src={row.team.flag}
                                                alt=""
                                                className="h-4 w-6 shrink-0 rounded-[2px] object-cover"
                                            />
                                        ) : (
                                            <span className="h-4 w-6 shrink-0 rounded-[2px] bg-muted" />
                                        )}
                                        <div className="min-w-0 flex-1">
                                            <div className="flex min-w-0 items-center gap-2">
                                                <p className="truncate font-medium text-wc-ink">
                                                    {row.team.name}
                                                </p>
                                                {row.matchesLeft > 0 && (
                                                    <Badge
                                                        variant="outline"
                                                        className="h-5 shrink-0 px-1.5 text-[10px] font-normal"
                                                    >
                                                        {formatMatchesLeft(
                                                            row.matchesLeft,
                                                        )}
                                                    </Badge>
                                                )}
                                            </div>
                                            <div className="mt-0.5 sm:hidden">
                                                <span className="font-mono text-[11px] text-muted-foreground">
                                                    Group {row.groupCode}
                                                </span>
                                            </div>
                                        </div>
                                        {row.qualifies && (
                                            <Badge className="ml-auto hidden bg-wc-green text-[10px] text-white sm:ml-0">
                                                Qualifies
                                            </Badge>
                                        )}
                                    </div>

                                    <span className="hidden w-12 font-mono text-xs text-muted-foreground sm:block">
                                        {row.groupCode}
                                    </span>

                                    <span className="hidden w-8 text-right font-mono text-xs text-muted-foreground sm:block">
                                        {row.played}
                                    </span>
                                    <span className="hidden w-10 text-right font-mono text-xs text-muted-foreground sm:block">
                                        {formatGoalDifference(row.gd)}
                                    </span>
                                    <span className="hidden w-8 text-right font-mono text-xs text-muted-foreground sm:block">
                                        {row.gf}
                                    </span>
                                    <span className="w-10 text-right font-mono text-sm font-semibold text-wc-ink">
                                        {row.points}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    </div>

                    <p className="mt-4 text-center text-xs text-muted-foreground">
                        Top eight teams highlighted in green qualify for the
                        round of 32.
                    </p>

                    <div className="mt-8 rounded-2xl border border-wc-ink/10 bg-wc-surface-2/60 p-5">
                        <p className="font-mono text-[11px] font-bold tracking-[0.18em] text-wc-ink/70 uppercase">
                            tiebreak order
                        </p>
                        <ol className="mt-3 list-decimal space-y-1 pl-5 text-sm text-wc-ink/80">
                            <li>Total points</li>
                            <li>Goal difference</li>
                            <li>Goals scored</li>
                            <li className="text-muted-foreground">
                                Team conduct (yellow and red cards) — not applied
                                yet
                            </li>
                            <li className="text-muted-foreground">
                                FIFA ranking — not applied yet
                            </li>
                        </ol>
                    </div>
                </div>
            </ProductShell>
        </>
    );
}
