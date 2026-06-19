import type { PredictFixture } from '@/components/predict/types';

export function savedMarketIdsFromFixtures(
    fixtures: PredictFixture[],
): number[] {
    return fixtures.flatMap((fixture) =>
        fixture.markets
            .filter((market) => market.value !== null)
            .map((market) => market.id),
    );
}

export function hasSavedPicksForDay(fixtures: PredictFixture[]): boolean {
    return savedMarketIdsFromFixtures(fixtures).length > 0;
}
