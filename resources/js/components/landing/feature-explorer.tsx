import { ChevronRight, Gift, Network, Target } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { cn } from '@/lib/utils';

/* -------------------------------------------------------------------------- */
/*  Shared: reduced-motion preference                                          */
/* -------------------------------------------------------------------------- */

function usePrefersReducedMotion(): boolean {
    const [reduced, setReduced] = useState(() =>
        typeof window !== 'undefined'
            ? window.matchMedia('(prefers-reduced-motion: reduce)').matches
            : false,
    );

    useEffect(() => {
        const query = window.matchMedia('(prefers-reduced-motion: reduce)');
        const handler = (event: MediaQueryListEvent) =>
            setReduced(event.matches);

        query.addEventListener('change', handler);

        return () => query.removeEventListener('change', handler);
    }, []);

    return reduced;
}

/* -------------------------------------------------------------------------- */
/*  Demo: Daily predictions                                                    */
/* -------------------------------------------------------------------------- */

const PREDICTIONS_STYLE = `
.fxp-score, .fxp-lock, .fxp-underline, .fxp-banker, .fxp-points {
    transform-box: fill-box;
}
.fxp-score { transform-origin: center; animation: fxp-score 4.6s ease-in-out infinite both; }
.fxp-lock { transform-origin: center; animation: fxp-lock 4.6s ease-in-out infinite both; }
.fxp-underline { transform-origin: left center; animation: fxp-underline 4.6s ease-in-out infinite both; }
.fxp-banker { transform-origin: center; animation: fxp-banker 4.6s cubic-bezier(0.22, 1, 0.36, 1) infinite both; }
.fxp-points { transform-origin: center; animation: fxp-points 4.6s ease-in-out infinite both; }

@keyframes fxp-score {
    0%, 8% { opacity: 0.35; transform: scale(0.92); }
    16% { opacity: 1; transform: scale(1.05); }
    24%, 100% { opacity: 1; transform: scale(1); }
}
@keyframes fxp-lock {
    0%, 9% { opacity: 0; transform: translateY(-4px) scale(0.6); }
    16% { opacity: 1; transform: translateY(0) scale(1.12); }
    24%, 100% { opacity: 1; transform: translateY(0) scale(1); }
}
@keyframes fxp-underline {
    0%, 16% { transform: scaleX(0); }
    32%, 100% { transform: scaleX(1); }
}
@keyframes fxp-banker {
    0%, 40% { opacity: 0; transform: scale(0.6); }
    48% { opacity: 1; transform: scale(1.1); }
    56%, 100% { opacity: 1; transform: scale(1); }
}
@keyframes fxp-points {
    0%, 56% { opacity: 0; transform: translateY(8px); }
    66% { opacity: 1; transform: translateY(0); }
    86% { opacity: 1; transform: translateY(-3px); }
    100% { opacity: 0; transform: translateY(-12px); }
}
`;

