import { Handle, Position } from '@xyflow/react';
import type { Node, NodeProps } from '@xyflow/react';
import { Shield } from 'lucide-react';
import { LiveIndicator } from '@/components/live-indicator';
import { useMatchDurationMinutes } from '@/hooks/use-match-duration-minutes';
import { useNow } from '@/hooks/use-now';
import { isFixtureFinal, isFixtureLive } from '@/lib/fixture-live';
import {
    formatFixtureSideScore,
    formatFixtureStatusLabel,
    fixtureHasPenaltyScore,
} from '@/lib/fixture-score';
import {
    formatMatchKickoff,
    isShowpieceStage,
    SHOWPIECE_HANDLE_TOP,
    SHOWPIECE_NODE_H,
    SHOWPIECE_NODE_W,
} from '@/lib/showpiece-match';
import { cn } from '@/lib/utils';
import type { KnockoutMatch, MatchNodeData, Slot } from './types';

type MatchNode = Node<MatchNodeData, 'match'>;

function matchSideScores(
    match: KnockoutMatch,
    showScores: boolean,
): { home: string | null; away: string | null } {
    if (
        !showScores ||
        match.homeScore === null ||
        match.awayScore === null
    ) {
        return { home: null, away: null };
    }

    const hasPenalties = fixtureHasPenaltyScore(match);

    return {
        home: formatFixtureSideScore(
            match.homeScore,
            match.extraTimeHome,
            match.penaltiesHome,
            hasPenalties,
        ),
        away: formatFixtureSideScore(
            match.awayScore,
            match.extraTimeAway,
            match.penaltiesAway,
            hasPenalties,
        ),
    };
}

function SlotRow({
    slot,
    divider,
    score,
    showpiece = false,
}: {
    slot: Slot;
    divider: boolean;
    score?: string | null;
    showpiece?: boolean;
}) {
    return (
        <div
            className={cn(
                showpiece
                    ? 'flex items-center gap-2.5 px-4 py-2'
                    : 'flex items-center gap-2 px-2.5 py-1.5',
                divider && 'border-t border-border',
            )}
        >
            {slot.team ? (
                <>
                    {slot.team.flag ? (
                        <img
                            src={slot.team.flag}
                            alt=""
                            className="h-4 w-5 shrink-0 rounded-[2px] object-cover"
                        />
                    ) : (
                        <span className="h-4 w-5 shrink-0 rounded-[2px] bg-muted" />
                    )}
                    <span className="min-w-0 flex-1 truncate text-sm font-semibold">
                        {slot.team.name}
                    </span>
                </>
            ) : showpiece ? (
                <>
                    <Shield
                        className="size-4 shrink-0 text-muted-foreground/70"
                        aria-hidden
                    />
                    <span className="min-w-0 flex-1 truncate font-mono text-xs text-muted-foreground">
                        {slot.label ?? 'TBD'}
                    </span>
                </>
            ) : (
                <>
                    <span className="grid h-3.5 w-5 shrink-0 place-items-center rounded-[2px] bg-muted font-mono text-[8px] text-muted-foreground">
                        ?
                    </span>
                    <span className="min-w-0 flex-1 truncate font-mono text-[11px] text-muted-foreground">
                        {slot.label ?? 'TBD'}
                    </span>
                </>
            )}
            {score != null && (
                <span className="shrink-0 font-mono text-sm font-semibold tabular-nums text-foreground">
                    {score}
                </span>
            )}
        </div>
    );
}

