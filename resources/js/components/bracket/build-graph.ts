import type { Edge, Node } from '@xyflow/react';
import type { BracketEdgeData } from './bracket-edge';
import type { GroupTable, KnockoutMatch } from './types';
import { TROPHY_SIZE } from './trophy-node';
import {
    isShowpieceStage,
    SHOWPIECE_CONNECTOR_Y,
    SHOWPIECE_NODE_H,
    SHOWPIECE_NODE_W,
} from '@/lib/showpiece-match';

export const GROUP_STEP = 224;
export const NODE_H = 84;
export const NODE_W = 208;

/** Vertical gap between two feeder matches in a pair. */
const PAIR_GAP = 32;

/** Gap between the two R16 mini-blocks inside one QF region. */
const R16_STACK_GAP = 36;

/** Gap between the two QF regions inside one SF block. */
const QF_STACK_GAP = 52;

/** Gap between the two SF half-brackets. */
const HALF_BRACKET_GAP = 72;

/** Padding below the final before the third-place play-off. */
const THIRD_PLACE_GAP = 96;

const COLUMN_STEP = 240;

/** Width of the group standings node (`group-table-node.tsx`). */
export const GROUP_TABLE_W = 260;

/** Horizontal gap between group tables and the knockout bracket. */
const GROUP_BRACKET_GAP = 96;

const BRACKET_ORIGIN = GROUP_TABLE_W + GROUP_BRACKET_GAP;

const TROPHY_GAP = 28;

export const COLUMN_X: Record<string, number> = {
    group: 0,
    r32: BRACKET_ORIGIN,
    r16: BRACKET_ORIGIN + COLUMN_STEP,
    qf: BRACKET_ORIGIN + COLUMN_STEP * 2,
    sf: BRACKET_ORIGIN + COLUMN_STEP * 3,
    final: BRACKET_ORIGIN + COLUMN_STEP * 4,
    trophy: BRACKET_ORIGIN + COLUMN_STEP * 4 + SHOWPIECE_NODE_W + TROPHY_GAP,
};

interface LayoutContext {
    nodes: Node[];
    positions: Map<string, { x: number; y: number }>;
    focusNodeId: string | null;
    r32ById: Map<number, KnockoutMatch>;
    r16ById: Map<number, KnockoutMatch>;
    qfById: Map<number, KnockoutMatch>;
}

interface SubtreeBounds {
    topY: number;
    bottomY: number;
    anchorY: number;
}

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

function yBetweenNodes(topY: number, bottomY: number): number {
    const topCenter = topY + NODE_H / 2;
    const bottomCenter = bottomY + NODE_H / 2;

    return (topCenter + bottomCenter) / 2 - NODE_H / 2;
}

function yCenteredBetween(
    topY: number,
    topHeight: number,
    bottomY: number,
    bottomHeight: number,
    parentHeight: number,
): number {
    const topCenter = topY + topHeight / 2;
    const bottomCenter = bottomY + bottomHeight / 2;

    return (topCenter + bottomCenter) / 2 - parentHeight / 2;
}

function nodeHeight(match: KnockoutMatch): number {
    return isShowpieceStage(match.stage) ? SHOWPIECE_NODE_H : NODE_H;
}

function yFromFeeders(
    positions: Map<string, { x: number; y: number }>,
    feeders: [number, number],
): number | null {
    const top = positions.get(`m${feeders[0]}`);
    const bottom = positions.get(`m${feeders[1]}`);

    if (top === undefined || bottom === undefined) {
        return null;
    }

    return yBetweenNodes(top.y, bottom.y);
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
    ctx: LayoutContext,
    match: KnockoutMatch,
    x: number,
    y: number,
): void {
    const id = `m${match.id}`;

    ctx.positions.set(id, { x, y });

    const existing = ctx.nodes.find((node) => node.id === id);

    if (existing !== undefined) {
        existing.position = { x, y };

        return;
    }

    ctx.nodes.push({
        id,
        type: 'match',
        position: { x, y },
        data: { match, active: id === ctx.focusNodeId },
        draggable: false,
    });
}

