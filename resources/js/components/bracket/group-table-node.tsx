import { Handle,  Position  } from '@xyflow/react';
import type {NodeProps, Node} from '@xyflow/react';
import { useMemo } from 'react';
import { LiveIndicator } from '@/components/live-indicator';
import { useMatchDurationMinutes } from '@/hooks/use-match-duration-minutes';
import { useNow } from '@/hooks/use-now';
import { liveTeamIds } from '@/lib/fixture-live';
import { cn } from '@/lib/utils';
import type { GroupNodeData } from './types';

type GroupNode = Node<GroupNodeData, 'group'>;

export function GroupTableNode({ data }: NodeProps<GroupNode>) {
    const { group, active } = data;
    const now = useNow();
    const matchDurationMinutes = useMatchDurationMinutes();
    const playingTeamIds = useMemo(
        () => liveTeamIds(group.fixtures, matchDurationMinutes, now),
        [group.fixtures, matchDurationMinutes, now],
    );

    return (
        <div
            className={cn(
                'w-[260px] overflow-hidden rounded-lg border bg-card text-card-foreground shadow-sm',
                active
                    ? 'border-wc-gold ring-2 ring-wc-gold/35 shadow-md'
                    : 'border-wc-ink/10',
            )}
        >
            <div className="flex items-center justify-between bg-wc-ink px-3 py-2 text-wc-surface">
                <span className="font-display text-sm tracking-wider uppercase">
                    Group {group.code}
                </span>
                <span className="font-mono text-[10px] tracking-wider text-wc-surface/55">
                    P GD PTS
                </span>
            </div>
            <ul>
                {group.teams.map((team, index) => {
                    const qualifies = index < 2;
                    const isLive = playingTeamIds.has(team.id);

                    return (
                        <li
                            key={team.id}
                            className={cn(
                                'flex items-center gap-2 px-3 py-1.5 text-xs',
                                index > 0 && 'border-t border-border',
                                qualifies && 'bg-wc-green/5',
                            )}
                        >
                            <span
                                className={cn(
                                    'w-3 shrink-0 text-center font-mono text-[10px]',
                                    qualifies
                                        ? 'text-wc-green'
                                        : 'text-muted-foreground',
                                )}
                            >
                                {index + 1}
                            </span>
                            {team.flag ? (
                                <img
                                    src={team.flag}
                                    alt=""
                                    className="h-3.5 w-5 shrink-0 rounded-[2px] object-cover"
                                />
                            ) : (
                                <span className="h-3.5 w-5 shrink-0 rounded-[2px] bg-muted" />
                            )}
                            <span className="flex min-w-0 flex-1 items-center justify-start gap-1.5 text-left">
                                <span className="truncate font-medium">
                                    {team.name}
                                </span>
                                {isLive && <LiveIndicator label="" />}
                            </span>
                            <span className="w-4 shrink-0 text-right font-mono text-[11px] text-muted-foreground">
                                {team.played}
                            </span>
                            <span className="w-6 shrink-0 text-right font-mono text-[11px] text-muted-foreground">
                                {team.gd > 0 ? `+${team.gd}` : team.gd}
                            </span>
                            <span className="w-5 shrink-0 text-right font-mono text-[11px] font-semibold">
                                {team.points}
                            </span>
                        </li>
                    );
                })}
            </ul>
            <Handle
                type="source"
                position={Position.Right}
                className="!size-1.5 !border-0 !bg-wc-ink-3"
            />
        </div>
    );
}
