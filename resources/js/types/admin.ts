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
