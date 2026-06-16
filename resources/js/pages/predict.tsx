import { Head, Link, router, usePage } from '@inertiajs/react';
import { CalendarDays, ChevronLeft, ChevronRight } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { toast } from 'sonner';
import { store as storePredictions } from '@/actions/App/Http/Controllers/PredictController';
import { FixtureCard } from '@/components/predict/fixture-card';
import type { MarketValue, PredictFixture } from '@/components/predict/types';
import { SiteHeader } from '@/components/site-header';
import { Button } from '@/components/ui/button';
import { predict } from '@/routes';

interface PredictPageProps {
    dates: string[];
    selectedDate: string | null;
    fixtures: PredictFixture[];
    [key: string]: unknown;
}

function formatDay(date: string): string {
    return new Date(`${date}T12:00:00`).toLocaleDateString('en-GB', {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
    });
}

function initialPicks(fixtures: PredictFixture[]): Record<number, MarketValue> {
    const picks: Record<number, MarketValue> = {};

    for (const fixture of fixtures) {
        for (const market of fixture.markets) {
            if (market.value !== null) {
                picks[market.id] = market.value;
            }
        }
    }

    return picks;
}

function initialBanker(fixtures: PredictFixture[]): number | null {
    for (const fixture of fixtures) {
        for (const market of fixture.markets) {
            if (market.isBanker) {
                return market.id;
            }
        }
    }

    return null;
}

function PredictDay({ fixtures, selectedDate, dates }: PredictPageProps) {
    const [picks, setPicks] = useState(() => initialPicks(fixtures));
    const [banker, setBanker] = useState(() => initialBanker(fixtures));
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [processing, setProcessing] = useState(false);
    const [now, setNow] = useState(() => Date.now());

    useEffect(() => {
        const id = window.setInterval(() => setNow(Date.now()), 30000);

        return () => window.clearInterval(id);
    }, []);

    const index = selectedDate ? dates.indexOf(selectedDate) : -1;
    const prevDate = index > 0 ? dates[index - 1] : null;
    const nextDate =
        index >= 0 && index < dates.length - 1 ? dates[index + 1] : null;

    const openCount = useMemo(
        () =>
            fixtures.reduce(
                (total, fixture) =>
                    total +
                    fixture.markets.filter((market) => !market.locked).length,
                0,
            ),
        [fixtures],
    );

    const pickCount = Object.keys(picks).length;

    const setPick = (marketId: number, value: MarketValue) => {
        setPicks((current) => ({ ...current, [marketId]: value }));
    };

    const toggleBanker = (marketId: number) => {
        setBanker((current) => (current === marketId ? null : marketId));
    };

    const submit = () => {
        const predictions = Object.entries(picks).map(([id, value]) => ({
            fixture_market_id: Number(id),
            value,
        }));

        router.post(
            storePredictions.url(),
            {
                date: selectedDate,
                predictions,
                banker_fixture_market_id: banker,
            },
            {
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
                onSuccess: () => {
                    setErrors({});
                    toast.success('Your picks are in.');
                },
                onError: (formErrors) =>
                    setErrors(formErrors as Record<string, string>),
            },
        );
    };

    return (
        <div className="mx-auto w-full max-w-3xl px-4 py-6">
            <div className="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h1 className="font-display text-3xl tracking-wide uppercase">
                        Make your picks
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Predict each market before kickoff. Star one pick as your{' '}
                        <span className="font-semibold text-wc-gold-deep">
                            banker
                        </span>{' '}
                        to double its points.
                    </p>
                </div>
                <span className="inline-flex items-center gap-1.5 font-mono text-xs tracking-wider text-muted-foreground uppercase">
                    <CalendarDays className="size-3.5" />
                    {pickCount} picked · {openCount} open
                </span>
            </div>

            <div className="mt-5 flex items-center justify-between gap-2 rounded-lg border bg-card px-2 py-2">
                <Button
                    asChild={prevDate !== null}
                    variant="ghost"
                    size="sm"
                    disabled={prevDate === null}
                >
                    {prevDate !== null ? (
                        <Link
                            href={predict({ query: { date: prevDate } })}
                            preserveScroll
                        >
                            <ChevronLeft className="size-4" />
                            <span className="hidden sm:inline">Prev day</span>
                        </Link>
                    ) : (
                        <span>
                            <ChevronLeft className="size-4" />
                        </span>
                    )}
                </Button>
                <span className="font-display text-lg tracking-wide uppercase">
                    {selectedDate ? formatDay(selectedDate) : 'No fixtures'}
                </span>
                <Button
                    asChild={nextDate !== null}
                    variant="ghost"
                    size="sm"
                    disabled={nextDate === null}
                >
                    {nextDate !== null ? (
                        <Link
                            href={predict({ query: { date: nextDate } })}
                            preserveScroll
                        >
                            <span className="hidden sm:inline">Next day</span>
                            <ChevronRight className="size-4" />
                        </Link>
                    ) : (
                        <span>
                            <ChevronRight className="size-4" />
                        </span>
                    )}
                </Button>
            </div>

            {(errors.predictions || errors.banker) && (
                <p className="mt-4 rounded-md bg-wc-primary/10 px-3 py-2 text-sm font-medium text-wc-primary">
                    {errors.predictions ?? errors.banker}
                </p>
            )}

            <div className="mt-4 space-y-4">
                {fixtures.length === 0 ? (
                    <div className="rounded-xl border border-dashed bg-card px-6 py-12 text-center text-sm text-muted-foreground">
                        No matches to predict on this day.
                    </div>
                ) : (
                    fixtures.map((fixture) => (
                        <FixtureCard
                            key={fixture.id}
                            fixture={fixture}
                            picks={picks}
                            banker={banker}
                            errors={errors}
                            now={now}
                            onPick={setPick}
                            onBanker={toggleBanker}
                        />
                    ))
                )}
            </div>

            {fixtures.length > 0 && (
                <div className="sticky bottom-4 mt-6 flex justify-end">
                    <Button
                        type="button"
                        variant="gold"
                        size="lg"
                        disabled={processing || openCount === 0}
                        onClick={submit}
                        className="shadow-lg"
                    >
                        {openCount === 0 ? 'Day locked' : 'Save my picks'}
                    </Button>
                </div>
            )}
        </div>
    );
}

export default function Predict() {
    const props = usePage<PredictPageProps>().props;

    return (
        <>
            <Head title="Make your picks" />
            <div className="min-h-screen bg-background font-sans text-foreground">
                <SiteHeader />
                <PredictDay key={props.selectedDate ?? 'none'} {...props} />
            </div>
        </>
    );
}
