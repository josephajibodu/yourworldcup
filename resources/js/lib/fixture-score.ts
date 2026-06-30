export type ResultDuration = 'regular' | 'extra_time' | 'penalties';

export interface FixtureScoreParts {
    homeScore: number | null;
    awayScore: number | null;
    extraTimeHome?: number | null;
    extraTimeAway?: number | null;
    penaltiesHome?: number | null;
    penaltiesAway?: number | null;
    resultDuration?: ResultDuration | null;
    scoreLabel?: string | null;
}

export function fixtureHasPenaltyScore(parts: FixtureScoreParts): boolean {
    return (
        parts.penaltiesHome !== null &&
        parts.penaltiesHome !== undefined &&
        parts.penaltiesAway !== null &&
        parts.penaltiesAway !== undefined
    );
}

export function formatFixtureSideScore(
    regularScore: number,
    extraTimeScore: number | null | undefined,
    penaltyScore: number | null | undefined,
    hasPenalties: boolean,
): string {
    if (hasPenalties && penaltyScore !== null && penaltyScore !== undefined) {
        return `${regularScore}(${penaltyScore})`;
    }

    return String(regularScore + (extraTimeScore ?? 0));
}

export function formatFixtureCenterScore(
    parts: FixtureScoreParts,
): string | null {
    if (parts.homeScore === null || parts.awayScore === null) {
        return null;
    }

    const hasPenalties = fixtureHasPenaltyScore(parts);

    const home = formatFixtureSideScore(
        parts.homeScore,
        parts.extraTimeHome,
        parts.penaltiesHome,
        hasPenalties,
    );
    const away = formatFixtureSideScore(
        parts.awayScore,
        parts.extraTimeAway,
        parts.penaltiesAway,
        hasPenalties,
    );

    return `${home} – ${away}`;
}

export function formatFixtureStatusLabel(
    parts: FixtureScoreParts,
    final: boolean,
): string | null {
    if (!final) {
        return null;
    }

    return parts.scoreLabel ?? 'FT';
}
