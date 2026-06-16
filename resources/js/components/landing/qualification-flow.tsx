import {
    getBezierPath,
    getStraightPath,
    Handle,
    Position,
    ReactFlow,
} from '@xyflow/react';
import type { Edge, EdgeProps, Node, NodeProps } from '@xyflow/react';
import '@xyflow/react/dist/style.css';
import { useEffect, useMemo, useState } from 'react';

interface Team {
    name: string;
    code: string;
    flag: string;
    status?: 'active' | 'eliminated' | 'winner';
}

interface FlagNodeData {
    [key: string]: unknown;
    team: Team;
    phase?: 'entry' | 'knockout' | 'champion';
}

interface LabelNodeData {
    [key: string]: unknown;
    label: string;
    accent?: 'gold' | 'ink';
    pulseDelay?: string;
}

const teams: Team[] = [
    { name: 'Ghana', code: 'GHA', flag: 'https://flagcdn.com/w80/gh.png' },
    { name: 'Argentina', code: 'ARG', flag: 'https://flagcdn.com/w80/ar.png' },
    { name: 'Brazil', code: 'BRA', flag: 'https://flagcdn.com/w80/br.png' },
    { name: 'France', code: 'FRA', flag: 'https://flagcdn.com/w80/fr.png' },
    { name: 'Senegal', code: 'SEN', flag: 'https://flagcdn.com/w80/sn.png' },
    { name: 'Mexico', code: 'MEX', flag: 'https://flagcdn.com/w80/mx.png' },
    {
        name: 'South Africa',
        code: 'RSA',
        flag: 'https://flagcdn.com/w80/za.png',
    },
    { name: 'Morocco', code: 'MAR', flag: 'https://flagcdn.com/w80/ma.png' },
];

const championCodes = ['BRA', 'MAR', 'MEX', 'FRA', 'ARG'];
const cycleMs = 14000;

const championPool = championCodes
    .map((code) => teams.find((team) => team.code === code))
    .filter((team): team is Team => Boolean(team));

const CORRIDOR_TOP = 176;

const gates = [
    { id: 'r32', label: 'R32', x: 360, delay: '2.2s' },
    { id: 'r16', label: 'R16', x: 540, delay: '4s' },
    { id: 'qf', label: 'QF', x: 690, delay: '5.8s' },
    { id: 'sf', label: 'SF', x: 840, delay: '7.4s' },
    { id: 'final', label: 'FINAL', x: 1000, delay: '9s' },
];

const getEntryPosition = (index: number) => ({
    x: 24 + (index % 2) * 58,
    y: 36 + index * 48,
});

function FlagNode({ data }: NodeProps<Node<FlagNodeData, 'flag'>>) {
    const isWinner = data.team.status === 'winner';
    const isActive = data.team.status === 'active';
    const isEliminated = data.team.status === 'eliminated';

    return (
        <div
            className={[
                'broadcast-flag flex items-center gap-2 rounded-[1.1rem] border bg-white/92 px-3 py-2 shadow-[0_18px_50px_rgba(10,10,11,0.1)] backdrop-blur',
                isWinner
                    ? 'broadcast-flag-winner border-wc-gold ring-4 ring-wc-gold/20'
                    : 'border-wc-ink/12',
                isActive ? 'broadcast-flag-active' : '',
                isEliminated ? 'broadcast-flag-eliminated' : '',
            ].join(' ')}
            style={{
                animationDelay:
                    data.phase === 'entry'
                        ? `${teams.findIndex((team) => team.code === data.team.code) * 0.42}s`
                        : undefined,
            }}
        >
            <Handle
                type="target"
                position={Position.Left}
                className="size-1! border-0! bg-transparent!"
            />
            <img
                src={data.team.flag}
                alt=""
                className={
                    isWinner
                        ? 'h-10 w-15 rounded-lg object-cover'
                        : 'h-6 w-9 rounded-md object-cover'
                }
            />
            <span
                className={
                    isWinner
                        ? 'font-mono text-sm font-black tracking-wider text-wc-ink'
                        : 'font-mono text-xs font-bold tracking-wider text-wc-ink'
                }
            >
                {data.team.code}
            </span>
            <Handle
                type="source"
                position={Position.Right}
                className="size-1! border-0! bg-transparent!"
            />
        </div>
    );
}