function PredictionsDemo({ reduced }: { reduced: boolean }) {
    return (
        <svg
            viewBox="0 0 360 220"
            className="h-full w-full"
            role="img"
            aria-label="A match prediction card locking in a score with a banker pick"
        >
            {!reduced && <style>{PREDICTIONS_STYLE}</style>}

            <text
                x="24"
                y="34"
                className="fill-wc-ink/45 font-mono"
                fontSize="11"
                letterSpacing="0.08em"
            >
                MATCHDAY 7 · LOCKS 17:00
            </text>

            {/* Team A */}
            <g>
                <rect
                    x="22"
                    y="74"
                    width="96"
                    height="72"
                    rx="16"
                    fill="#fff"
                    stroke="rgba(10,10,11,0.1)"
                />
                <image
                    href="https://flagcdn.com/br.svg"
                    x="44"
                    y="88"
                    width="52"
                    height="34"
                    preserveAspectRatio="xMidYMid slice"
                />
                <text
                    x="70"
                    y="138"
                    textAnchor="middle"
                    className="fill-wc-ink font-mono font-bold"
                    fontSize="13"
                >
                    BRA
                </text>
            </g>

            {/* Team B */}
            <g>
                <rect
                    x="242"
                    y="74"
                    width="96"
                    height="72"
                    rx="16"
                    fill="#fff"
                    stroke="rgba(10,10,11,0.1)"
                />
                <image
                    href="https://flagcdn.com/fr.svg"
                    x="264"
                    y="88"
                    width="52"
                    height="34"
                    preserveAspectRatio="xMidYMid slice"
                />
                <text
                    x="290"
                    y="138"
                    textAnchor="middle"
                    className="fill-wc-ink font-mono font-bold"
                    fontSize="13"
                >
                    FRA
                </text>
            </g>

            {/* Lock glyph */}
            <g className={reduced ? undefined : 'fxp-lock'}>
                <path
                    d="M176 58 v-3 a4 4 0 0 1 8 0 v3"
                    fill="none"
                    stroke="#0A0A0B"
                    strokeWidth="1.8"
                />
                <rect
                    x="173"
                    y="58"
                    width="14"
                    height="11"
                    rx="2.5"
                    fill="#0A0A0B"
                />
            </g>

            {/* Score panel */}
            <g className={reduced ? undefined : 'fxp-score'}>
                <rect
                    x="146"
                    y="84"
                    width="68"
                    height="44"
                    rx="12"
                    fill="#fff"
                    stroke="#E9A721"
                    strokeWidth="1.5"
                />
                <text
                    x="180"
                    y="114"
                    textAnchor="middle"
                    className="fill-wc-ink font-mono font-bold"
                    fontSize="22"
                >
                    2–1
                </text>
            </g>
            <rect
                x="150"
                y="132"
                width="60"
                height="3"
                rx="1.5"
                fill="#E9A721"
                className={reduced ? undefined : 'fxp-underline'}
            />

            {/* Banker pill */}
            <g className={reduced ? undefined : 'fxp-banker'}>
                <rect
                    x="120"
                    y="160"
                    width="120"
                    height="28"
                    rx="14"
                    fill="#0A0A0B"
                />
                <text
                    x="180"
                    y="178"
                    textAnchor="middle"
                    className="fill-wc-gold font-mono font-bold"
                    fontSize="12"
                    letterSpacing="0.06em"
                >
                    BANKER ×2
                </text>
            </g>

            {/* Points ticking up */}
            {!reduced && (
                <text
                    x="312"
                    y="66"
                    textAnchor="middle"
                    className="fxp-points fill-wc-gold font-bold"
                    fontSize="15"
                >
                    +45 pts
                </text>
            )}
        </svg>
    );
}

/* -------------------------------------------------------------------------- */
/*  Demo: Living bracket                                                       */
/* -------------------------------------------------------------------------- */

const GOLD = '#E9A721';
const INK_FAINT = 'rgba(10,10,11,0.14)';

const BRACKET_STYLE = `
.fxb-team { transform-box: fill-box; transform-origin: center; animation: fxb-team 4.8s ease-in-out infinite both; }
@keyframes fxb-team {
    0%, 46% { opacity: 0; transform: scale(0.5); }
    58% { opacity: 1; transform: scale(1.12); }
    66%, 92% { opacity: 1; transform: scale(1); }
    100% { opacity: 0; transform: scale(0.5); }
}
`;

function Slot({ x, y, gold }: { x: number; y: number; gold?: boolean }) {
    return (
        <rect
            x={x}
            y={y}
            width="64"
            height="24"
            rx="7"
            fill="#fff"
            stroke={gold ? GOLD : 'rgba(10,10,11,0.12)'}
            strokeWidth={gold ? 1.5 : 1}
        />
    );
}

