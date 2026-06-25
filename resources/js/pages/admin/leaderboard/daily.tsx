import Heading from '@/components/heading';
import { AdminLeaderboardNav } from '@/components/admin/leaderboard-scope-nav';
import { LeaderboardDayPicker } from '@/components/admin/leaderboard-day-picker';
import { StandingsTable } from '@/components/leaderboard/standings-table';
import type { StandingsRow } from '@/components/leaderboard/standings-table';
import { SeoHead } from '@/components/seo-head';
import { privatePageRobots } from '@/lib/seo';
import { leaderboard } from '@/routes/admin';
import { dashboard } from '@/routes';

interface DailyLeaderboardProps {
    standings: StandingsRow[];
    dates: string[];
    selectedDate: string | null;
    dateRange: {
        start: string;
        end: string;
    };
}

export default function AdminDailyLeaderboard({
    standings,
    selectedDate,
    dateRange,
}: DailyLeaderboardProps) {
    return (
        <>
            <SeoHead
                title="Daily leaderboard"
                description="Daily standings for site admins."
                path="/admin/leaderboard/daily"
                robots={privatePageRobots}
            />
            <div className="flex flex-col gap-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <Heading
                        title="Leaderboard"
                        description="Daily standings including prediction and referral points."
                    />
                    <AdminLeaderboardNav active="daily" />
                </div>
                <LeaderboardDayPicker
                    selectedDate={selectedDate}
                    rangeStart={dateRange.start}
                    rangeEnd={dateRange.end}
                />
                <StandingsTable
                    rows={standings}
                    currentUserId={null}
                    emptyMessage="No picks or referrals this day yet."
                />
            </div>
        </>
    );
}

AdminDailyLeaderboard.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Leaderboard', href: leaderboard() },
    ],
};