function CheckpointNode({
    data,
}: NodeProps<Node<LabelNodeData, 'checkpoint'>>) {
    return (
        <div
            className={[
                'broadcast-checkpoint relative flex h-12 min-w-16 items-center justify-center rounded-full border bg-white px-5 shadow-[0_18px_55px_rgba(10,10,11,0.1)]',
                data.accent === 'gold'
                    ? 'border-wc-gold text-wc-ink'
                    : 'border-wc-ink/15 text-wc-ink/75',
            ].join(' ')}
            style={
                {
                    '--pulse-delay': data.pulseDelay ?? '0s',
                } as React.CSSProperties
            }
        >
            <Handle
                type="target"
                position={Position.Left}
                className="size-1! border-0! bg-transparent!"
            />
            <span className="font-mono text-[10px] font-black tracking-[0.28em] uppercase">
                {data.label}
            </span>
            <Handle
                type="source"
                position={Position.Right}
                className="size-1! border-0! bg-transparent!"
            />
        </div>
    );
}

function ChampionNode({ data }: NodeProps<Node<FlagNodeData, 'champion'>>) {
    return (
        <div className="broadcast-champion relative flex items-center gap-3 rounded-3xl border border-wc-gold bg-wc-ink px-4 py-3.5 text-white shadow-[0_22px_70px_rgba(10,10,11,0.28)]">
            <Handle
                type="target"
                position={Position.Left}
                className="size-1! border-0! bg-transparent!"
            />
            <div className="grid size-12 place-items-center rounded-full bg-wc-gold/15 ring-1 ring-wc-gold/45">
                <img
                    src="/images/world-cup-icon.png"
                    alt=""
                    className="size-9 object-contain"
                />
            </div>
            <div className="flex items-center gap-3">
                <img
                    src={data.team.flag}
                    alt=""
                    className="h-10 w-15 rounded-lg object-cover ring-2 ring-white/20"
                />
                <div>
                    <p className="font-mono text-[10px] font-black tracking-[0.32em] text-wc-gold uppercase">
                        Champion
                    </p>
                    <p className="text-lg font-black tracking-tight">
                        {data.team.name}
                    </p>
                </div>
            </div>
        </div>
    );
}

const nodeTypes = {
    flag: FlagNode,
    checkpoint: CheckpointNode,
    champion: ChampionNode,
};

interface BroadcastEdgeData {
    [key: string]: unknown;
    active?: boolean;
    straight?: boolean;
    delay?: string;
    team?: Team;
    traveler?: boolean;
    particle?: boolean;
    rail?: boolean;
}

