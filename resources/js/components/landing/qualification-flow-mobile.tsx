import { useEffect, useState } from 'react';

interface Team {
    name: string;
    code: string;
    flag: string;
}

const TEAMS: Team[] = [
    { name: 'Brazil',       code: 'BRA', flag: 'https://flagcdn.com/w80/br.png' },
    { name: 'France',       code: 'FRA', flag: 'https://flagcdn.com/w80/fr.png' },
    { name: 'Morocco',      code: 'MAR', flag: 'https://flagcdn.com/w80/ma.png' },
    { name: 'Argentina',    code: 'ARG', flag: 'https://flagcdn.com/w80/ar.png' },
    { name: 'Mexico',       code: 'MEX', flag: 'https://flagcdn.com/w80/mx.png' },
    { name: 'Portugal',     code: 'POR', flag: 'https://flagcdn.com/w80/pt.png' },
    { name: 'Senegal',      code: 'SEN', flag: 'https://flagcdn.com/w80/sn.png' },
    { name: 'South Africa', code: 'RSA', flag: 'https://flagcdn.com/w80/za.png' },
];

const CHAMPION_CYCLE: Team[] = [
    TEAMS[0], // Brazil
    TEAMS[2], // Morocco
    TEAMS[4], // Mexico
    TEAMS[1], // France
    TEAMS[3], // Argentina
    TEAMS[5], // Portugal
];

const CYCLE_MS = 14_000;

interface LaneDef {
    teams: Team[];
    dir: 'ltr' | 'rtl';
    sec: number;
    offset: string;
    w: number;
    h: number;
    gap: number;
    opacity: number;
    top: string;
    blur?: number;
}

// 4 lanes with depth variation — background lanes are small/faded, foreground is large/sharp
const LANES: LaneDef[] = [
    {
        // Deep background — tiny, slow, very faded
        teams: [TEAMS[0], TEAMS[1], TEAMS[4], TEAMS[6], TEAMS[2], TEAMS[7]],
        dir: 'ltr', sec: 40, offset: '-8s',
        w: 46, h: 30, gap: 22,
        opacity: 0.18, top: '9%', blur: 1.5,
    },
    {
        // Mid background — RTL, medium fade
        teams: [TEAMS[3], TEAMS[5], TEAMS[6], TEAMS[0], TEAMS[7], TEAMS[1]],
        dir: 'rtl', sec: 28, offset: '-5s',
        w: 64, h: 43, gap: 30,
        opacity: 0.52, top: '34%',
    },
    {
        // Primary foreground — LTR, full opacity, largest flags
        teams: [TEAMS[0], TEAMS[2], TEAMS[4], TEAMS[1], TEAMS[5], TEAMS[7]],
        dir: 'ltr', sec: 22, offset: '-14s',
        w: 88, h: 58, gap: 42,
        opacity: 1.0, top: '63%',
    },
    {
        // Far background — RTL, barely visible
        teams: [TEAMS[7], TEAMS[3], TEAMS[2], TEAMS[6], TEAMS[4], TEAMS[5]],
        dir: 'rtl', sec: 34, offset: '-20s',
        w: 36, h: 24, gap: 18,
        opacity: 0.13, top: '89%', blur: 2,
    },
];

