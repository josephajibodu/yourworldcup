import { Sparkles } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { usePlayerCount } from '@/hooks/use-player-count';

/* -------------------------------------------------------------------------- */
/*  Copy                                                                       */
/* -------------------------------------------------------------------------- */

const STATE_A_EYEBROW = 'every day';
const STATE_A_HEADLINE =
    'win airtime every single day of the tournament, top the daily board and it lands straight on your verified phone.';
const STATE_B_EYEBROW = 'The grand prize';
const STATE_B_HEADLINE =
    'every prediction flows toward one grand prize at the final whistle. It unlocks the moment we reach 1,000 players.';
const PERSISTENT_LINE =
    'plus surprise drops at random moments, ensure you are active to win!';

/* -------------------------------------------------------------------------- */
/*  Math                                                                       */
/* -------------------------------------------------------------------------- */

const RING_R = 58;
const RING_C = 2 * Math.PI * RING_R;
const NODE_CX = 140;
const NODE_CY = 140;

const clamp = (value: number, min: number, max: number): number =>
    Math.min(max, Math.max(min, value));

const smoothstep = (edge0: number, edge1: number, x: number): number => {
    const t = clamp((x - edge0) / (edge1 - edge0), 0, 1);

    return t * t * (3 - 2 * t);
};

/* -------------------------------------------------------------------------- */
/*  Hooks                                                                      */
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

/** rAF count-up tween toward `target` (mirrors the feature-explorer CountUp). */
function useCountUp(target: number, animate: boolean): number {
    const [display, setDisplay] = useState(target);
    const fromRef = useRef(target);
    const rafRef = useRef<number | null>(null);

    useEffect(() => {
        if (!animate) {
            fromRef.current = target;

            return;
        }

        const from = fromRef.current;
        const to = target;

        if (from === to) {
            return;
        }

        const duration = 600;
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
    }, [target, animate]);

    return animate ? display : target;
}

/* -------------------------------------------------------------------------- */
/*  Presentational pieces                                                       */
/* -------------------------------------------------------------------------- */

function StateBlock({
    eyebrow,
    headline,
}: {
    eyebrow: string;
    headline: string;
}) {
    return (
        <div>
            <span className="inline-block rounded-full bg-wc-gold/15 px-3 py-1 font-mono text-[11px] font-bold tracking-[0.18em] text-wc-ink uppercase">
                {eyebrow}
            </span>
            <p className="mt-4 text-2xl leading-tight font-bold tracking-tight text-wc-ink sm:text-3xl md:text-4xl">
                {headline}
            </p>
        </div>
    );
}

function PersistentLine() {
    return (
        <p className="flex items-center gap-2 text-sm leading-relaxed text-wc-ink/60">
            {PERSISTENT_LINE}
        </p>
    );
}

const LOCK_STYLE = `
.prize-burst { transform-box: fill-box; transform-origin: center; animation: prize-burst 620ms cubic-bezier(0.22, 1, 0.36, 1) both; }
@keyframes prize-burst {
    0% { opacity: 0; transform: scale(0.4); }
    60% { opacity: 1; }
    100% { opacity: 0.9; transform: scale(1); }
}
.prize-ring-loading { animation: prize-ring-pulse 1.5s ease-in-out infinite; }
@keyframes prize-ring-pulse { 0%, 100% { opacity: 0.35; } 50% { opacity: 0.75; } }
`;

interface GrandPrizeLockProps {
    fraction: number;
    isUnlocked: boolean;
    isLoading: boolean;
    count: number | null;
    unlockTarget: number;
    shownCount: number;
    reduced: boolean;
}