function BracketDemo({ reduced }: { reduced: boolean }) {
    // Faint, always-on connectors for the non-winning side.
    const faintPaths = [
        'M80 78 H114 V56 H148',
        'M80 142 H114 V164 H148',
        'M80 186 H114 V164 H148',
        'M212 164 H242 V110 H272',
    ];

    // Gold corridor segments (the winning road to the final), filled in sequence.
    const goldSegments = [
        { d: 'M80 34 H114 V56 H180', keyTimes: '0;0.06;0.22;0.92;1' },
        { d: 'M180 56 H212 V110 H304', keyTimes: '0;0.22;0.38;0.92;1' },
        { d: 'M304 110 H346', keyTimes: '0;0.38;0.54;0.92;1' },
    ];

    const travelPath = 'M80 34 H114 V56 H212 V110 H346';

    return (
        <svg
            viewBox="0 0 360 220"
            className="h-full w-full"
            role="img"
            aria-label="A mini tournament bracket filling toward the final"
        >
            {!reduced && <style>{BRACKET_STYLE}</style>}

            {faintPaths.map((d) => (
                <path
                    key={d}
                    d={d}
                    fill="none"
                    stroke={INK_FAINT}
                    strokeWidth="2"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                />
            ))}

            {goldSegments.map((segment) => (
                <path
                    key={segment.d}
                    d={segment.d}
                    fill="none"
                    stroke={GOLD}
                    strokeWidth="2.5"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    pathLength={1}
                    strokeDasharray={1}
                    strokeDashoffset={reduced ? 0 : 1}
                >
                    {!reduced && (
                        <animate
                            attributeName="stroke-dashoffset"
                            dur="4.8s"
                            values="1;1;0;0;1"
                            keyTimes={segment.keyTimes}
                            repeatCount="indefinite"
                        />
                    )}
                </path>
            ))}

            {/* Round-one slots */}
            <Slot x={16} y={22} gold />
            <Slot x={16} y={66} />
            <Slot x={16} y={130} />
            <Slot x={16} y={174} />
            {/* Semis */}
            <Slot x={148} y={44} gold />
            <Slot x={148} y={152} />
            {/* Final */}
            <Slot x={272} y={98} gold />

            {/* Winning team flag entering round one */}
            <image
                href="https://flagcdn.com/br.svg"
                x="24"
                y="27"
                width="22"
                height="14"
                preserveAspectRatio="xMidYMid slice"
            />

            {/* Traveling gold particle along the winning road */}
            {!reduced && (
                <circle r="4" fill={GOLD}>
                    <animateMotion
                        dur="4.8s"
                        path={travelPath}
                        keyPoints="0;0;1;1"
                        keyTimes="0;0.08;0.54;1"
                        repeatCount="indefinite"
                    />
                </circle>
            )}

            {/* Champion reveal at the final */}
            <g className={reduced ? undefined : 'fxb-team'}>
                <circle cx="346" cy="60" r="16" fill="#0A0A0B" />
                <image
                    href="https://flagcdn.com/br.svg"
                    x="334"
                    y="53"
                    width="24"
                    height="14"
                    preserveAspectRatio="xMidYMid slice"
                />
                <image
                    href="/images/world-cup-icon.png"
                    x="338"
                    y="86"
                    width="16"
                    height="16"
                />
            </g>
        </svg>
    );
}

/* -------------------------------------------------------------------------- */
/*  Demo: Real rewards (live leaderboard)                                      */
/* -------------------------------------------------------------------------- */

interface Player {
    id: string;
    name: string;
    points: number;
}

const SEED_PLAYERS: Player[] = [
    { id: 'a', name: 'Ada', points: 1280 },
    { id: 'b', name: 'Bola', points: 1210 },
    { id: 'c', name: 'Musa', points: 1175 },
    { id: 'd', name: 'Dami', points: 1090 },
    { id: 'e', name: 'Emeka', points: 1040 },
];

const ROW_H = 56;
const ROW_GAP = 8;
const TICK_MS = 2200;