function BroadcastEdge({
    id,
    sourceX,
    sourceY,
    targetX,
    targetY,
    sourcePosition,
    targetPosition,
    data,
}: EdgeProps<Edge<BroadcastEdgeData, 'broadcast'>>) {
    const [path] = data?.straight
        ? getStraightPath({
              sourceX,
              sourceY,
              targetX,
              targetY: sourceY,
          })
        : getBezierPath({
              sourceX,
              sourceY,
              sourcePosition,
              targetX,
              targetY,
              targetPosition,
          });
    const isActive = data?.active;

    if (data?.rail && data.team) {
        return (
            <g>
                <g className="broadcast-journey-flag">
                    <animateMotion
                        dur={`${cycleMs / 1000}s`}
                        keyPoints="0;0;1;1"
                        keyTimes="0;0.22;0.72;1"
                        calcMode="linear"
                        repeatCount="indefinite"
                        rotate="0"
                        path={path}
                    />
                    <animate
                        attributeName="opacity"
                        dur={`${cycleMs / 1000}s`}
                        values="0;0;1;1;0;0"
                        keyTimes="0;0.2;0.25;0.72;0.8;1"
                        repeatCount="indefinite"
                    />
                    <rect
                        x="-22"
                        y="-16"
                        width="44"
                        height="32"
                        rx="10"
                        fill="white"
                        stroke="#E9A721"
                        strokeWidth="1"
                    />
                    <image
                        href={data.team.flag}
                        x="-15"
                        y="-10"
                        width="30"
                        height="20"
                        preserveAspectRatio="xMidYMid slice"
                    />
                </g>
                <g
                    className="broadcast-reduced-journey-flag"
                    transform={`translate(${targetX - 44}, ${sourceY})`}
                >
                    <rect
                        x="-22"
                        y="-16"
                        width="44"
                        height="32"
                        rx="10"
                        fill="white"
                        stroke="#E9A721"
                        strokeWidth="1"
                    />
                    <image
                        href={data.team.flag}
                        x="-15"
                        y="-10"
                        width="30"
                        height="20"
                        preserveAspectRatio="xMidYMid slice"
                    />
                </g>
                <title>{id}</title>
            </g>
        );
    }

    return (
        <g className={isActive ? 'broadcast-edge-active' : undefined}>
            <path
                d={path}
                fill="none"
                stroke={isActive ? '#E9A721' : 'rgba(10,10,11,0.13)'}
                strokeDasharray={isActive ? '1 0' : '6 8'}
                strokeLinecap="round"
                strokeWidth={isActive ? 2.8 : 1.1}
            />
            {isActive && (
                <>
                    <path
                        d={path}
                        fill="none"
                        stroke="#E9A721"
                        strokeLinecap="round"
                        strokeWidth={8}
                        opacity={0.13}
                    />
                    {data?.straight && (
                        <path
                            className="broadcast-flow-dash"
                            d={path}
                            fill="none"
                            stroke="#E9A721"
                            strokeLinecap="round"
                            strokeDasharray="8 14"
                            strokeWidth={2.2}
                        />
                    )}
                    {data?.particle !== false && (
                        <>
                            <circle r="4.5" fill="#E9A721">
                                <animateMotion
                                    begin={data?.delay ?? '0s'}
                                    dur="7.2s"
                                    repeatCount="indefinite"
                                    rotate="auto"
                                    path={path}
                                />
                            </circle>
                            <circle r="8" fill="#E9A721" opacity="0.16">
                                <animateMotion
                                    begin={data?.delay ?? '0s'}
                                    dur="7.2s"
                                    repeatCount="indefinite"
                                    rotate="auto"
                                    path={path}
                                />
                            </circle>
                        </>
                    )}
                </>
            )}
            {data?.traveler && data.team && (
                <g
                    className={
                        isActive
                            ? 'broadcast-traveler broadcast-traveler-active'
                            : 'broadcast-traveler'
                    }
                >
                    <animateMotion
                        begin={data.delay ?? '0s'}
                        dur={isActive ? '9s' : '6.8s'}
                        repeatCount="indefinite"
                        rotate="auto"
                        path={path}
                    />
                    <rect
                        x="-20"
                        y="-15"
                        width="40"
                        height="30"
                        rx="10"
                        fill="white"
                        stroke={isActive ? '#E9A721' : 'rgba(10,10,11,0.16)'}
                        strokeWidth="1"
                    />
                    <image
                        href={data.team.flag}
                        x="-14"
                        y="-9"
                        width="28"
                        height="18"
                        preserveAspectRatio="xMidYMid slice"
                    />
                </g>
            )}
            <title>{id}</title>
        </g>
    );
}

const edgeTypes = {
    broadcast: BroadcastEdge,
};

