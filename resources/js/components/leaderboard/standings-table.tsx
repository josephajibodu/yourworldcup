import { Star } from 'lucide-react';
import { cn } from '@/lib/utils';

export interface StandingsRow {
    userId: number;
    name: string;
    points: number;
    rank: number;
}

interface StandingsTableProps {
    rows: StandingsRow[];
    currentUserId: number | null;
    emptyMessage: string;
}

function rankAccent(rank: number): string {
    if (rank === 1) {
        return 'bg-wc-gold text-wc-ink';
    }

    if (rank <= 3) {
        return 'bg-wc-gold/20 text-wc-gold-deep';
    }

    return 'bg-secondary text-muted-foreground';
}

export function StandingsTable({
    rows,
    currentUserId,
    emptyMessage,
}: StandingsTableProps) {
    if (rows.length === 0) {
        return (
            <div className="rounded-xl border border-dashed bg-card px-6 py-12 text-center text-sm text-muted-foreground">
                {emptyMessage}
            </div>
        );
    }

    return (
        <ul className="overflow-hidden rounded-xl border bg-card">
            {rows.map((row) => {
                const isCurrent = row.userId === currentUserId;

                return (
                    <li
                        key={row.userId}
                        className={cn(
                            'flex items-center gap-3 border-b px-3 py-2.5 last:border-b-0',
                            isCurrent && 'bg-wc-primary/5',
                        )}
                    >
                        <span
                            className={cn(
                                'grid size-7 shrink-0 place-items-center rounded-md font-mono text-xs font-semibold tabular-nums',
                                rankAccent(row.rank),
                            )}
                        >
                            {row.rank}
                        </span>
                        {row.rank === 1 && (
                            <Star className="size-4 shrink-0 fill-wc-gold text-wc-gold" />
                        )}
                        <span className="flex-1 truncate text-sm font-medium">
                            {row.name}
                            {isCurrent && (
                                <span className="ml-2 rounded bg-wc-primary px-1.5 py-0.5 font-mono text-[10px] font-semibold tracking-wider text-white uppercase">
                                    You
                                </span>
                            )}
                        </span>
                        <span className="font-mono text-sm font-semibold tabular-nums">
                            {row.points}
                            <span className="ml-1 text-[10px] text-muted-foreground">
                                PTS
                            </span>
                        </span>
                    </li>
                );
            })}
        </ul>
    );
}