function GrandPrizeLock({
    fraction,
    isUnlocked,
    isLoading,
    count,
    unlockTarget,
    shownCount,
    reduced,
}: GrandPrizeLockProps) {
    return (
        <svg
            viewBox="0 0 280 280"
            preserveAspectRatio="xMidYMid meet"
            className="mx-auto h-full w-full max-w-[280px]"
            role="img"
            aria-label="Grand prize unlock progress"
        >
            {!reduced && <style>{LOCK_STYLE}</style>}

            <circle
                cx={NODE_CX}
                cy={NODE_CY}
                r={RING_R}
                fill="none"
                stroke="rgba(10,10,11,0.12)"
                strokeWidth="6"
            />
            <circle
                cx={NODE_CX}
                cy={NODE_CY}
                r={RING_R}
                fill="none"
                stroke="#E9A721"
                strokeWidth="6"
                strokeLinecap="round"
                strokeDasharray={RING_C}
                strokeDashoffset={RING_C * (1 - fraction)}
                transform={`rotate(-90 ${NODE_CX} ${NODE_CY})`}
                className={
                    !reduced && isLoading ? 'prize-ring-loading' : undefined
                }
            />

            {isUnlocked ? (
                <>
                    <g transform={`translate(${NODE_CX} ${NODE_CY})`}>
                        <g className={reduced ? undefined : 'prize-burst'}>
                            {[0, 45, 90, 135, 180, 225, 270, 315].map(
                                (angle) => {
                                    const rad = (angle * Math.PI) / 180;

                                    return (
                                        <line
                                            key={angle}
                                            x1={Math.cos(rad) * 36}
                                            y1={Math.sin(rad) * 36}
                                            x2={Math.cos(rad) * 52}
                                            y2={Math.sin(rad) * 52}
                                            stroke="#E9A721"
                                            strokeWidth="3"
                                            strokeLinecap="round"
                                        />
                                    );
                                },
                            )}
                        </g>
                    </g>
                    <image
                        href="/images/world-cup-icon.png"
                        x={NODE_CX - 26}
                        y={NODE_CY - 26}
                        width="52"
                        height="52"
                    />
                </>
            ) : (
                <g>
                    <path
                        d={`M ${NODE_CX - 12} ${NODE_CY - 2} v-8 a12 12 0 0 1 24 0 v8`}
                        fill="none"
                        stroke="#0A0A0B"
                        strokeWidth="3.5"
                        strokeLinecap="round"
                    />
                    <rect
                        x={NODE_CX - 16}
                        y={NODE_CY - 2}
                        width="32"
                        height="26"
                        rx="6"
                        fill="#0A0A0B"
                    />
                    <circle
                        cx={NODE_CX}
                        cy={NODE_CY + 9}
                        r="3.2"
                        fill="#E9A721"
                    />
                </g>
            )}

            <text
                x={NODE_CX}
                y={NODE_CY + RING_R + 38}
                textAnchor="middle"
                className="fill-wc-ink font-mono font-bold"
                fontSize="22"
            >
                {count === null ? '—' : shownCount.toLocaleString()}
                {count !== null && !isUnlocked && (
                    <tspan className="fill-wc-ink/40" fontSize="15">
                        {` / ${unlockTarget.toLocaleString()}`}
                    </tspan>
                )}
            </text>
            <text
                x={NODE_CX}
                y={NODE_CY + RING_R + 60}
                textAnchor="middle"
                className={
                    isUnlocked
                        ? 'fill-wc-gold-deep font-bold'
                        : 'fill-wc-ink/55'
                }
                fontSize="12.5"
                letterSpacing="0.04em"
            >
                {isUnlocked
                    ? 'Grand prize unlocked'
                    : count === null
                      ? isLoading
                          ? 'checking players…'
                          : 'players'
                      : `locked until ${unlockTarget.toLocaleString()} players`}
            </text>
        </svg>
    );
}

/* -------------------------------------------------------------------------- */
/*  Section                                                                     */
/* -------------------------------------------------------------------------- */

interface PrizesRevealProps {
    /** Player count that unlocks the grand prize. */
    unlockTarget?: number;
    /** If set, the live count refreshes on this interval (ms). */
    pollMs?: number;
}