function CountUp({ value, animate }: { value: number; animate: boolean }) {
    const [display, setDisplay] = useState(value);
    const fromRef = useRef(value);
    const rafRef = useRef<number | null>(null);

    useEffect(() => {
        if (!animate) {
            return;
        }

        const from = fromRef.current;
        const to = value;

        if (from === to) {
            return;
        }

        const duration = 500;
        const start = performance.now();
        const step = (now: number) => {
            const t = Math.min(1, (now - start) / duration);
            const eased = 1 - Math.pow(1 - t, 3);
            setDisplay(Math.round(from + (to - from) * eased));

            if (t < 1) {
                rafRef.current = requestAnimationFrame(step);
            } else {
                fromRef.current = to;
            }
        };

        rafRef.current = requestAnimationFrame(step);

        return () => {
            if (rafRef.current) {
                cancelAnimationFrame(rafRef.current);
            }

            fromRef.current = to;
        };
    }, [value, animate]);

    if (!animate) {
        return <>{value.toLocaleString()}</>;
    }

    return <>{display.toLocaleString()}</>;
}

const REWARDS_STYLE = `
@keyframes fxr-pulse {
    0% { transform: scale(1); }
    40% { transform: scale(1.025); }
    100% { transform: scale(1); }
}
.fxr-pulse { animation: fxr-pulse 320ms ease-out; }
`;

function RewardsDemo({ reduced }: { reduced: boolean }) {
    const [players, setPlayers] = useState<Player[]>(() =>
        reduced
            ? [...SEED_PLAYERS].sort((a, b) => b.points - a.points)
            : SEED_PLAYERS,
    );

    // Keep the latest committed players available to the interval without
    // re-creating the timer on every tick.
    const playersRef = useRef(players);
    useEffect(() => {
        playersRef.current = players;
    }, [players]);

    // Rank (0-indexed) keyed by stable player id — drives translateY, never DOM order.
    const ranks = useMemo(() => {
        const sorted = [...players].sort((a, b) => b.points - a.points);
        const map: Record<string, number> = {};
        sorted.forEach((player, index) => {
            map[player.id] = index;
        });

        return map;
    }, [players]);

    const prevRanksRef = useRef(ranks);
    const [pulsing, setPulsing] = useState<Set<string>>(new Set());
    const [bumped, setBumped] = useState<Set<string>>(new Set());

    // Tick loop: bump 1–2 players, then re-rank.
    useEffect(() => {
        if (reduced) {
            return;
        }

        const interval = window.setInterval(() => {
            const current = playersRef.current;
            const count = 1 + Math.floor(Math.random() * 2);
            const chosen = new Set<number>();

            while (chosen.size < count) {
                chosen.add(Math.floor(Math.random() * current.length));
            }

            const bumpedIds = new Set<string>();
            const next = current.map((player, index) => {
                if (!chosen.has(index)) {
                    return player;
                }

                bumpedIds.add(player.id);

                return {
                    ...player,
                    points:
                        player.points + (15 + Math.floor(Math.random() * 46)),
                };
            });

            setPlayers(next);
            setBumped(bumpedIds);
        }, TICK_MS);

        return () => window.clearInterval(interval);
    }, [reduced]);

    // Detect upward rank movement → brief pulse cue.
    useEffect(() => {
        const moved = new Set<string>();
        Object.keys(ranks).forEach((id) => {
            const prev = prevRanksRef.current[id];

            if (prev !== undefined && ranks[id] < prev) {
                moved.add(id);
            }
        });
        prevRanksRef.current = ranks;

        if (moved.size === 0) {
            return;
        }

        setPulsing(moved);
        const timeout = window.setTimeout(() => setPulsing(new Set()), 340);

        return () => window.clearTimeout(timeout);
    }, [ranks]);

    // Clear the gold points flash shortly after a bump.
    useEffect(() => {
        if (bumped.size === 0) {
            return;
        }

        const timeout = window.setTimeout(() => setBumped(new Set()), 600);

        return () => window.clearTimeout(timeout);
    }, [bumped]);

    const boardHeight = players.length * (ROW_H + ROW_GAP) - ROW_GAP;

    return (
        <div className="flex h-full w-full items-center justify-center px-2">
            <style>{REWARDS_STYLE}</style>
            <div
                className="relative w-full max-w-sm"
                style={{ height: boardHeight }}
            >
                {players.map((player) => {
                    const rank = ranks[player.id];
                    const isLeader = rank === 0;
                    const isPulsing = pulsing.has(player.id);
                    const isBumped = bumped.has(player.id);

                    return (
                        <div
                            key={player.id}
                            className="absolute inset-x-0"
                            style={{
                                height: ROW_H,
                                transform: `translateY(${rank * (ROW_H + ROW_GAP)}px)`,
                                transition: reduced
                                    ? undefined
                                    : 'transform 600ms cubic-bezier(0.22, 1, 0.36, 1)',
                            }}
                        >
                            <div
                                className={cn(
                                    'flex h-full items-center gap-3 rounded-xl border px-4 transition-colors duration-500',
                                    isLeader
                                        ? 'border-wc-gold bg-wc-gold/10'
                                        : 'border-wc-ink/10 bg-white',
                                    isPulsing && 'fxr-pulse',
                                )}
                            >
                                <span
                                    className={cn(
                                        'flex size-7 shrink-0 items-center justify-center rounded-lg font-mono text-sm font-bold',
                                        isLeader
                                            ? 'bg-wc-gold text-wc-ink'
                                            : 'bg-wc-surface-2 text-wc-ink/60',
                                    )}
                                >
                                    {rank + 1}
                                </span>
                                <span className="flex-1 truncate text-sm font-semibold text-wc-ink">
                                    {player.name}
                                </span>
                                {isLeader && (
                                    <span className="hidden items-center gap-1 rounded-full bg-wc-gold/20 px-2 py-0.5 text-[11px] font-bold text-wc-gold-deep sm:flex">
                                        <Gift className="size-3" />
                                        airtime
                                    </span>
                                )}
                                <span
                                    className={cn(
                                        'font-mono text-sm font-bold tabular-nums transition-colors duration-300',
                                        isBumped
                                            ? 'text-wc-gold'
                                            : 'text-wc-ink',
                                    )}
                                >
                                    <CountUp
                                        value={player.points}
                                        animate={!reduced}
                                    />
                                </span>
                            </div>
                        </div>
                    );
                })}
            </div>
        </div>
    );
}

