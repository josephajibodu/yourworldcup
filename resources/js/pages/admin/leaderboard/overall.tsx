import Heading from '@/components/heading';
import { AdminLeaderboardNav } from '@/components/admin/leaderboard-scope-nav';
import { StandingsTable } from '@/components/leaderboard/standings-table';
import type { StandingsRow } from '@/components/leaderboard/standings-table';
import { SeoHead } from '@/components/seo-head';
import { privatePageRobots } from '@/lib/seo';
import { leaderboard } from '@/routes/admin';
import { dashboard } from '@/routes';

interface OverallLeaderboardProps {
    standings: StandingsRow[];
}

export default function AdminOverallLeaderboard({
    standings,
}: OverallLeaderboardProps) {
    return (
        <>
            <SeoHead
                title="Leaderboard"
                description="Overall tournament standings for site admins."
                path="/admin/leaderboard"
                robots={privatePageRobots}
            />
            <div className="flex flex-col gap-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <Heading
                        title="Leaderboard"
                        description="Overall standings including prediction and referral points."
                    />
                    <AdminLeaderboardNav active="overall" />
                </div>
                <StandingsTable
                    rows={standings}
                    currentUserId={null}
                    emptyMessage="No participants yet."
                />
            </div>
        </>
    );
}

AdminOverallLeaderboard.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Leaderboard', href: leaderboard() },
    ],
};
