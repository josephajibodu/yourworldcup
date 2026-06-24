export const ogImage = '/og-image.png';

export const siteDescription =
    'Daily World Cup predictions, a living bracket, and skill-ranked leaderboards. Pick winners, call exact scores, and climb the daily board.';

export const seo = {
    home: {
        title: 'Predict the World Cup, daily',
        description:
            'Pick match winners and exact scores every day. Follow a living bracket, track live results, and climb daily and overall leaderboards.',
        path: '/',
    },
    predict: {
        title: 'Make your picks',
        description:
            'Submit your daily World Cup predictions before kickoff. Choose match winners, exact scores, and a banker pick to double your points.',
        path: '/predict',
    },
    bracket: {
        title: 'Living bracket',
        description:
            'Follow the World Cup knockout bracket as it updates — from the round of 32 through to the final.',
        path: '/bracket',
    },
    leaderboard: {
        title: 'Leaderboard',
        description:
            'See who leads the daily and overall World Cup prediction rankings as results come in.',
        path: '/leaderboard',
    },
    referrals: {
        title: 'Referrals',
        description:
            'Invite friends to YourWorldCup and earn bonus leaderboard points when they sign up and make their first prediction.',
        path: '/referrals',
    },
} as const;

export const privatePageRobots = 'noindex, nofollow';