function Lane({ lane, championCode }: { lane: LaneDef; championCode: string }) {
    // Double the list so the seamless loop works:
    // LTR animates translateX(-50% → 0), RTL animates translateX(0 → -50%)
    const doubled = [...lane.teams, ...lane.teams];

    return (
        <div
            aria-hidden
            style={{
                position: 'absolute',
                left: 0,
                right: 0,
                top: lane.top,
                transform: 'translateY(-50%)',
                overflow: 'hidden',
                opacity: lane.opacity,
            }}
        >
            <div
                className="mob-lane"
                style={{
                    display: 'flex',
                    alignItems: 'center',
                    width: 'max-content',
                    filter: lane.blur ? `blur(${lane.blur}px)` : undefined,
                    // LTR: enter from left → track moves right (-50% → 0)
                    // RTL: enter from right → track moves left (0 → -50%)
                    animationName: lane.dir === 'ltr' ? 'mob-ltr' : 'mob-rtl',
                    animationDuration: `${lane.sec}s`,
                    animationDelay: lane.offset,
                    animationTimingFunction: 'linear',
                    animationIterationCount: 'infinite',
                }}
            >
                {doubled.map((team, i) => {
                    const isChamp = team.code === championCode;
                    return (
                        <img
                            key={`${team.code}-${i}`}
                            src={team.flag}
                            alt=""
                            style={{
                                width: lane.w,
                                height: lane.h,
                                objectFit: 'cover',
                                borderRadius: Math.round(lane.w * 0.11),
                                marginRight: lane.gap,
                                flexShrink: 0,
                                boxShadow: isChamp
                                    ? '0 6px 24px rgba(233,167,33,0.65), 0 0 0 2.5px #E9A721'
                                    : '0 4px 14px rgba(10,10,11,0.18)',
                                transition: 'box-shadow 500ms ease',
                            }}
                        />
                    );
                })}
            </div>
        </div>
    );
}

function ChampionMoment({ team }: { team: Team }) {
    return (
        <div
            aria-hidden
            style={{
                position: 'absolute',
                inset: 0,
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                zIndex: 8,
                pointerEvents: 'none',
            }}
        >
            {/* Soft backdrop circle to lift the flag above the lanes */}
            <div
                style={{
                    position: 'absolute',
                    width: 200,
                    height: 200,
                    borderRadius: '50%',
                    background:
                        'radial-gradient(circle, rgba(247,245,240,0.88) 0%, rgba(247,245,240,0.45) 52%, transparent 72%)',
                    animation: `mob-champion-appear ${CYCLE_MS / 1000}s ease-in-out forwards`,
                }}
            />

            <div
                className="mob-champion"
                style={{
                    display: 'flex',
                    flexDirection: 'column',
                    alignItems: 'center',
                    gap: 10,
                    animation: `mob-champion-appear ${CYCLE_MS / 1000}s ease-in-out forwards`,
                }}
            >
                {/* Flag with gold glow */}
                <div style={{ position: 'relative' }}>
                    <div
                        style={{
                            position: 'absolute',
                            inset: -22,
                            borderRadius: '50%',
                            background:
                                'radial-gradient(circle, rgba(233,167,33,0.5) 0%, rgba(233,167,33,0.2) 48%, transparent 70%)',
                            animation: 'mob-glow-pulse 2.2s ease-in-out infinite',
                        }}
                    />
                    <img
                        src={team.flag}
                        alt={team.name}
                        style={{
                            display: 'block',
                            width: 120,
                            height: 80,
                            objectFit: 'cover',
                            borderRadius: 12,
                            border: '2.5px solid rgba(233,167,33,0.9)',
                            boxShadow:
                                '0 22px 55px rgba(10,10,11,0.35), 0 0 32px rgba(233,167,33,0.42)',
                            position: 'relative',
                            zIndex: 1,
                        }}
                    />
                </div>

                {/* Country badge */}
                <div
                    style={{
                        display: 'flex',
                        alignItems: 'center',
                        gap: 6,
                        padding: '4px 14px',
                        borderRadius: 999,
                        background: 'rgba(10,10,11,0.74)',
                        backdropFilter: 'blur(10px)',
                        WebkitBackdropFilter: 'blur(10px)',
                        border: '1px solid rgba(233,167,33,0.4)',
                    }}
                >
                    <img
                        src="/images/world-cup-icon.png"
                        alt=""
                        style={{ width: 14, height: 14, objectFit: 'contain' }}
                    />
                    <span
                        style={{
                            fontFamily: 'JetBrains Mono, monospace',
                            fontSize: 10,
                            fontWeight: 900,
                            letterSpacing: '0.24em',
                            color: '#E9A721',
                            textTransform: 'uppercase',
                        }}
                    >
                        {team.name}
                    </span>
                </div>
            </div>
        </div>
    );
}

