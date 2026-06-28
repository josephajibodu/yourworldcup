import { usePage } from '@inertiajs/react';
import {
    Background,
    BackgroundVariant,
    Controls,
    MiniMap,
    ReactFlow,
    useReactFlow,
} from '@xyflow/react';
import '@xyflow/react/dist/style.css';
import { useEffect, useMemo } from 'react';
import { buildGraph } from '@/components/bracket/build-graph';
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

const prefersReducedMotion = () =>
    typeof window !== 'undefined' &&
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

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
