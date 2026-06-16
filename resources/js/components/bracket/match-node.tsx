import { Handle,   Position } from '@xyflow/react';
import type {Node, NodeProps} from '@xyflow/react';
import { cn } from '@/lib/utils';
import type { MatchNodeData, Slot } from './types';

type MatchNode = Node<MatchNodeData, 'match'>;

function SlotRow({ slot, divider }: { slot: Slot; divider: boolean }) {
    return (
        <div
            className={cn(
                'flex items-center gap-2 px-2.5 py-1.5',
                divider && 'border-t border-border',
            )}
        >
            {slot.team ? (
                <>
                    {slot.team.flag ? (
                        <img
                            src={slot.team.flag}
                            alt=""
                            className="h-3.5 w-5 shrink-0 rounded-[2px] object-cover"
                        />
                    ) : (
                        <span className="h-3.5 w-5 shrink-0 rounded-[2px] bg-muted" />
                    )}
                    <span className="truncate text-xs font-semibold">
                        {slot.team.name}
                    </span>
                </>
            ) : (
                <>
                    <span className="grid h-3.5 w-5 shrink-0 place-items-center rounded-[2px] bg-muted font-mono text-[8px] text-muted-foreground">
                        ?
                    </span>
                    <span className="truncate font-mono text-[11px] text-muted-foreground">
                        {slot.label ?? 'TBD'}
                    </span>
                </>
            )}
        </div>
    );
}

export function MatchNode({ data }: NodeProps<MatchNode>) {
    const { match, active } = data;
    const kickoff = new Date(match.kickoffAt);
    const date = kickoff.toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
    });

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
            <div className="flex items-center justify-between bg-wc-surface-2 px-2.5 py-1">
                <span className="font-mono text-[10px] font-semibold tracking-wider text-wc-ink/70">
                    {match.code}
                </span>
                <span className="font-mono text-[10px] tracking-wider text-muted-foreground">
                    {date}
                </span>
            </div>
            <SlotRow slot={match.home} divider={false} />
            <SlotRow slot={match.away} divider />
            <Handle
                type="source"
                position={Position.Right}
                className="!size-1.5 !border-0 !bg-wc-ink-3"
            />
        </div>
    );
}
