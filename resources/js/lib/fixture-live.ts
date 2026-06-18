export function isFixtureFinal(status: string): boolean {
    return status === 'final';
}

export function isFixtureLive(
    status: string,
    kickoffAt: string,
    matchDurationMinutes: number,
    now = Date.now(),
): boolean {
    if (status === 'final' || status === 'void') {
        return false;
    }

    const kickoff = new Date(kickoffAt).getTime();
    const windowMs = matchDurationMinutes * 60 * 1000;

    if (kickoff <= now && kickoff + windowMs > now) {
        return true;
    }

    return status === 'live';
}

export interface FixtureTeamRef {
    kickoffAt: string;
    status: string;
    homeTeamId: number;
    awayTeamId: number;
}

export function liveTeamIds(
    fixtures: FixtureTeamRef[],
    matchDurationMinutes: number,
    now = Date.now(),
): Set<number> {
    const ids = new Set<number>();

    for (const fixture of fixtures) {
        if (
            isFixtureLive(
                fixture.status,
                fixture.kickoffAt,
                matchDurationMinutes,
                now,
            )
        ) {
            ids.add(fixture.homeTeamId);
            ids.add(fixture.awayTeamId);
        }
    }

    return ids;
}
