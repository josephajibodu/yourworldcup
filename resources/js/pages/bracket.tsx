import { Head, Link, usePage } from '@inertiajs/react';
import {
    Background,
    BackgroundVariant,
    Controls,
    
    MiniMap,
    
    ReactFlow
} from '@xyflow/react';
import type {Edge, Node} from '@xyflow/react';
import '@xyflow/react/dist/style.css';
import { ArrowLeft, Trophy } from 'lucide-react';
import { useMemo } from 'react';
import { GroupTableNode } from '@/components/bracket/group-table-node';
import { MatchNode } from '@/components/bracket/match-node';
import { TrophyNode } from '@/components/bracket/trophy-node';
import type { GroupTable, KnockoutMatch } from '@/components/bracket/types';
import { home } from '@/routes';

interface BracketPageProps {
    groups: GroupTable[];
    knockout: KnockoutMatch[];
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

function buildGraph(groups: GroupTable[], knockout: KnockoutMatch[]) {
    const totalHeight = Math.max(groups.length * GROUP_STEP, 1);
    const nodes: Node[] = [];
    const edges: Edge[] = [];
    const reduce = prefersReducedMotion();

    groups.forEach((group, index) => {
        nodes.push({
            id: `g${group.code}`,
            type: 'group',
            position: { x: COLUMN_X.group, y: index * GROUP_STEP },
            data: { group },
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
            nodes.push({
                id: `m${match.id}`,
                type: 'match',
                position: { x, y: centerY(i, matches.length) },
                data: { match },
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
        nodes.push({
            id: `m${final.id}`,
            type: 'match',
            position: { x: COLUMN_X.final, y: totalHeight / 2 - NODE_H / 2 },
            data: { match: final },
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
            source: `m${final.id}`,
            target: 'trophy',
            type: 'smoothstep',
            animated: !reduce,
            style: { stroke: '#E9A721', strokeWidth: 2 },
        });
    }

    if (third) {
        nodes.push({
            id: `m${third.id}`,
            type: 'match',
            position: { x: COLUMN_X.final, y: totalHeight / 2 + 200 },
            data: { match: third },
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
                          stroke: 'rgba(247,245,240,0.18)',
                          strokeDasharray: '4 4',
                      }
                    : { stroke: 'rgba(247,245,240,0.28)' },
            });
        }
    }

    return { nodes, edges };
}

export default function Bracket() {
    const { groups, knockout } = usePage<BracketPageProps>().props;

    const { nodes, edges } = useMemo(
        () => buildGraph(groups, knockout),
        [groups, knockout],
    );

    return (
        <>
            <Head title="Living bracket" />
            <div className="flex h-screen flex-col bg-wc-ink">
                <header className="z-10 flex items-center justify-between border-b border-wc-ink-3 bg-wc-ink px-5 py-3 text-wc-surface">
                    <div className="flex items-center gap-4">
                        <Link
                            href={home()}
                            className="flex items-center gap-2.5"
                        >
                            <span className="flex size-7 items-center justify-center rounded-md bg-wc-gold text-wc-ink">
                                <Trophy className="size-4" />
                            </span>
                            <span className="font-display text-xl tracking-wide">
                                YOURWORLD
                                <span className="text-wc-gold">CUP</span>
                            </span>
                        </Link>
                        <span className="hidden font-display text-sm tracking-wider text-wc-surface/55 uppercase sm:inline">
                            Living bracket
                        </span>
                    </div>
                    <Link
                        href={home()}
                        className="inline-flex items-center gap-1.5 rounded-md border border-wc-ink-3 px-3 py-1.5 font-mono text-[11px] tracking-wider text-wc-surface/70 uppercase transition-colors hover:bg-wc-ink-2"
                    >
                        <ArrowLeft className="size-3.5" />
                        Home
                    </Link>
                </header>

                <div className="relative flex-1">
                    <ReactFlow
                        nodes={nodes}
                        edges={edges}
                        nodeTypes={nodeTypes}
                        fitView
                        fitViewOptions={{ padding: 0.15 }}
                        minZoom={0.1}
                        maxZoom={1.5}
                        nodesDraggable={false}
                        nodesConnectable={false}
                        elementsSelectable={false}
                        proOptions={{ hideAttribution: false }}
                    >
                        <Background
                            variant={BackgroundVariant.Dots}
                            gap={28}
                            size={1}
                            color="#2A2A33"
                        />
                        <Controls
                            showInteractive={false}
                            className="!border-wc-ink-3 !bg-wc-ink-2 [&_button]:!border-wc-ink-3 [&_button]:!bg-wc-ink-2 [&_button]:!fill-wc-surface [&_button:hover]:!bg-wc-ink-3"
                        />
                        <MiniMap
                            pannable
                            zoomable
                            className="!bg-wc-ink-2"
                            maskColor="rgba(10,10,11,0.7)"
                            nodeColor="#3A3A44"
                        />
                    </ReactFlow>
                </div>
            </div>
        </>
    );
}
