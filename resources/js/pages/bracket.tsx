import { usePage } from '@inertiajs/react';
import {
    Background,
    BackgroundVariant,
    Controls,
    MiniMap,
    ReactFlow,
    useReactFlow,
} from '@xyflow/react';
import type { Edge, Node } from '@xyflow/react';
import '@xyflow/react/dist/style.css';
import { useEffect, useMemo } from 'react';
import { GroupTableNode } from '@/components/bracket/group-table-node';
import { MatchNode } from '@/components/bracket/match-node';
import { TrophyNode } from '@/components/bracket/trophy-node';
import type { GroupTable, KnockoutMatch } from '@/components/bracket/types';
import { ProductShell } from '@/components/product-shell';
import { ThirdPlaceRankingFloatingLink } from '@/components/third-place-ranking-floating-link';
import { SeoHead } from '@/components/seo-head';
import { seo } from '@/lib/seo';

interface BracketPageProps {
    groups: GroupTable[];
    knockout: KnockoutMatch[];
    focusNodeId: string | null;
    [key: string]: unknown;
}

const nodeTypes = {
    group: GroupTableNode,
    match: MatchNode,
    trophy: TrophyNode,
};

const GROUP_STEP = 224;
const NODE_H = 84;

const COLUMN_X: Record<string, number> = {
    group: 0,
    r32: 360,
    r16: 720,
    qf: 1080,
    sf: 1440,
    final: 1800,
    trophy: 2128,
};

const prefersReducedMotion = () =>
    typeof window !== 'undefined' &&
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

function buildGraph(
    groups: GroupTable[],
    knockout: KnockoutMatch[],
    focusNodeId: string | null,
) {
    const totalHeight = Math.max(groups.length * GROUP_STEP, 1);
    const nodes: Node[] = [];
    const edges: Edge[] = [];
    const reduce = prefersReducedMotion();

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

    const byStage: Record<string, KnockoutMatch[]> = {};

    for (const match of knockout) {
        (byStage[match.stage] ??= []).push(match);
    }

    const centerY = (i: number, count: number) =>
        (totalHeight * (i + 0.5)) / count - NODE_H / 2;

    const placeStage = (stage: string, x: number) => {
        const matches = byStage[stage] ?? [];
        matches.forEach((match, i) => {
            const id = `m${match.id}`;

            nodes.push({
                id,
                type: 'match',
                position: { x, y: centerY(i, matches.length) },
                data: { match, active: id === focusNodeId },
                draggable: false,
            });
        });
    };

    placeStage('r32', COLUMN_X.r32);
    placeStage('r16', COLUMN_X.r16);
    placeStage('qf', COLUMN_X.qf);
    placeStage('sf', COLUMN_X.sf);

    const final = (byStage.final ?? [])[0];
    const third = (byStage.third_place ?? [])[0];

    if (final) {
        const finalId = `m${final.id}`;

        nodes.push({
            id: finalId,
            type: 'match',
            position: { x: COLUMN_X.final, y: totalHeight / 2 - NODE_H / 2 },
            data: { match: final, active: finalId === focusNodeId },
            draggable: false,
        });
        nodes.push({
            id: 'trophy',
            type: 'trophy',
            position: { x: COLUMN_X.trophy, y: totalHeight / 2 - 70 },
            data: { label: 'Final' },
            draggable: false,
        });
        edges.push({
            id: `e-final-trophy`,
            source: finalId,
            target: 'trophy',
            type: 'smoothstep',
            animated: !reduce,
            style: { stroke: '#E9A721', strokeWidth: 2 },
        });
    }

    if (third) {
        const thirdId = `m${third.id}`;

        nodes.push({
            id: thirdId,
            type: 'match',
            position: { x: COLUMN_X.final, y: totalHeight / 2 + 200 },
            data: { match: third, active: thirdId === focusNodeId },
            draggable: false,
        });
    }

    for (const match of knockout) {
        if (!match.feeders) {
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

function BracketViewport({ focusNodeId }: { focusNodeId: string | null }) {
    const { fitView } = useReactFlow();

    useEffect(() => {
        const frame = requestAnimationFrame(() => {
            if (focusNodeId) {
                fitView({
                    nodes: [{ id: focusNodeId }],
                    maxZoom: 0.82,
                    padding: 0.55,
                    duration: prefersReducedMotion() ? 0 : 700,
                });

                return;
            }

            fitView({ padding: 0.15, duration: 0 });
        });

        return () => cancelAnimationFrame(frame);
    }, [focusNodeId, fitView]);

    return null;
}

export default function Bracket() {
    const { groups, knockout, focusNodeId } =
        usePage<BracketPageProps>().props;

    const { nodes, edges } = useMemo(
        () => buildGraph(groups, knockout, focusNodeId),
        [groups, knockout, focusNodeId],
    );

    return (
        <>
            <SeoHead {...seo.bracket} />
            <ThirdPlaceRankingFloatingLink className="top-34 sm:top-24" />
            <ProductShell
                className="bg-wc-surface text-wc-ink"
                mainClassName="relative min-h-[calc(100svh-11rem)] flex-1"
            >
                <div className="absolute inset-0 min-h-[480px]">
                    <ReactFlow
                        nodes={nodes}
                        edges={edges}
                        nodeTypes={nodeTypes}
                        fitView={false}
                        minZoom={0.1}
                        maxZoom={1.5}
                        nodesDraggable={false}
                        nodesConnectable={false}
                        elementsSelectable={false}
                        proOptions={{ hideAttribution: false }}
                    >
                        <Background
                            variant={BackgroundVariant.Dots}
                            gap={24}
                            size={1.5}
                            color="rgba(10,10,11,0.1)"
                        />
                        <BracketViewport focusNodeId={focusNodeId} />
                        <Controls
                            showInteractive={false}
                            className="border-wc-ink/10! bg-white/90! shadow-sm! [&_button]:border-wc-ink/10! [&_button]:bg-white! [&_button]:fill-wc-ink! [&_button:hover]:bg-wc-surface-2!"
                        />
                        <MiniMap
                            pannable
                            zoomable
                            className="hidden border border-wc-ink/10! bg-white/90! md:block"
                            maskColor="rgba(247,245,240,0.75)"
                            nodeColor="rgba(10,10,11,0.18)"
                        />
                    </ReactFlow>
                </div>
            </ProductShell>
        </>
    );
}