export function QualificationFlow() {
    const [championIndex, setChampionIndex] = useState(0);
    const champion = championPool[championIndex % championPool.length];

    useEffect(() => {
        const interval = window.setInterval(() => {
            setChampionIndex((current) => (current + 1) % championPool.length);
        }, cycleMs);

        return () => window.clearInterval(interval);
    }, []);

    const { nodes, edges } = useMemo(() => {
        const flagNodes: Node<FlagNodeData, 'flag'>[] = teams.map(
            (team, index) => {
                const status =
                    team.code === champion.code
                        ? 'active'
                        : index > 1 && index % 3 === championIndex % 3
                          ? 'eliminated'
                          : undefined;

                return {
                    id: `team-${team.code}`,
                    type: 'flag',
                    position: getEntryPosition(index),
                    data: {
                        team: status ? { ...team, status } : team,
                        phase: 'entry',
                    },
                    draggable: false,
                };
            },
        );

        const allNodes: Node[] = [
            ...flagNodes,
            ...gates.map((gate, index) => ({
                id: gate.id,
                type: 'checkpoint',
                position: { x: gate.x, y: CORRIDOR_TOP },
                data: {
                    label: gate.label,
                    accent:
                        index === 0 || index === gates.length - 1
                            ? 'gold'
                            : 'ink',
                    pulseDelay: gate.delay,
                },
                draggable: false,
            })),
            {
                id: 'champion',
                type: 'champion',
                position: { x: 1148, y: 158 },
                data: { team: { ...champion, status: 'winner' } },
                draggable: false,
            },
        ];

        const inEdges: Edge[] = flagNodes.map((node, index) => ({
            id: `${node.id}-r32`,
            source: node.id,
            target: 'r32',
            type: 'broadcast',
            data: {
                active: node.id === `team-${champion.code}`,
                delay: `${index * 0.32}s`,
                team: teams[index],
                traveler: true,
                particle: true,
            },
        }));

        const knockoutEdges: Edge[] = [
            ['r32', 'r16'],
            ['r16', 'qf'],
            ['qf', 'sf'],
            ['sf', 'final'],
            ['final', 'champion'],
        ].map(([source, target], index) => ({
            id: `${source}-${target}`,
            source,
            target,
            type: 'broadcast',
            data: {
                active: true,
                straight: true,
                delay: `${2.25 + index * 1.25}s`,
                team: champion,
                traveler: false,
                particle: false,
            },
        }));

        const railEdge: Edge<BroadcastEdgeData, 'broadcast'> = {
            id: 'winner-rail',
            source: 'r32',
            target: 'champion',
            type: 'broadcast',
            data: {
                active: true,
                straight: true,
                team: champion,
                rail: true,
            },
        };

        return {
            nodes: allNodes,
            edges: [...inEdges, ...knockoutEdges, railEdge],
        };
    }, [champion, championIndex]);

    return (
        <div
            aria-label="Animated qualification flow"
            className="broadcast-flow relative mx-auto h-90 w-full max-w-none overflow-hidden border-y bg-wc-surface md:h-120"
            role="img"
        >
            <div
                aria-hidden
                className="pointer-events-none absolute -inset-x-32 inset-y-0 z-10 bg-[radial-gradient(ellipse_52%_68%_at_78%_48%,rgba(233,167,33,0.13)_0%,rgba(233,167,33,0.075)_34%,rgba(247,245,240,0)_74%),linear-gradient(90deg,#F7F5F0_0%,rgba(247,245,240,0.82)_10%,rgba(247,245,240,0)_25%,rgba(247,245,240,0)_72%,rgba(247,245,240,0.72)_90%,#F7F5F0_100%)]"
            />
            <style>{`
                .broadcast-flow .react-flow__handle {
                    opacity: 0;
                }

                .broadcast-flow .react-flow__renderer {
                    z-index: 20;
                }

                .broadcast-flow .react-flow__node {
                    transition: filter 400ms ease;
                }

                .broadcast-flag {
                    animation: flag-entry 14s cubic-bezier(0.16, 1, 0.3, 1) infinite both;
                }

                .broadcast-flag-winner {
                    animation: winner-flag 14s cubic-bezier(0.16, 1, 0.3, 1) infinite both;
                }

                .broadcast-flag-active {
                    box-shadow: 0 20px 60px rgba(233, 167, 33, 0.24), 0 0 0 1px rgba(233, 167, 33, 0.32);
                }

                .broadcast-flag-eliminated {
                    animation: eliminated-flag 14s cubic-bezier(0.16, 1, 0.3, 1) infinite both;
                    filter: saturate(0.45);
                }

                .broadcast-checkpoint::before {
                    content: '';
                    position: absolute;
                    inset: -8px;
                    border-radius: 9999px;
                    background: radial-gradient(circle, rgba(233, 167, 33, 0.25), transparent 68%);
                    opacity: 0;
                    animation: checkpoint-pulse 14s ease-in-out infinite;
                    animation-delay: var(--pulse-delay);
                }

                .broadcast-checkpoint::after {
                    content: '';
                    position: absolute;
                    inset: 3px;
                    border-radius: 9999px;
                    border: 1px solid rgba(233, 167, 33, 0.18);
                    opacity: 0;
                    animation: gate-ring 14s ease-in-out infinite;
                    animation-delay: var(--pulse-delay);
                }

                .broadcast-edge-active path:first-child {
                    filter: drop-shadow(0 0 6px rgba(233, 167, 33, 0.42));
                }

                .broadcast-traveler {
                    opacity: 0.24;
                    animation: traveler-elimination 14s ease-in-out infinite both;
                }

                .broadcast-traveler-active {
                    opacity: 1;
                    filter: drop-shadow(0 10px 18px rgba(233, 167, 33, 0.34));
                    animation: traveler-survivor 14s ease-in-out infinite both;
                }

                .broadcast-champion {
                    animation: champion-reveal 14s cubic-bezier(0.16, 1, 0.3, 1) infinite both;
                }

                .broadcast-journey-flag {
                    filter: drop-shadow(0 10px 18px rgba(233, 167, 33, 0.3));
                }

                .broadcast-reduced-journey-flag {
                    display: none;
                }

                .broadcast-flow-dash {
                    animation: flow-dash 1.35s linear infinite;
                    opacity: 0.78;
                }

                @keyframes flag-entry {
                    0%, 8% { opacity: 0.72; transform: translateX(-8px) scale(0.96); }
                    16%, 88% { opacity: 1; transform: translateX(0) scale(1); }
                    100% { opacity: 0.72; transform: translateX(8px) scale(0.98); }
                }

                @keyframes eliminated-flag {
                    0%, 10% { opacity: 0.38; transform: translateX(-8px) scale(0.94); }
                    18%, 48% { opacity: 0.68; transform: translateX(0) scale(0.96); }
                    64%, 100% { opacity: 0.28; transform: translateX(6px) scale(0.92); }
                }

                @keyframes winner-flag {
                    0%, 8% { opacity: 0.82; transform: translateX(-12px) scale(0.96); }
                    18%, 76% { opacity: 1; transform: translateX(0) scale(1); }
                    88%, 100% { opacity: 1; transform: translateX(8px) scale(1.08); }
                }

                @keyframes checkpoint-pulse {
                    0%, 26% { opacity: 0; transform: scale(0.92); }
                    34%, 48% { opacity: 1; transform: scale(1.05); }
                    68%, 100% { opacity: 0; transform: scale(1.18); }
                }

                @keyframes gate-ring {
                    0%, 30% { opacity: 0; transform: scale(0.96); }
                    38%, 52% { opacity: 1; transform: scale(1.05); }
                    76%, 100% { opacity: 0; transform: scale(1.18); }
                }

                @keyframes traveler-elimination {
                    0%, 12% { opacity: 0; }
                    22%, 48% { opacity: 0.38; }
                    62%, 100% { opacity: 0; }
                }

                @keyframes traveler-survivor {
                    0%, 8% { opacity: 0.55; transform: scale(0.9); }
                    18%, 84% { opacity: 1; transform: scale(1); }
                    100% { opacity: 0.7; transform: scale(1.06); }
                }

                @keyframes flow-dash {
                    to {
                        stroke-dashoffset: -22;
                    }
                }

                @keyframes champion-reveal {
                    0%, 70% { opacity: 0; transform: translateX(18px) scale(0.94); }
                    80%, 92% { opacity: 1; transform: translateX(0) scale(1.04); }
                    100% { opacity: 0.92; transform: translateX(0) scale(1); }
                }

                @media (prefers-reduced-motion: reduce) {
                    .broadcast-flag,
                    .broadcast-flag-winner,
                    .broadcast-flag-eliminated,
                    .broadcast-checkpoint::before,
                    .broadcast-checkpoint::after,
                    .broadcast-traveler,
                    .broadcast-flow-dash,
                    .broadcast-champion {
                        animation: none !important;
                    }

                    .broadcast-journey-flag {
                        display: none;
                    }

                    .broadcast-reduced-journey-flag {
                        display: block;
                    }
                }
            `}</style>

            <ReactFlow
                nodes={nodes}
                edges={edges}
                nodeTypes={nodeTypes}
                edgeTypes={edgeTypes}
                fitView
                fitViewOptions={{ padding: 0.16 }}
                minZoom={0.25}
                maxZoom={1}
                nodesDraggable={false}
                nodesConnectable={false}
                elementsSelectable={false}
                panOnDrag={false}
                zoomOnScroll={false}
                zoomOnPinch={false}
                zoomOnDoubleClick={false}
                preventScrolling={false}
                proOptions={{ hideAttribution: true }}
            />
        </div>
    );
}
