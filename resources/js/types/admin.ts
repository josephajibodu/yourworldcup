export type AdminUserSummary = {
    id: number;
    name: string;
    email: string;
    emailVerifiedAt: string | null;
    isSiteAdmin: boolean;
    predictionsCount: number;
    referralsCount: number;
    createdAt: string;
};

export type AdminUserDetail = AdminUserSummary & {
    twoFactorEnabled: boolean;
    referralCode: string | null;
    referrer: {
        id: number;
        name: string;
        email: string;
    } | null;
    updatedAt: string;
};

export type AdminUserPrediction = {
    id: number;
    isBanker: boolean;
    submittedAt: string;
    isScored: boolean;
    outcome: 'won' | 'lost' | null;
    pointsAwarded: number | null;
    marketName: string;
    value: Record<string, unknown>;
    fixture: {
        id: number;
        stageLabel: string;
        status: string;
        kickoffAt: string;
        homeTeam: string | null;
        awayTeam: string | null;
        homeScore: number | null;
        awayScore: number | null;
        extraTimeHome?: number | null;
        extraTimeAway?: number | null;
        penaltiesHome?: number | null;
        penaltiesAway?: number | null;
        resultDuration?: 'regular' | 'extra_time' | 'penalties' | null;
        scoreLabel?: string | null;
    };
};

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
};

export type AdminFixtureSummary = {
    id: number;
    externalId: string | null;
    stageLabel: string;
    groupCode: string | null;
    status: string;
    kickoffAt: string;
    watDate: string;
    homeTeam: string | null;
    awayTeam: string | null;
    stadium: {
        id: number;
        name: string;
        city: string | null;
    } | null;
    referee: string | null;
    homeScore: number | null;
    awayScore: number | null;
    extraTimeHome: number | null;
    extraTimeAway: number | null;
    penaltiesHome: number | null;
    penaltiesAway: number | null;
    resultDuration: 'regular' | 'extra_time' | 'penalties' | null;
    lastGoal: 'home' | 'none' | 'away' | null;
    highestBooking: 'home' | 'draw' | 'away' | null;
    scoreLabel: string | null;
    marketsCount: number;
    settledMarketsCount: number;
};

export type AdminFilterOption = {
    id: number;
    name: string;
    city?: string | null;
};

export type AdminBracketSlotTeamOption = {
    id: number;
    name: string;
    code: string;
    flag: string | null;
    groupCode: string | null;
    standingPosition: number | null;
};

export type AdminBracketSlotSummary = {
    id: number;
    label: string | null;
    displayCode: string;
    slotType: string;
    side: string;
    feedsFixture: {
        id: number;
        externalId: string | null;
        stageLabel: string;
    } | null;
    resolvedTeam: {
        id: number;
        name: string;
        code: string;
        flag: string | null;
        groupCode: string | null;
    } | null;
    eligibleTeams: AdminBracketSlotTeamOption[];
};

export type AdminDashboardFixture = {
    id: number;
    externalId: string | null;
    stageLabel: string;
    groupCode: string | null;
    status: string;
    kickoffAt: string;
    homeTeam: string | null;
    awayTeam: string | null;
    homeScore: number | null;
    awayScore: number | null;
    unsettledMarketsCount?: number;
    marketsCount?: number;
};

export type AdminDashboardBracketSlot = {
    id: number;
    label: string | null;
    displayCode: string;
    feedsFixtureExternalId: string | null;
    feedsFixtureStageLabel: string | null;
};

export type AdminRewardClaimSummary = {
    id: number;
    weekStart: string;
    leaderboardRank: number;
    passedOn: boolean;
    preference: 'airtime' | 'data' | 'cash' | null;
    preferenceLabel: string | null;
    phoneNumber: string | null;
    mobileNetwork: string | null;
    mobileNetworkLabel: string | null;
    accountHolderName: string | null;
    bankName: string | null;
    accountNumber: string | null;
    passOnMessage: string | null;
    submittedAt: string | null;
    user: {
        id: number;
        name: string;
        email: string;
    };
};

export type AdminRewardClaimsSummary = {
    total: number;
    passed: number;
    claimed: number;
    airtime: number;
    data: number;
    cash: number;
};

export type AdminPendingPassOnSummary = {
    userId: number;
    rank: number;
    name: string;
    email: string;
};

export type AdminDashboardSummary = {
    stats: {
        users: {
            total: number;
            verified: number;
            withPredictions: number;
            grandPrizeTarget: number;
        };
        predictions: {
            total: number;
            unscored: number;
        };
        referrals: {
            total: number;
        };
        fixtures: {
            total: number;
            live: number;
            today: number;
            final: number;
            unsettledFinal: number;
        };
        bracketSlots: {
            total: number;
            assigned: number;
        };
        tournament: {
            allGroupsComplete: boolean;
        };
    };
    leaderboardTop: Array<{
        userId: number;
        name: string;
        points: number;
        rank: number;
    }>;
    liveFixtures: AdminDashboardFixture[];
    upcomingFixtures: AdminDashboardFixture[];
    fixturesNeedingSettlement: AdminDashboardFixture[];
    unassignedBracketSlots: AdminDashboardBracketSlot[];
};
