import type { Edge, Node } from '@xyflow/react';
import type { GroupTable, KnockoutMatch } from './types';

export const GROUP_STEP = 224;
export const NODE_H = 84;

/** Vertical gap between the two R32 nodes in one bracket pair. */
const PAIR_GAP = 16;

/** Vertical gap between adjacent R16 subtrees. */
const BLOCK_GAP = 40;

/** Minimum gap between node boxes in the same column. */
const COLUMN_GAP = 24;

export const COLUMN_X: Record<string, number> = {
    group: 0,
    r32: 360,
    r16: 720,
    qf: 1080,
    sf: 1440,
    final: 1800,
    trophy: 2128,
};

function prefersReducedMotion(): boolean {
    return (
        typeof window !== 'undefined' &&
        window.matchMedia('(prefers-reduced-motion: reduce)').matches
    );
}

function kickoffMs(match: KnockoutMatch): number {
    return Date.parse(match.kickoffAt);
}

function compareByKickoff(
    left: KnockoutMatch,
    right: KnockoutMatch,
): number {
    const difference = kickoffMs(left) - kickoffMs(right);

    return difference !== 0 ? difference : left.id - right.id;
}

function feederMatchesOrdered(
    feeders: [number, number],
    byId: Map<number, KnockoutMatch>,
): [KnockoutMatch, KnockoutMatch] | null {
    const first = byId.get(feeders[0]);
    const second = byId.get(feeders[1]);

    if (first === undefined || second === undefined) {
        return null;
    }

    return compareByKickoff(first, second) <= 0
        ? [first, second]
        : [second, first];
}

function subtreeKickoff(
    parent: KnockoutMatch & { feeders: [number, number] },
    childById: Map<number, KnockoutMatch>,
): number {
    const feeders = feederMatchesOrdered(parent.feeders, childById);

    if (feeders === null) {
        return kickoffMs(parent);
    }

    return kickoffMs(feeders[0]);
}

function nodeCenterY(
    positions: Map<string, { x: number; y: number }>,
    nodeId: string,
): number | null {
    const position = positions.get(nodeId);

    return position !== undefined ? position.y + NODE_H / 2 : null;
}

function yBetweenNodes(topY: number, bottomY: number): number {
    const topCenter = topY + NODE_H / 2;
    const bottomCenter = bottomY + NODE_H / 2;

    return (topCenter + bottomCenter) / 2 - NODE_H / 2;
}

function yFromFeeders(
    positions: Map<string, { x: number; y: number }>,
    feeders: [number, number],
): number | null {
    const homeCenter = nodeCenterY(positions, `m${feeders[0]}`);
    const awayCenter = nodeCenterY(positions, `m${feeders[1]}`);

    if (homeCenter === null || awayCenter === null) {
        return null;
    }

    return (homeCenter + awayCenter) / 2 - NODE_H / 2;
}

function matchesByStage(
    knockout: KnockoutMatch[],
): Record<string, KnockoutMatch[]> {
    const byStage: Record<string, KnockoutMatch[]> = {};

    for (const match of knockout) {
        (byStage[match.stage] ??= []).push(match);
    }

    return byStage;
}

function placeMatchNode(
    nodes: Node[],
    positions: Map<string, { x: number; y: number }>,
    match: KnockoutMatch,
    x: number,
    y: number,
    focusNodeId: string | null,
): void {
    const id = `m${match.id}`;

    positions.set(id, { x, y });
    nodes.push({
        id,
        type: 'match',
        position: { x, y },
        data: { match, active: id === focusNodeId },
        draggable: false,
    });
}

function resolveColumnOverlaps(
    positions: Map<string, { x: number; y: number }>,
    nodeIds: string[],
): void {
    if (nodeIds.length < 2) {
        return;
    }

    const sorted = nodeIds
        .map((id) => ({ id, y: positions.get(id)?.y ?? 0 }))
        .sort((a, b) => a.y - b.y);

    for (let index = 1; index < sorted.length; index++) {
        const previous = sorted[index - 1];
        const current = sorted[index];
        const minimumY = previous.y + NODE_H + COLUMN_GAP;

        if (current.y < minimumY) {
            const shift = minimumY - current.y;

            for (let offset = index; offset < sorted.length; offset++) {
                sorted[offset].y += shift;
                const position = positions.get(sorted[offset].id);

                if (position !== undefined) {
                    position.y += shift;
                }
            }
        }
    }
}