function layoutR16MiniSubtree(
    ctx: LayoutContext,
    r16: KnockoutMatch & { feeders: [number, number] },
    startY: number,
): SubtreeBounds {
    const orderedFeeders = feederMatchesOrdered(r16.feeders, ctx.r32ById);

    if (orderedFeeders === null) {
        return { topY: startY, bottomY: startY, anchorY: startY };
    }

    const [topR32, bottomR32] = orderedFeeders;
    const topY = startY;
    const bottomY = topY + NODE_H + PAIR_GAP;
    const r16Y = yBetweenNodes(topY, bottomY);

    placeMatchNode(ctx, topR32, COLUMN_X.r32, topY);
    placeMatchNode(ctx, bottomR32, COLUMN_X.r32, bottomY);
    placeMatchNode(ctx, r16, COLUMN_X.r16, r16Y);

    return {
        topY,
        bottomY: bottomY + NODE_H,
        anchorY: r16Y,
    };
}

function layoutQfRegion(
    ctx: LayoutContext,
    qf: KnockoutMatch & { feeders: [number, number] },
    startY: number,
): SubtreeBounds {
    const orderedFeeders = feederMatchesOrdered(qf.feeders, ctx.r16ById);

    if (orderedFeeders === null) {
        return { topY: startY, bottomY: startY, anchorY: startY };
    }

    const [topR16, bottomR16] = orderedFeeders;
    const firstBlock = layoutR16MiniSubtree(
        ctx,
        topR16 as KnockoutMatch & { feeders: [number, number] },
        startY,
    );
    const secondBlock = layoutR16MiniSubtree(
        ctx,
        bottomR16 as KnockoutMatch & { feeders: [number, number] },
        firstBlock.bottomY + R16_STACK_GAP,
    );
    const qfY = yBetweenNodes(
        ctx.positions.get(`m${topR16.id}`)!.y,
        ctx.positions.get(`m${bottomR16.id}`)!.y,
    );

    placeMatchNode(ctx, qf, COLUMN_X.qf, qfY);

    return {
        topY: startY,
        bottomY: secondBlock.bottomY,
        anchorY: qfY,
    };
}

function layoutSfBlock(
    ctx: LayoutContext,
    sf: KnockoutMatch & { feeders: [number, number] },
    startY: number,
): SubtreeBounds {
    const orderedFeeders = feederMatchesOrdered(sf.feeders, ctx.qfById);

    if (orderedFeeders === null) {
        return { topY: startY, bottomY: startY, anchorY: startY };
    }

    const [topQf, bottomQf] = orderedFeeders;
    const firstRegion = layoutQfRegion(
        ctx,
        topQf as KnockoutMatch & { feeders: [number, number] },
        startY,
    );
    const secondRegion = layoutQfRegion(
        ctx,
        bottomQf as KnockoutMatch & { feeders: [number, number] },
        firstRegion.bottomY + QF_STACK_GAP,
    );
    const sfY = yBetweenNodes(
        ctx.positions.get(`m${topQf.id}`)!.y,
        ctx.positions.get(`m${bottomQf.id}`)!.y,
    );

    placeMatchNode(ctx, sf, COLUMN_X.sf, sfY);

    return {
        topY: startY,
        bottomY: secondRegion.bottomY,
        anchorY: sfY,
    };
}