function ShowpieceMatchCard({
    match,
    active,
    live,
    final,
}: {
    match: KnockoutMatch;
    active?: boolean;
    live: boolean;
    final: boolean;
}) {
    const isThird = match.stage === 'third_place';
    const kickoffLabel = formatMatchKickoff(match.kickoffAt, match.timezone);
    const sideScores = matchSideScores(match, final || live);
    const statusLabel = formatFixtureStatusLabel(match, final);

    return (
        <div
            className={cn(
                'overflow-hidden rounded-xl border bg-card text-card-foreground shadow-sm',
                isThird
                    ? 'border-sky-500/80'
                    : active
                      ? 'border-wc-gold ring-2 ring-wc-gold/35 shadow-md'
                      : 'border-wc-ink/10',
            )}
            style={{ width: SHOWPIECE_NODE_W, height: SHOWPIECE_NODE_H }}
        >
            <Handle
                type="target"
                position={Position.Left}
                className="!size-1.5 !border-0 !bg-wc-ink-3"
                style={{ top: SHOWPIECE_HANDLE_TOP }}
            />
            <div className="border-b border-border px-4 py-3 text-center">
                <div className="flex items-center justify-center gap-1.5">
                    <h3 className="text-sm font-bold tracking-tight text-foreground">
                        {match.headline ?? match.stageLabel}
                    </h3>
                    {live && <LiveIndicator label="" />}
                    {statusLabel && (
                        <span className="rounded bg-wc-ink/10 px-1 py-0.5 text-[9px] font-semibold text-wc-ink">
                            {statusLabel}
                        </span>
                    )}
                </div>
                {(match.stadium || match.location) && (
                    <div className="mt-1 space-y-0.5 text-xs text-muted-foreground">
                        {match.stadium && <p>{match.stadium}</p>}
                        {match.location && <p>{match.location}</p>}
                    </div>
                )}
            </div>
            <SlotRow
                slot={match.home}
                divider={false}
                score={sideScores.home}
                showpiece
            />
            <SlotRow
                slot={match.away}
                divider
                score={sideScores.away}
                showpiece
            />
            <div className="flex items-center justify-between border-t border-border px-4 py-2 text-[11px] text-muted-foreground">
                <span>{kickoffLabel}</span>
                {match.broadcast && <span>{match.broadcast}</span>}
            </div>
            {!isThird && (
                <Handle
                    type="source"
                    position={Position.Right}
                    className="!size-1.5 !border-0 !bg-wc-ink-3"
                    style={{ top: SHOWPIECE_HANDLE_TOP }}
                />
            )}
        </div>
    );
}

export function MatchNode({ data }: NodeProps<MatchNode>) {
    const { match, active } = data;
    const now = useNow();
    const matchDurationMinutes = useMatchDurationMinutes();
    const live = isFixtureLive(
        match.status,
        match.kickoffAt,
        matchDurationMinutes,
        now,
    );
    const final = isFixtureFinal(match.status);

    if (isShowpieceStage(match.stage)) {
        return (
            <ShowpieceMatchCard
                match={match}
                active={active}
                live={live}
                final={final}
            />
        );
    }

    const kickoffLabel = formatMatchKickoff(match.kickoffAt, match.timezone);
    const sideScores = matchSideScores(match, final || live);
    const statusLabel = formatFixtureStatusLabel(match, final);

    return (
        <div
            className={cn(
                'w-[208px] overflow-hidden rounded-lg border bg-card text-card-foreground shadow-sm',
                active
                    ? 'border-wc-gold ring-2 ring-wc-gold/35 shadow-md'
                    : 'border-wc-ink/10',
            )}
        >
            <Handle
                type="target"
                position={Position.Left}
                className="!size-1.5 !border-0 !bg-wc-ink-3"
            />
            <div className="flex items-center justify-between gap-2 bg-wc-surface-2 px-2.5 py-1">
                <span className="flex shrink-0 items-center gap-1.5 font-mono text-[10px] font-semibold tracking-wider text-wc-ink/70">
                    {match.code}
                    {live && <LiveIndicator label="" />}
                    {statusLabel && (
                        <span className="rounded bg-wc-ink/10 px-1 py-0.5 text-[9px] font-semibold text-wc-ink">
                            {statusLabel}
                        </span>
                    )}
                </span>
                <span className="truncate text-right font-mono text-[10px] tracking-wide text-muted-foreground">
                    {kickoffLabel}
                </span>
            </div>
            <SlotRow
                slot={match.home}
                divider={false}
                score={sideScores.home}
            />
            <SlotRow
                slot={match.away}
                divider
                score={sideScores.away}
            />
            <Handle
                type="source"
                position={Position.Right}
                className="!size-1.5 !border-0 !bg-wc-ink-3"
            />
        </div>
    );
}
