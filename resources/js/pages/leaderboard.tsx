import { Head, Link, usePage } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, Crown } from 'lucide-react';
import { StandingsTable } from '@/components/leaderboard/standings-table';
import type { StandingsRow } from '@/components/leaderboard/standings-table';
import { SiteHeader } from '@/components/site-header';
import { Button } from '@/components/ui/button';
import { leaderboard } from '@/routes';

interface LeaderboardPageProps {
    overall: StandingsRow[];
    daily: StandingsRow[];
    dates: string[];
    selectedDate: string | null;
    [key: string]: unknown;
}

function formatDay(date: string): string {
    return new Date(`${date}T12:00:00`).toLocaleDateString('en-GB', {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
    });
}

export default function Leaderboard() {
    const { overall, daily, dates, selectedDate, auth } =
        usePage<LeaderboardPageProps>().props;
    const currentUserId = auth.user?.id ?? null;

    const index = selectedDate ? dates.indexOf(selectedDate) : -1;
    const prevDate = index > 0 ? dates[index - 1] : null;
    const nextDate =
        index >= 0 && index < dates.length - 1 ? dates[index + 1] : null;

    return (
        <>
            <Head title="Leaderboard" />
            <div className="min-h-screen bg-background font-sans text-foreground">
                <SiteHeader />

                <section className="relative overflow-hidden bg-wc-primary text-white">
                    <div
                        aria-hidden
                        className="pointer-events-none absolute inset-0"
                        style={{
                            background:
                                'radial-gradient(120% 90% at 50% 120%, #E9A721 0%, rgba(233,167,33,0.3) 32%, transparent 64%)',
                        }}
                    />
                    <div className="relative mx-auto max-w-5xl px-5 py-10">
                        <h1 className="font-display text-4xl tracking-tight uppercase md:text-5xl">
                            Leaderboard
                        </h1>
                        <p className="mt-2 max-w-lg text-sm text-white/90">
                            Points land as results come in. Top the daily board
                            for airtime, and the overall table for the grand
                            prize.
                        </p>
                    </div>
                </section>

                <div className="mx-auto grid max-w-5xl gap-6 px-5 py-8 md:grid-cols-2">
                    <div>
                        <div className="mb-3 flex items-center gap-2">
                            <Crown className="size-5 text-wc-gold" />
                            <h2 className="font-display text-xl tracking-wide uppercase">
                                Overall
                            </h2>
                        </div>
                        <StandingsTable
                            rows={overall}
                            currentUserId={currentUserId}
                            emptyMessage="No points yet — standings fill in as matches are scored."
                        />
                    </div>

                    <div>
                        <div className="mb-3 flex items-center justify-between gap-2">
                            <h2 className="font-display text-xl tracking-wide uppercase">
                                Daily
                            </h2>
                            <div className="flex items-center gap-1">
                                <Button
                                    asChild={prevDate !== null}
                                    variant="ghost"
                                    size="icon"
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
                                <span className="min-w-28 text-center font-mono text-xs font-semibold tracking-wider tabular-nums uppercase">
                                    {selectedDate
                                        ? formatDay(selectedDate)
                                        : '—'}
                                </span>
                                <Button
                                    asChild={nextDate !== null}
                                    variant="ghost"
                                    size="icon"
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
                            rows={daily}
                            currentUserId={currentUserId}
                            emptyMessage="No scored picks for this day yet."
                        />
                    </div>
                </div>
            </div>
        </>
    );
}