function laneXForTarget(
    sourceColumn: number,
    targetColumn: number,
    targetMatchId: number,
    targetIds: number[],
): number {
    const sourceEdge = sourceColumn + NODE_W;
    const span = targetColumn - sourceEdge;
    const sortedIds = [...targetIds].sort((left, right) => left - right);
    const laneIndex = sortedIds.indexOf(targetMatchId);
    const laneCount = Math.max(sortedIds.length, 1);
    const padding = Math.min(56, span * 0.18);
    const usable = span - padding * 2;
    const laneSpacing = laneCount > 1 ? usable / (laneCount - 1) : 0;

    return sourceEdge + padding + laneIndex * laneSpacing;
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
    const r16Matches = byStage.r16 ?? [];
    const qfMatches = byStage.qf ?? [];
    const sfMatches = byStage.sf ?? [];

    const ctx: LayoutContext = {
        nodes,
        positions,
        focusNodeId,
        r32ById: new Map(r32Matches.map((match) => [match.id, match])),
        r16ById: new Map(r16Matches.map((match) => [match.id, match])),
        qfById: new Map(qfMatches.map((match) => [match.id, match])),
    };

    const semiBlocks = sfMatches
        .filter((match): match is KnockoutMatch & { feeders: [number, number] } =>
            match.feeders !== null,
        )
        .sort(
            (left, right) =>
                subtreeKickoff(left, ctx.qfById) -
                subtreeKickoff(right, ctx.qfById),
        );

    let cursorY = 0;

    for (const semi of semiBlocks) {
        const bounds = layoutSfBlock(ctx, semi, cursorY);

        cursorY = bounds.bottomY + HALF_BRACKET_GAP;
    }

    const final = (byStage.final ?? [])[0];
    const third = (byStage.third_place ?? [])[0];
    const knockoutHeight = Math.max(
        ...Array.from(positions.entries()).map(([id, position]) => {
            const match = knockout.find((entry) => `m${entry.id}` === id);

            return position.y + (match ? nodeHeight(match) : NODE_H);
        }),
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
                ? (() => {
                      const top = positions.get(`m${final.feeders[0]}`);
                      const bottom = positions.get(`m${final.feeders[1]}`);

                      if (top === undefined || bottom === undefined) {
                          return totalHeight / 2 - SHOWPIECE_NODE_H / 2;
                      }

                      return yCenteredBetween(
                          top.y,
                          NODE_H,
                          bottom.y,
                          NODE_H,
                          SHOWPIECE_NODE_H,
                      );
                  })()
                : totalHeight / 2 - SHOWPIECE_NODE_H / 2;

        placeMatchNode(ctx, final, COLUMN_X.final, finalY);

        const connectorY = finalY + SHOWPIECE_CONNECTOR_Y;
        const trophyY = connectorY - TROPHY_SIZE / 2;

        nodes.push({
            id: 'trophy',
            type: 'trophy',
            position: { x: COLUMN_X.trophy, y: trophyY },
            data: { label: 'Final' },
            draggable: false,
        });

        edges.push({
            id: 'e-final-trophy',
            source: `m${final.id}`,
            target: 'trophy',
            type: 'bracket',
            data: {
                straight: true,
            } satisfies BracketEdgeData,
            animated: !reduce,
            style: { stroke: '#E9A721', strokeWidth: 2 },
        });
    }

    if (third) {
        const finalY = positions.get(`m${final?.id}`)?.y;
        const thirdY =
            finalY !== undefined
                ? finalY + SHOWPIECE_NODE_H + THIRD_PLACE_GAP
                : totalHeight / 2 + 200 - SHOWPIECE_NODE_H / 2;

        placeMatchNode(ctx, third, COLUMN_X.final, thirdY);
    }

    const r16Ids = r16Matches.map((match) => match.id);
    const qfIds = qfMatches.map((match) => match.id);
    const sfIds = sfMatches.map((match) => match.id);

    for (const match of knockout) {
        if (match.feeders === null) {
            continue;
        }

        const isThird = match.stage === 'third_place';
        let sourceColumn = COLUMN_X.r32;
        let targetColumn = COLUMN_X.r16;
        let targetIds = r16Ids;

        if (match.stage === 'qf') {
            sourceColumn = COLUMN_X.r16;
            targetColumn = COLUMN_X.qf;
            targetIds = qfIds;
        } else if (match.stage === 'sf') {
            sourceColumn = COLUMN_X.qf;
            targetColumn = COLUMN_X.sf;
            targetIds = sfIds;
        } else if (match.stage === 'final' || match.stage === 'third_place') {
            sourceColumn = COLUMN_X.sf;
            targetColumn = COLUMN_X.final;
            targetIds = [match.id];
        }

        for (const feeder of match.feeders) {
            edges.push({
                id: `e-${feeder}-${match.id}`,
                source: `m${feeder}`,
                target: `m${match.id}`,
                type: 'bracket',
                data: {
                    laneX: laneXForTarget(
                        sourceColumn,
                        targetColumn,
                        match.id,
                        targetIds,
                    ),
                } satisfies BracketEdgeData,
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