/* -------------------------------------------------------------------------- */
/*  Feature config                                                             */
/* -------------------------------------------------------------------------- */

interface FeatureConfig {
    id: 'predictions' | 'bracket' | 'rewards';
    label: string;
    title: string;
    shortDescription: string;
    longDescription: string;
    icon: LucideIcon;
    accent: string;
    Demo: (props: { reduced: boolean }) => React.JSX.Element;
}

const FEATURES: FeatureConfig[] = [
    {
        id: 'predictions',
        label: 'Daily predictions',
        title: 'Master daily predictions',
        shortDescription:
            'Call the winner of every match — add the exact score for bonus points, and double down with one banker pick a day.',
        longDescription:
            'Call the winner of every match, add the exact score for bonus points, and double down with one banker pick a day to climb faster.',
        icon: Target,
        accent: 'bg-rose-500/12 text-rose-600',
        Demo: PredictionsDemo,
    },
    {
        id: 'bracket',
        label: 'Living bracket',
        title: 'Follow the living bracket',
        shortDescription:
            "All 12 groups flow into the knockouts, updating in real time as results land. Follow any team's road to the final.",
        longDescription:
            "All 12 groups flow into the knockouts, updating in real time as results land. Follow any team's road to the final.",
        icon: Network,
        accent: 'bg-indigo-500/12 text-indigo-600',
        Demo: BracketDemo,
    },
    {
        id: 'rewards',
        label: 'Real rewards',
        title: 'Win real rewards',
        shortDescription:
            'Top the daily board for airtime, paid straight to your verified phone. Climb the overall table for the grand prize.',
        longDescription:
            'Top the daily board for airtime, paid straight to your verified phone. Climb the overall table for the grand prize.',
        icon: Gift,
        accent: 'bg-wc-green/12 text-wc-green',
        Demo: RewardsDemo,
    },
];

