import { Link } from '@inertiajs/react';
import { ArrowLeft, Check, Star, X } from 'lucide-react';
import Heading from '@/components/heading';
import { SeoHead } from '@/components/seo-head';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatFixtureCenterScore } from '@/lib/fixture-score';
import { privatePageRobots } from '@/lib/seo';
import { formatTwitterHandle } from '@/lib/twitter-handle';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import { index as usersIndex } from '@/routes/admin/users';
import { index as userPredictionsIndex } from '@/routes/admin/users/predictions';
import type { AdminUserPrediction, Paginated } from '@/types/admin';

type PageProps = {
    user: {
        id: number;
        name: string;
        email: string;
    };
    predictions: Paginated<AdminUserPrediction>;
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
    prediction: AdminUserPrediction;
}) {
    if (!prediction.isScored || prediction.pointsAwarded === null) {
        return null;
    }

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

export default function AdminUserPredictions({ user, predictions }: PageProps) {
    return (
        <>
            <SeoHead
                title={`Predictions - ${formatTwitterHandle(user.name)}`}
                description={`Predictions for ${formatTwitterHandle(user.name)}.`}
                path={userPredictionsIndex.url(user.id)}
                robots={privatePageRobots}
            />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <Heading
                        title={`${formatTwitterHandle(user.name)} predictions`}
                        description={user.email}
                    />
                    <Button asChild variant="secondary">
                        <Link href={usersIndex()}>
                            <ArrowLeft className="size-4" />
                            Back to users
                        </Link>
                    </Button>
                </div>

                {predictions.total === 0 ? (
                    <div className="rounded-xl border border-dashed border-wc-ink/15 bg-card px-6 py-12 text-center text-sm text-muted-foreground">
                        This user has not submitted any predictions yet.
                    </div>
                ) : (
                    <>
                        <div className="overflow-hidden rounded-xl border border-wc-ink/10 bg-card">
                            <ul>
                                {predictions.data.map((prediction) => (
                                    <li
                                        key={prediction.id}
                                        className="flex items-start justify-between gap-4 border-b border-wc-ink/8 px-4 py-3 last:border-b-0"
                                    >
                                        <div className="min-w-0 flex-1">
                                            <div className="flex flex-wrap items-center gap-2 text-sm">
                                                <span className="font-medium">
                                                    {prediction.fixture.homeTeam ?? 'TBD'} vs{' '}
                                                    {prediction.fixture.awayTeam ?? 'TBD'}
                                                </span>
                                                {formatFixtureCenterScore(
                                                    prediction.fixture,
                                                ) && (
                                                    <span className="font-mono text-muted-foreground">
                                                        (
                                                        {formatFixtureCenterScore(
                                                            prediction.fixture,
                                                        )}
                                                        )
                                                    </span>
                                                )}
                                                <span className="text-muted-foreground">
                                                    · {prediction.fixture.stageLabel}
                                                </span>
                                            </div>
                                            <div className="mt-1 flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
                                                <span>
                                                    {prediction.marketName}:{' '}
                                                    <span className="font-medium text-foreground">
                                                        {formatPredictionValue(prediction.value)}
                                                    </span>
                                                </span>
                                                {prediction.isBanker && (
                                                    <Badge variant="gold">
                                                        <Star className="size-3 fill-current" />
                                                        Banker
                                                    </Badge>
                                                )}
                                            </div>
                                            <p className="mt-1 text-xs text-muted-foreground">
                                                Kickoff:{' '}
                                                {formatDateTime(prediction.fixture.kickoffAt)} ·
                                                Submitted:{' '}
                                                {formatDateTime(prediction.submittedAt)}
                                            </p>
                                        </div>

                                        <PredictionOutcome prediction={prediction} />
                                    </li>
                                ))}
                            </ul>
                        </div>

                        {predictions.last_page > 1 && (
                            <nav
                                aria-label="Predictions pagination"
                                className="flex flex-wrap items-center justify-center gap-1"
                            >
                                {predictions.links.map((link, index) => {
                                    if (link.url === null) {
                                        return (
                                            <span
                                                key={index}
                                                className="px-3 py-2 text-sm text-muted-foreground"
                                                dangerouslySetInnerHTML={{ __html: link.label }}
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
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    );
                                })}
                            </nav>
                        )}
                    </>
                )}
            </div>
        </>
    );
}

AdminUserPredictions.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
        {
            title: 'Users',
            href: usersIndex(),
        },
    ],
};
