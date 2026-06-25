import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import { leaderboard } from '@/routes/admin';
import { daily, weekly } from '@/routes/admin/leaderboard';

type LeaderboardScope = 'overall' | 'weekly' | 'daily';

const tabs: {
    scope: LeaderboardScope;
    label: string;
    href: ReturnType<typeof leaderboard>;
}[] = [
    { scope: 'overall', label: 'Overall', href: leaderboard() },
    { scope: 'weekly', label: 'Weekly', href: weekly() },
    { scope: 'daily', label: 'Daily', href: daily() },
];

interface AdminLeaderboardNavProps {
    active: LeaderboardScope;
}

export function AdminLeaderboardNav({ active }: AdminLeaderboardNavProps) {
    return (
        <nav
            aria-label="Leaderboard scope"
            className="flex w-full max-w-md gap-1 rounded-xl border p-1"
        >
            {tabs.map((tab) => (
                <Link
                    key={tab.scope}
                    href={tab.href}
                    prefetch
                    className={cn(
                        'flex-1 rounded-lg px-3 py-2 text-center text-sm font-medium transition-colors',
                        active === tab.scope
                            ? 'bg-primary text-primary-foreground'
                            : 'text-muted-foreground hover:text-foreground',
                    )}
                >
                    {tab.label}
                </Link>
            ))}
        </nav>
    );
}
