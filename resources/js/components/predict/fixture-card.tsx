import { Lock, Star } from 'lucide-react';
import type {FocusEvent} from 'react';
import { LiveIndicator } from '@/components/live-indicator';
import { useMatchDurationMinutes } from '@/hooks/use-match-duration-minutes';
import { isFixtureFinal, isFixtureLive } from '@/lib/fixture-live';
import { cn } from '@/lib/utils';
import { MarketInput } from './market-input';
import { useScoreStepper } from './score-stepper-context';
import type { MarketValue, PredictFixture } from './types';

interface FixtureCardProps {
    fixture: PredictFixture;
    picks: Record<number, MarketValue>;
    banker: number | null;
    errors: Record<string, string>;
    now: number;
    onPick: (marketId: number, value: MarketValue) => void;
    onBanker: (marketId: number) => void;
}

function lockLabel(lockAt: string, now: number): string {
    const diff = new Date(lockAt).getTime() - now;

    if (diff <= 0) {
        return 'Locked';
    }

    const minutes = Math.floor(diff / 60000);
    const days = Math.floor(minutes / 1440);
    const hours = Math.floor((minutes % 1440) / 60);
    const mins = minutes % 60;

    if (days > 0) {
        return `Locks in ${days}d ${hours}h`;
    }

    if (hours > 0) {
        return `Locks in ${hours}h ${mins}m`;
    }

    return `Locks in ${mins}m`;
}

function TeamSide({
    team,
    align,
}: {
    team: PredictFixture['home'];
    align: 'left' | 'right';
}) {
    return (
        <div
            className={cn(
                'flex flex-1 items-center gap-2',
                align === 'right' && 'flex-row-reverse text-right',
            )}
        >
            {team?.flag ? (
                <img
                    src={team.flag}
                    alt=""
                    className="h-5 w-7 shrink-0 rounded-[2px] object-cover"
                />
            ) : (
                <span className="h-5 w-7 shrink-0 rounded-[2px] bg-muted" />
            )}
            <span className="truncate text-xs font-semibold sm:text-sm">
                {team?.name ?? 'TBD'}
            </span>
        </div>
    );
}

export function FixtureCard({
    fixture,
    picks,
    banker,
    errors,
    now,
    onPick,
    onBanker,
}: FixtureCardProps) {
    const kickoff = new Date(fixture.kickoffAt);
    const time = kickoff.toLocaleTimeString('en-GB', {
        hour: '2-digit',
        minute: '2-digit',
    });
    const matchDurationMinutes = useMatchDurationMinutes();
    const locked = new Date(fixture.lockAt).getTime() <= now;
    const live = isFixtureLive(
        fixture.status,
        fixture.kickoffAt,
        matchDurationMinutes,
        now,
    );
    const final = isFixtureFinal(fixture.status);
    const centerLabel =
        final &&
        fixture.homeScore !== null &&
        fixture.awayScore !== null
            ? `${fixture.homeScore} – ${fixture.awayScore}`
            : time;
    const { dismiss } = useScoreStepper();

    const handleFocusOut = (event: FocusEvent<HTMLDivElement>) => {
        const related = event.relatedTarget;

        if (related instanceof Node && event.currentTarget.contains(related)) {
            return;
        }

        dismiss();
    };

    return (
        <div
            className="overflow-hidden rounded-xl border bg-card text-card-foreground shadow-sm"
            onFocusOut={handleFocusOut}
        >
            <div className="flex items-center justify-between gap-2 border-b bg-secondary/60 px-4 py-2">
                <span className="inline-flex items-center gap-2 font-mono text-[11px] tracking-wider text-muted-foreground uppercase">
                    {fixture.group
                        ? `Group ${fixture.group}`
                        : fixture.stageLabel}
                    {live && <LiveIndicator label="" />}
                    {final && (
                        <span className="rounded bg-muted px-1.5 py-0.5 text-[10px] font-semibold text-foreground">
                            FT
                        </span>
                    )}
                </span>
                <span
                    className={cn(
                        'inline-flex items-center gap-1.5 font-mono text-[11px] tracking-wider uppercase',
                        locked ? 'text-wc-primary' : 'text-muted-foreground',
                    )}
                >
                    {locked && <Lock className="size-3" />}
                    {lockLabel(fixture.lockAt, now)}
                </span>
            </div>

            <div className="flex items-center gap-3 px-4 py-3">
                <TeamSide team={fixture.home} align="left" />
                <span
                    className={cn(
                        'shrink-0 font-mono text-sm font-semibold tabular-nums',
                        final ? 'text-foreground' : 'text-muted-foreground',
                    )}
                >
                    {centerLabel}
                </span>
                <TeamSide team={fixture.away} align="right" />
            </div>

            <div className="space-y-3 border-t px-4 py-3">
                {fixture.markets.map((market) => {
                    const disabled = locked || market.locked;
                    const hasPick = picks[market.id] !== undefined;
                    const error = errors[`markets.${market.id}`];

                    return (
                        <div key={market.id} className="space-y-1.5">
                            <div className="flex items-center justify-between gap-2">
                                <div className="flex items-center gap-1.5">
                                    <span className="text-xs font-semibold">
                                        {market.name}
                                    </span>
                                    <span className="rounded bg-secondary px-1.5 py-0.5 font-mono text-[10px] font-semibold text-muted-foreground">
                                        {market.points}
                                        {market.points === 1 ? ' PT' : ' PTS'}
                                    </span>
                                </div>
                                <button
                                    type="button"
                                    disabled={disabled || !hasPick}
                                    onClick={() => onBanker(market.id)}
                                    title="Banker — doubles points on one pick a day"
                                    className={cn(
                                        'inline-flex items-center gap-1 rounded-md px-1.5 py-0.5 font-mono text-[10px] font-semibold tracking-wider uppercase transition-colors disabled:cursor-not-allowed disabled:opacity-40',
                                        banker === market.id
                                            ? 'bg-wc-gold text-wc-ink'
                                            : 'text-muted-foreground hover:bg-wc-gold/15 hover:text-wc-gold-deep',
                                    )}
                                >
                                    <Star
                                        className={cn(
                                            'size-3',
                                            banker === market.id &&
                                                'fill-current',
                                        )}
                                    />
                                    {banker === market.id ? '×2' : 'Banker'}
                                </button>
                            </div>
                            <MarketInput
                                market={market}
                                value={picks[market.id] ?? null}
                                disabled={disabled}
                                onChange={(value) => onPick(market.id, value)}
                            />
                            {error && (
                                <p className="text-xs font-medium text-wc-primary">
                                    {error}
                                </p>
                            )}
                        </div>
                    );
                })}
            </div>
        </div>
    );
}
