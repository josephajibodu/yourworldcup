import Heading from '@/components/heading';
import { AdminLeaderboardNav } from '@/components/admin/leaderboard-scope-nav';
import { DateNav } from '@/components/leaderboard/date-nav';
import { StandingsTable } from '@/components/leaderboard/standings-table';
import type { StandingsRow } from '@/components/leaderboard/standings-table';
import { SeoHead } from '@/components/seo-head';
import { privatePageRobots } from '@/lib/seo';
import { leaderboard } from '@/routes/admin';
import { weekly } from '@/routes/admin/leaderboard';
import { dashboard } from '@/routes';

interface WeeklyLeaderboardProps {
    standings: StandingsRow[];
    dates: string[];
    selectedDate: string | null;
}

function formatWeek(weekStart: string): string {
    const start = new Date(`${weekStart}T12:00:00`);
    const end = new Date(start);
    end.setDate(end.getDate() + 6);
    const fmt = (d: Date) =>
        d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short' });

    return `${fmt(start)} – ${fmt(end)}`;
}

export default function AdminWeeklyLeaderboard({
    standings,
    dates,
    selectedDate,
}: WeeklyLeaderboardProps) {
    return (
        <>
            <SeoHead
                title="Weekly leaderboard"
                description="Weekly standings for site admins."
                path="/admin/leaderboard/weekly"
                robots={privatePageRobots}
            />
            <div className="flex flex-col gap-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <Heading
                        title="Leaderboard"
                        description="Weekly standings including prediction and referral points."
                    />
                    <AdminLeaderboardNav active="weekly" />
                </div>
                <div className="flex justify-end">
                    <DateNav
                        dates={dates}
                        selectedDate={selectedDate}
                        formatLabel={formatWeek}
                        hrefForDate={(date) => weekly({ query: { date } })}
                    />
                </div>
                <StandingsTable
                    rows={standings}
                    currentUserId={null}
                    emptyMessage="No picks or referrals this week yet."
                />
            </div>
        </>
    );
}

AdminWeeklyLeaderboard.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Leaderboard', href: leaderboard() },
    ],
};
