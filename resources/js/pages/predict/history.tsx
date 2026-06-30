import { Link } from '@inertiajs/react';
import { ArrowLeft, Check, Star, X } from 'lucide-react';
import { ProductShell } from '@/components/product-shell';
import { SeoHead } from '@/components/seo-head';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatFixtureCenterScore } from '@/lib/fixture-score';
import { privatePageRobots, seo } from '@/lib/seo';
import { cn } from '@/lib/utils';
import { history as predictHistory } from '@/routes/predict';
import { predict } from '@/routes';
import type { Paginated } from '@/types/admin';

type SettledPrediction = {
    id: number;
    isBanker: boolean;
    outcome: 'won' | 'lost';
    pointsAwarded: number;
    marketName: string;
    value: Record<string, unknown>;
};

type SettledFixture = {
    id: number;
    stageLabel: string;
    kickoffAt: string;
    homeTeam: string | null;
    awayTeam: string | null;
    totalPoints: number;
    homeScore: number | null;
    awayScore: number | null;
    extraTimeHome?: number | null;
    extraTimeAway?: number | null;
    penaltiesHome?: number | null;
    penaltiesAway?: number | null;
    resultDuration?: 'regular' | 'extra_time' | 'penalties' | null;
    scoreLabel?: string | null;
    predictions: SettledPrediction[];
};

type PageProps = {
    summary: {
        predictionPoints: number;
        referralPoints: number;
    };
    fixtures: Paginated<SettledFixture>;
};