function layoutRoundOfSixteenSubtrees(
    nodes: Node[],
    positions: Map<string, { x: number; y: number }>,
    r32ById: Map<number, KnockoutMatch>,
    r16Matches: KnockoutMatch[],
    focusNodeId: string | null,
): number {
    const subtrees = r16Matches
        .filter((match): match is KnockoutMatch & { feeders: [number, number] } =>
            match.feeders !== null,
        )
        .sort(
            (left, right) =>
                subtreeKickoff(left, r32ById) - subtreeKickoff(right, r32ById),
        );

    let cursorY = 0;

    for (const r16 of subtrees) {
        const orderedFeeders = feederMatchesOrdered(r16.feeders, r32ById);

        if (orderedFeeders === null) {
            continue;
        }

        const [topMatch, bottomMatch] = orderedFeeders;
        const topY = cursorY;
        const bottomY = topY + NODE_H + PAIR_GAP;
        const r16Y = yBetweenNodes(topY, bottomY);

        placeMatchNode(
            nodes,
            positions,
            topMatch,
            COLUMN_X.r32,
            topY,
            focusNodeId,
        );
        placeMatchNode(
            nodes,
            positions,
            bottomMatch,
            COLUMN_X.r32,
            bottomY,
            focusNodeId,
        );
        placeMatchNode(
            nodes,
            positions,
            r16,
            COLUMN_X.r16,
            r16Y,
            focusNodeId,
        );

        cursorY = bottomY + NODE_H + BLOCK_GAP;
    }

    return cursorY > 0 ? cursorY - BLOCK_GAP : 0;
}

function placeStageFromFeeders(
    nodes: Node[],
    positions: Map<string, { x: number; y: number }>,
    matches: KnockoutMatch[],
    x: number,
    focusNodeId: string | null,
): string[] {
    const placedIds: string[] = [];

    const sortedMatches = matches
        .filter(
            (match): match is KnockoutMatch & { feeders: [number, number] } =>
                match.feeders !== null,
        )
        .sort(compareByKickoff);

    for (const match of sortedMatches) {
        const y = yFromFeeders(positions, match.feeders);

        if (y === null) {
            continue;
        }

        placeMatchNode(nodes, positions, match, x, y, focusNodeId);
        placedIds.push(`m${match.id}`);
    }

    resolveColumnOverlaps(positions, placedIds);

    return placedIds;
}

export function buildGraph(
    groups: GroupTable[],
    knockout: KnockoutMatch[],
    focusNodeId: string | null,
): { nodes: Node[]; edges: Edge[] } {
    const nodes: Node[] = [];
    const edges: Edge[] = [];
    const positions = new Map<string, { x: number; y: number }>();
    const reduce = prefersReducedMotion();
    const byStage = matchesByStage(knockout);
    const r32Matches = byStage.r32 ?? [];
    const r32ById = new Map(r32Matches.map((match) => [match.id, match]));

    layoutRoundOfSixteenSubtrees(
        nodes,
        positions,
        r32ById,
        byStage.r16 ?? [],
        focusNodeId,
    );

    placeStageFromFeeders(
        nodes,
        positions,
        byStage.qf ?? [],
        COLUMN_X.qf,
        focusNodeId,
    );
    placeStageFromFeeders(
        nodes,
        positions,
        byStage.sf ?? [],
        COLUMN_X.sf,
        focusNodeId,
    );

    const final = (byStage.final ?? [])[0];
    const third = (byStage.third_place ?? [])[0];
    const knockoutHeight = Math.max(
        ...Array.from(positions.values()).map((position) => position.y + NODE_H),
        0,
    );
    const groupHeight = groups.length * GROUP_STEP;
    const totalHeight = Math.max(groupHeight, knockoutHeight, 1);

    groups.forEach((group, index) => {
        const id = `g${group.code}`;

        nodes.push({
            id,
            type: 'group',
            position: { x: COLUMN_X.group, y: index * GROUP_STEP },
            data: { group, active: id === focusNodeId },
            draggable: false,
        });
    });

    if (final) {
        const finalY =
            final.feeders !== null
                ? (yFromFeeders(positions, final.feeders) ??
                  totalHeight / 2 - NODE_H / 2)
                : totalHeight / 2 - NODE_H / 2;

        placeMatchNode(
            nodes,
            positions,
            final,
            COLUMN_X.final,
            finalY,
            focusNodeId,
        );

        nodes.push({
            id: 'trophy',
            type: 'trophy',
            position: { x: COLUMN_X.trophy, y: finalY + 14 },
            data: { label: 'Final' },
            draggable: false,
        });

        edges.push({
            id: 'e-final-trophy',
            source: `m${final.id}`,
            target: 'trophy',
            type: 'smoothstep',
            animated: !reduce,
            style: { stroke: '#E9A721', strokeWidth: 2 },
        });
    }

    if (third) {
        const finalY = positions.get(`m${final?.id}`)?.y;
        const thirdY =
            finalY !== undefined
                ? finalY + NODE_H + 80
                : totalHeight / 2 + 200 - NODE_H / 2;

        placeMatchNode(
            nodes,
            positions,
            third,
            COLUMN_X.final,
            thirdY,
            focusNodeId,
        );
    }

    for (const match of knockout) {
        if (match.feeders === null) {
            continue;
        }

        const isThird = match.stage === 'third_place';

        for (const feeder of match.feeders) {
            edges.push({
                id: `e-${feeder}-${match.id}`,
                source: `m${feeder}`,
                target: `m${match.id}`,
                type: 'smoothstep',
                animated: false,
                style: isThird
                    ? {
                          stroke: 'rgba(10,10,11,0.12)',
                          strokeDasharray: '4 4',
                      }
                    : { stroke: 'rgba(10,10,11,0.2)' },
            });
        }
    }

    return { nodes, edges };
}
