import { Link, usePage } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import {
    WeeklyRewardPanel,
    type WeeklyRewardStatus,
} from '@/components/leaderboard/weekly-reward-panel';
import { StandingsTable } from '@/components/leaderboard/standings-table';
import type { StandingsRow } from '@/components/leaderboard/standings-table';
import { ProductShell } from '@/components/product-shell';
import { SeoHead } from '@/components/seo-head';
import { Button } from '@/components/ui/button';
import { seo } from '@/lib/seo';
import { leaderboard } from '@/routes';

const TWITTER_HANDLE = 'joseph_ajibodu';
const TWITTER_URL = `https://x.com/${TWITTER_HANDLE}`;

interface LeaderboardPageProps {
    overall: StandingsRow[];
    weekly: StandingsRow[];
    dates: string[];
    selectedDate: string | null;
    weeklyReward: WeeklyRewardStatus | null;
    [key: string]: unknown;
}

function formatWeek(weekStart: string): string {
    const start = new Date(`${weekStart}T12:00:00`);
    const end = new Date(start);
    end.setDate(end.getDate() + 6);
    const fmt = (d: Date) =>
        d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short' });

    return `${fmt(start)} – ${fmt(end)}`;
}

export default function Leaderboard() {
    const { overall, weekly, dates, selectedDate, weeklyReward, auth } =
        usePage<LeaderboardPageProps>().props;
    const currentUserId = auth.user?.id ?? null;

    const index = selectedDate ? dates.indexOf(selectedDate) : -1;
    const prevDate = index > 0 ? dates[index - 1] : null;
    const nextDate =
        index >= 0 && index < dates.length - 1 ? dates[index + 1] : null;

    return (
        <>
            <SeoHead {...seo.leaderboard} />
            <ProductShell>
                <div className="mx-auto max-w-6xl px-6 sm:py-10 md:py-14">
                    <div className="max-w-2xl">
                        <h1 className="text-4xl font-bold tracking-tight text-wc-ink md:text-5xl">
                            leaderboard
                        </h1>
                        <p className="mt-3 text-base leading-relaxed text-muted-foreground">
                            points land as results come in. climb the weekly
                            board for airtime, and the overall table for the
                            grand prize.
                        </p>
                    </div>

                    <div className="mt-8 flex gap-4 rounded-2xl border border-wc-gold/25 bg-wc-gold/8 p-5 md:items-center">
                        <div className="grid size-14 shrink-0 place-items-center rounded-full bg-wc-gold/15 ring-1 ring-wc-gold/35">
                            <img
                                src="/images/world-cup-icon.png"
                                alt=""
                                className="size-9 object-contain"
                            />
                        </div>
                        <div className="min-w-0">
                            <p className="font-mono text-[11px] font-bold tracking-[0.18em] text-wc-gold uppercase">
                                overall monetary prize
                            </p>
                            <p className="mt-1 text-sm font-semibold text-wc-ink">
                                the overall winner takes a cash grand prize at
                                the final whistle.
                            </p>
                            <p className="mt-1 text-sm leading-relaxed text-wc-ink/60">
                                keep climbing the overall table, the top spot
                                when the tournament ends wins the grand prize,
                                unlocked once we reach 1,000 players.
                            </p>
                            <p className="mt-3 text-sm leading-relaxed text-wc-ink/60">
                                to qualify for any reward, weekly airtime or the
                                grand prize, you must be following{' '}
                                <a
                                    href={TWITTER_URL}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="font-semibold text-wc-ink underline-offset-4 hover:underline"
                                >
                                    @{TWITTER_HANDLE}
                                </a>{' '}
                                on X.
                            </p>
                        </div>
                    </div>

                    <div className="mt-10 grid gap-8 md:grid-cols-2">
                        <div>
                            <div className="mb-4 flex items-center gap-2">
                                <div>
                                    <h2 className="text-lg font-bold tracking-tight text-wc-ink">
                                        overall
                                    </h2>
                                    <p className="text-xs text-muted-foreground">
                                        Race for the monetary grand prize
                                    </p>
                                </div>
                            </div>
                            <StandingsTable
                                rows={overall}
                                currentUserId={currentUserId}
                                emptyMessage="No predictors yet — make your first pick to join the table."
                            />
                        </div>

                        <div>
                            <div className="mb-4 flex items-start justify-between gap-3">
                                <div className="flex items-center gap-2">
                                    <div>
                                        <h2 className="text-lg font-bold tracking-tight text-wc-ink">
                                            weekly
                                        </h2>
                                        <p className="text-xs text-muted-foreground">
                                            top the week for airtime rewards
                                        </p>
                                    </div>
                                </div>
                                <div className="flex items-center gap-1">
                                    <Button
                                        asChild={prevDate !== null}
                                        variant="ghost"
                                        size="icon"
                                        className="rounded-full"
                                        disabled={prevDate === null}
                                    >
                                        {prevDate !== null ? (
                                            <Link
                                                href={leaderboard({
                                                    query: { date: prevDate },
                                                })}
                                            >
                                                <ChevronLeft className="size-4" />
                                            </Link>
                                        ) : (
                                            <span>
                                                <ChevronLeft className="size-4" />
                                            </span>
                                        )}
                                    </Button>
                                    <span className="min-w-36 text-center font-mono text-xs font-semibold tracking-wider text-wc-ink/70 uppercase tabular-nums">
                                        {selectedDate
                                            ? formatWeek(selectedDate)
                                            : '—'}
                                    </span>
                                    <Button
                                        asChild={nextDate !== null}
                                        variant="ghost"
                                        size="icon"
                                        className="rounded-full"
                                        disabled={nextDate === null}
                                    >
                                        {nextDate !== null ? (
                                            <Link
                                                href={leaderboard({
                                                    query: { date: nextDate },
                                                })}
                                            >
                                                <ChevronRight className="size-4" />
                                            </Link>
                                        ) : (
                                            <span>
                                                <ChevronRight className="size-4" />
                                            </span>
                                        )}
                                    </Button>
                                </div>
                            </div>
                            <StandingsTable
                                rows={weekly}
                                currentUserId={currentUserId}
                                emptyMessage="No picks or referrals this week yet."
                            />
                            {selectedDate && weeklyReward && (
                                <WeeklyRewardPanel
                                    weekStart={selectedDate}
                                    status={weeklyReward}
                                />
                            )}
                        </div>
                    </div>

                    <p className="mt-8 flex items-center gap-2 text-sm text-muted-foreground">
                        surprise drops can land at any moment, be playing when
                        they hit.
                    </p>
                </div>
            </ProductShell>
        </>
    );
}