function formatDateTime(value: string): string {
    return new Date(value).toLocaleString('en-GB', {
        day: 'numeric',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function formatPredictionValue(value: Record<string, unknown>): string {
    if (typeof value.selected === 'string') {
        return value.selected;
    }

    if (typeof value.answer === 'boolean') {
        return value.answer ? 'Yes' : 'No';
    }

    if (typeof value.home === 'number' && typeof value.away === 'number') {
        return `${value.home}-${value.away}`;
    }

    if (typeof value.value === 'number') {
        return String(value.value);
    }

    return JSON.stringify(value);
}

function PredictionOutcome({
    prediction,
}: {
    prediction: SettledPrediction;
}) {
    const isCorrect = prediction.outcome === 'won';

    return (
        <div
            className={cn(
                'flex shrink-0 items-center gap-1.5 rounded-md px-2 py-1 text-sm font-medium',
                isCorrect
                    ? 'bg-wc-green/10 text-wc-green'
                    : 'bg-destructive/10 text-destructive',
            )}
            aria-label={
                isCorrect
                    ? `Correct, ${prediction.pointsAwarded} points awarded${prediction.isBanker ? ', banker pick' : ''}`
                    : 'Incorrect, 0 points awarded'
            }
        >
            {isCorrect ? (
                <Check className="size-4 shrink-0" aria-hidden />
            ) : (
                <X className="size-4 shrink-0" aria-hidden />
            )}
            <span className="font-mono tabular-nums">
                {isCorrect ? `+${prediction.pointsAwarded}` : '0'} pts
            </span>
            {prediction.isBanker && (
                <Star className="size-3 shrink-0 fill-current" aria-hidden />
            )}
        </div>
    );
}

export default function PredictHistory({ summary, fixtures }: PageProps) {
    const totalPoints = summary.predictionPoints + summary.referralPoints;

    return (
        <>
            <SeoHead
                {...seo.predictHistory}
                robots={privatePageRobots}
            />

            <ProductShell>
                <div className="mx-auto w-full max-w-3xl px-4 sm:py-6">
                    <div className="flex flex-wrap items-end justify-between gap-3">
                        <div>
                            <h1 className="font-display text-4xl tracking-wide">
                                your settled picks
                            </h1>
                            <p className="mt-1 text-sm text-muted-foreground">
                                points from scored predictions and referral
                                bonuses.
                            </p>
                        </div>
                        <Button asChild variant="ghost" size="sm">
                            <Link href={predict()}>
                                <ArrowLeft className="size-4" />
                                Back to predict
                            </Link>
                        </Button>
                    </div>

                    <div className="mt-6 grid gap-3 sm:grid-cols-3">
                        <div className="rounded-xl border bg-card px-4 py-3">
                            <p className="font-mono text-[11px] tracking-[0.18em] text-muted-foreground uppercase">
                                prediction points
                            </p>
                            <p className="mt-1 font-display text-3xl tracking-wide text-wc-ink">
                                {summary.predictionPoints}
                            </p>
                        </div>
                        <div className="rounded-xl border bg-card px-4 py-3">
                            <p className="font-mono text-[11px] tracking-[0.18em] text-muted-foreground uppercase">
                                referral points
                            </p>
                            <p className="mt-1 font-display text-3xl tracking-wide text-wc-ink">
                                {summary.referralPoints}
                            </p>
                        </div>
                        <div className="rounded-xl border border-wc-gold/25 bg-wc-gold/8 px-4 py-3">
                            <p className="font-mono text-[11px] tracking-[0.18em] text-wc-gold uppercase">
                                total points
                            </p>
                            <p className="mt-1 font-display text-3xl tracking-wide text-wc-ink">
                                {totalPoints}
                            </p>
                        </div>
                    </div>

                    {fixtures.total === 0 ? (
                        <div className="mt-6 rounded-xl border border-dashed bg-card px-6 py-12 text-center text-sm text-muted-foreground">
                            No settled predictions yet. Once match results are
                            in, your scored picks will show up here.
                        </div>
                    ) : (
                        <>
                            <div className="mt-6 space-y-4">
                                {fixtures.data.map((fixture) => (
                                    <article
                                        key={fixture.id}
                                        className="overflow-hidden rounded-xl border bg-card"
                                    >
                                        <header className="flex flex-wrap items-start justify-between gap-3 border-b border-wc-ink/8 px-4 py-3">
                                            <div className="min-w-0">
                                                <div className="flex flex-wrap items-center gap-2 text-sm">
                                                    <span className="font-medium">
                                                        {fixture.homeTeam ??
                                                            'TBD'}{' '}
                                                        vs{' '}
                                                        {fixture.awayTeam ??
                                                            'TBD'}
                                                    </span>
                                                    {formatFixtureCenterScore(
                                                        fixture,
                                                    ) && (
                                                        <span className="font-mono text-muted-foreground">
                                                            (
                                                            {formatFixtureCenterScore(
                                                                fixture,
                                                            )}
                                                            )
                                                        </span>
                                                    )}
                                                    <span className="text-muted-foreground">
                                                        · {fixture.stageLabel}
                                                    </span>
                                                </div>
                                                <p className="mt-1 text-xs text-muted-foreground">
                                                    Kickoff:{' '}
                                                    {formatDateTime(
                                                        fixture.kickoffAt,
                                                    )}
                                                </p>
                                            </div>
                                            <span className="shrink-0 rounded-md bg-wc-green/10 px-2 py-1 font-mono text-sm font-medium text-wc-green tabular-nums">
                                                +{fixture.totalPoints} pts
                                            </span>
                                        </header>

                                        <ul>
                                            {fixture.predictions.map(
                                                (prediction) => (
                                                    <li
                                                        key={prediction.id}
                                                        className="flex items-start justify-between gap-4 border-b border-wc-ink/8 px-4 py-3 last:border-b-0"
                                                    >
                                                        <div className="min-w-0 flex-1">
                                                            <div className="flex flex-wrap items-center gap-2 text-sm">
                                                                <span>
                                                                    {
                                                                        prediction.marketName
                                                                    }
                                                                    :{' '}
                                                                    <span className="font-medium text-foreground">
                                                                        {formatPredictionValue(
                                                                            prediction.value,
                                                                        )}
                                                                    </span>
                                                                </span>
                                                                {prediction.isBanker && (
                                                                    <Badge variant="gold">
                                                                        <Star className="size-3 fill-current" />
                                                                        Banker
                                                                    </Badge>
                                                                )}
                                                            </div>
                                                        </div>

                                                        <PredictionOutcome
                                                            prediction={
                                                                prediction
                                                            }
                                                        />
                                                    </li>
                                                ),
                                            )}
                                        </ul>
                                    </article>
                                ))}
                            </div>

                            {fixtures.last_page > 1 && (
                                <nav
                                    aria-label="Settled predictions pagination"
                                    className="mt-6 flex flex-wrap items-center justify-center gap-1"
                                >
                                    {fixtures.links.map((link, index) => {
                                        if (link.url === null) {
                                            return (
                                                <span
                                                    key={index}
                                                    className="px-3 py-2 text-sm text-muted-foreground"
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
                        </>
                    )}
                </div>
            </ProductShell>
        </>
    );
}