export function QualificationFlowMobile() {
    const [idx, setIdx] = useState(0);
    const champion = CHAMPION_CYCLE[idx % CHAMPION_CYCLE.length];

    useEffect(() => {
        const id = window.setInterval(
            () => setIdx((n) => (n + 1) % CHAMPION_CYCLE.length),
            CYCLE_MS,
        );
        return () => window.clearInterval(id);
    }, []);

    return (
        <div
            aria-label="Animated tournament qualification montage"
            role="img"
            className="relative w-full overflow-hidden border-y bg-wc-surface"
            style={{ height: 220 }}
        >
            <style>{CSS}</style>

            {LANES.map((lane, i) => (
                <Lane key={i} lane={lane} championCode={champion.code} />
            ))}

            {/* key forces remount so champion-appear animation restarts each cycle */}
            <ChampionMoment key={idx} team={champion} />

            {/* Left / right vignette */}
            <div
                aria-hidden
                style={{
                    position: 'absolute',
                    inset: 0,
                    zIndex: 20,
                    pointerEvents: 'none',
                    background:
                        'linear-gradient(90deg,#F7F5F0 0%,rgba(247,245,240,0.88) 14%,rgba(247,245,240,0) 30%,rgba(247,245,240,0) 70%,rgba(247,245,240,0.88) 86%,#F7F5F0 100%)',
                }}
            />
            {/* Top / bottom vignette */}
            <div
                aria-hidden
                style={{
                    position: 'absolute',
                    inset: 0,
                    zIndex: 20,
                    pointerEvents: 'none',
                    background:
                        'linear-gradient(180deg,#F7F5F0 0%,rgba(247,245,240,0) 20%,rgba(247,245,240,0) 80%,#F7F5F0 100%)',
                }}
            />

            {/* Subtle gold light streaks */}
            <div className="mob-streak mob-streak-1" aria-hidden />
            <div className="mob-streak mob-streak-2" aria-hidden />
        </div>
    );
}

const CSS = `
    /* LTR: track moves right so flags enter from the left */
    @keyframes mob-ltr {
        from { transform: translateX(-50%); }
        to   { transform: translateX(0); }
    }

    /* RTL: track moves left so flags enter from the right */
    @keyframes mob-rtl {
        from { transform: translateX(0); }
        to   { transform: translateX(-50%); }
    }

    @keyframes mob-champion-appear {
        0%, 6%       { opacity: 0; transform: scale(0.68); }
        18%, 72%     { opacity: 1; transform: scale(1);    }
        84%, 100%    { opacity: 0; transform: scale(0.82); }
    }

    @keyframes mob-glow-pulse {
        0%, 100% { opacity: 0.65; transform: scale(0.88); }
        50%      { opacity: 1;    transform: scale(1.14); }
    }

    @keyframes mob-streak-move {
        0%   { transform: translateX(-120%) skewX(-8deg); opacity: 0;   }
        25%  {                                             opacity: 0.7; }
        75%  {                                             opacity: 0.7; }
        100% { transform: translateX(120%)  skewX(-8deg); opacity: 0;   }
    }

    .mob-lane {
        will-change: transform;
    }

    .mob-streak {
        position: absolute;
        left: -10%;
        right: -10%;
        height: 1px;
        background: linear-gradient(
            90deg,
            transparent,
            rgba(233,167,33,0.4) 30%,
            rgba(255,255,255,0.55) 50%,
            rgba(233,167,33,0.4) 70%,
            transparent
        );
        pointer-events: none;
        z-index: 6;
        animation: mob-streak-move ease-in-out infinite;
        opacity: 0;
    }

    .mob-streak-1 { top: 22%; animation-duration: 8s;  animation-delay: -1.5s; }
    .mob-streak-2 { top: 67%; animation-duration: 11s; animation-delay: -6s;   }

    @media (prefers-reduced-motion: reduce) {
        .mob-lane    { animation: none !important; }
        .mob-champion { animation: none !important; opacity: 1 !important; }
        .mob-streak  { display: none; }
    }
`;