/* -------------------------------------------------------------------------- */
/*  Master / detail explorer                                                   */
/* -------------------------------------------------------------------------- */

export function FeatureExplorer() {
    const reduced = usePrefersReducedMotion();
    const [activeId, setActiveId] = useState<FeatureConfig['id']>(
        FEATURES[0].id,
    );
    const active =
        FEATURES.find((feature) => feature.id === activeId) ?? FEATURES[0];
    const ActiveDemo = active.Demo;

    return (
        <div className="overflow-hidden rounded-3xl border border-wc-ink/10 bg-white/92 shadow-sm md:grid md:grid-cols-[minmax(0,38%)_1fr]">
            {/* Left: accordion list */}
            <div
                role="tablist"
                aria-label="Features"
                className="border-wc-insk/10 space-y-2 border-b p-2 md:border-r md:border-b-0 md:p-3"
            >
                {FEATURES.map((feature) => {
                    const isActive = feature.id === activeId;

                    return (
                        <button
                            key={feature.id}
                            type="button"
                            role="tab"
                            id={`fx-tab-${feature.id}`}
                            aria-selected={isActive}
                            aria-controls="fx-panel"
                            onClick={() => setActiveId(feature.id)}
                            className={cn(
                                'group relative w-full rounded-2xl px-4 py-4 text-left transition-colors hover:cursor-pointer',
                                isActive
                                    ? 'bg-wc-gold/5'
                                    : 'border-transparent hover:bg-wc-surface-2/60',
                            )}
                        >
                            <span className="flex items-center gap-3">
                                <span
                                    className={cn(
                                        'flex size-9 shrink-0 items-center justify-center rounded-lg',
                                        feature.accent,
                                    )}
                                >
                                    <feature.icon className="size-4.5" />
                                </span>
                                <span
                                    className={cn(
                                        'flex-1 text-sm font-bold tracking-tight',
                                        isActive
                                            ? 'text-wc-ink'
                                            : 'text-wc-ink/70',
                                    )}
                                >
                                    {feature.label}
                                </span>
                                <ChevronRight
                                    className={cn(
                                        'size-4 shrink-0 transition-transform',
                                        isActive
                                            ? 'rotate-90 text-wc-gold'
                                            : 'text-wc-ink/30',
                                    )}
                                />
                            </span>
                            {isActive && (
                                <p className="mt-2 pl-12 text-sm leading-relaxed text-wc-ink/60">
                                    {feature.shortDescription}
                                </p>
                            )}
                        </button>
                    );
                })}
            </div>

            {/* Right: detail panel */}
            <div
                role="tabpanel"
                id="fx-panel"
                aria-labelledby={`fx-tab-${active.id}`}
                className="flex flex-col gap-5 p-6 md:p-8"
            >
                <div
                    key={active.id}
                    className="flex flex-1 animate-in flex-col gap-5 duration-500 fade-in slide-in-from-bottom-2"
                >
                    <div>
                        <h3 className="text-2xl font-bold tracking-tight text-wc-ink">
                            {active.title}
                        </h3>
                        <p className="mt-2 max-w-prose text-base leading-relaxed text-wc-ink/65">
                            {active.longDescription}
                        </p>
                    </div>
                    <div className="flex min-h-56 flex-1 items-center justify-center rounded-2xl border border-wc-ink/8 bg-wc-surface p-4 sm:min-h-64">
                        <ActiveDemo reduced={reduced} />
                    </div>
                </div>
            </div>
        </div>
    );
}
