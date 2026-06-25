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
    };
};