export function PrizesReveal({
    unlockTarget = 1000,
    pollMs,
}: PrizesRevealProps) {
    const reduced = usePrefersReducedMotion();
    const { count, isLoading, error } = usePlayerCount(pollMs);

    const outerRef = useRef<HTMLDivElement>(null);
    const stateARef = useRef<HTMLDivElement>(null);
    const stateBRef = useRef<HTMLDivElement>(null);

    /* Scroll-linked text swap — updates DOM directly each frame so React never
       re-renders during scroll, and progress tracks scroll 1:1 (no trailing lerp). */
    useEffect(() => {
        if (reduced) {
            return;
        }

        const element = outerRef.current;

        if (!element) {
            return;
        }

        let raf = 0;

        const apply = (p: number) => {
            const aOpacity = 1 - smoothstep(0.3, 0.55, p);
            const bOpacity = smoothstep(0.45, 0.7, p);
            const aShift = -smoothstep(0.3, 0.55, p) * 20;
            const bShift = (1 - smoothstep(0.45, 0.7, p)) * 20;

            const a = stateARef.current;
            const b = stateBRef.current;

            if (a) {
                a.style.opacity = String(aOpacity);
                a.style.transform = `translate3d(0, ${aShift}px, 0)`;
            }

            if (b) {
                b.style.opacity = String(bOpacity);
                b.style.transform = `translate3d(0, ${bShift}px, 0)`;
            }
        };

        const update = () => {
            raf = 0;
            const rect = element.getBoundingClientRect();
            const total = rect.height - window.innerHeight;
            const p = total > 0 ? clamp(-rect.top / total, 0, 1) : 0;
            apply(p);
        };

        const onScroll = () => {
            if (!raf) {
                raf = requestAnimationFrame(update);
            }
        };

        update();
        window.addEventListener('scroll', onScroll, { passive: true });
        window.addEventListener('resize', onScroll);

        return () => {
            window.removeEventListener('scroll', onScroll);
            window.removeEventListener('resize', onScroll);

            if (raf) {
                cancelAnimationFrame(raf);
            }
        };
    }, [reduced]);

    /* Lock state — driven by the LIVE count, never by scroll progress. */
    const isUnlocked = count !== null && count >= unlockTarget;
    const displayCount = useCountUp(count ?? 0, !reduced && count !== null);
    const shownCount = count === null ? 0 : reduced ? count : displayCount;
    const fraction = clamp(shownCount / unlockTarget, 0, 1);

    const lockProps: GrandPrizeLockProps = {
        fraction,
        isUnlocked,
        isLoading,
        count: error && count === null ? null : count,
        unlockTarget,
        shownCount,
        reduced,
    };

    const content = (
        <div className="mx-auto grid w-full max-w-6xl items-center gap-10 px-6 md:grid-cols-[1fr_auto] md:gap-16 lg:gap-20">
            {/* Text — crossfading State A → State B (styles driven by scroll rAF) */}
            <div className="min-w-0">
                <div className="relative grid">
                    <div
                        ref={stateARef}
                        className="col-start-1 row-start-1 will-change-[transform,opacity]"
                    >
                        <StateBlock
                            eyebrow={STATE_A_EYEBROW}
                            headline={STATE_A_HEADLINE}
                        />
                    </div>
                    <div
                        ref={stateBRef}
                        className="col-start-1 row-start-1 will-change-[transform,opacity]"
                    >
                        <StateBlock
                            eyebrow={STATE_B_EYEBROW}
                            headline={STATE_B_HEADLINE}
                        />
                    </div>
                </div>
                <div className="mt-6">
                    <PersistentLine />
                </div>
            </div>

            {/* Grand-prize lock — beside the text */}
            <div className="flex shrink-0 items-center justify-center md:w-72 lg:w-80">
                <GrandPrizeLock {...lockProps} />
            </div>
        </div>
    );

    /* Reduced motion: static two-up, both text states visible, no pin. */
    if (reduced) {
        return (
            <section className="bg-wc-surface py-20">
                <div className="mx-auto grid max-w-6xl items-center gap-10 px-6 md:grid-cols-[1fr_auto] md:gap-16">
                    <div className="space-y-8">
                        <StateBlock
                            eyebrow={STATE_A_EYEBROW}
                            headline={STATE_A_HEADLINE}
                        />
                        <StateBlock
                            eyebrow={STATE_B_EYEBROW}
                            headline={STATE_B_HEADLINE}
                        />
                        <PersistentLine />
                    </div>
                    <div className="flex shrink-0 items-center justify-center md:w-72">
                        <GrandPrizeLock {...lockProps} />
                    </div>
                </div>
            </section>
        );
    }

    return (
        <section className="bg-wc-surface">
            <div ref={outerRef} style={{ height: '220vh' }}>
                <div className="sticky top-0 flex h-screen items-center overflow-hidden">
                    <div
                        aria-hidden
                        className="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_55%_60%_at_72%_50%,rgba(233,167,33,0.1),transparent_70%)]"
                    />
                    {content}
                </div>
            </div>
        </section>
    );
}
