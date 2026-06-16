const teams = [
    {
        name: 'Mexico',
        code: 'MEX',
        flag: 'https://flagcdn.com/w80/mx.png',
        track: 'qualifier-token-a',
    },
    {
        name: 'South Africa',
        code: 'RSA',
        flag: 'https://flagcdn.com/w80/za.png',
        track: 'qualifier-token-b',
    },
    {
        name: 'Morocco',
        code: 'MAR',
        flag: 'https://flagcdn.com/w80/ma.png',
        track: 'qualifier-token-c',
    },
    {
        name: 'Senegal',
        code: 'SEN',
        flag: 'https://flagcdn.com/w80/sn.png',
        track: 'qualifier-token-d',
    },
    {
        name: 'Ghana',
        code: 'GHA',
        flag: 'https://flagcdn.com/w80/gh.png',
        track: 'qualifier-token-e',
    },
    {
        name: 'Argentina',
        code: 'ARG',
        flag: 'https://flagcdn.com/w80/ar.png',
        track: 'qualifier-token-f',
    },
    {
        name: 'Brazil',
        code: 'BRA',
        flag: 'https://flagcdn.com/w80/br.png',
        track: 'qualifier-token-g',
    },
    {
        name: 'France',
        code: 'FRA',
        flag: 'https://flagcdn.com/w80/fr.png',
        track: 'qualifier-token-h',
    },
];

export function TeamMarquee() {
    return (
        <div
            aria-label="Animated qualification funnel"
            className="relative mx-auto h-80 w-full max-w-none overflow-hidden border-y bg-wc-surface md:h-120"
            role="img"
        >
            <style>{`
                @keyframes qualifier-flow {
                    0% { offset-distance: 0%; opacity: 0; transform: scale(0.92); }
                    6% { opacity: 1; }
                    96% { opacity: 1; }
                    100% { offset-distance: 100%; opacity: 1; transform: scale(1.02); }
                }

                @keyframes exit-flow {
                    from { transform: translateX(-50%); }
                    to { transform: translateX(0); }
                }

                .qualifier-token {
                    offset-rotate: 0deg;
                    animation: qualifier-flow 7s ease-in-out infinite;
                }

                .qualifier-token-a,
                .qualifier-token-e {
                    offset-path: path("M 18 96 C 150 96, 272 122, 438 190 L 496 204");
                }

                .qualifier-token-b,
                .qualifier-token-f {
                    offset-path: path("M 0 172 C 150 172, 286 184, 438 202 L 496 206");
                }

                .qualifier-token-c,
                .qualifier-token-g {
                    offset-path: path("M 28 248 C 158 248, 286 226, 438 210 L 496 208");
                }

                .qualifier-token-d,
                .qualifier-token-h {
                    offset-path: path("M 72 324 C 190 300, 310 248, 438 218 L 496 210");
                }

                @media (prefers-reduced-motion: reduce) {
                    .qualifier-token {
                        animation: none !important;
                        offset-distance: 70%;
                    }

                    .exit-track {
                        animation: none !important;
                        transform: translateX(0) !important;
                    }
                }
            `}</style>

            <svg
                aria-hidden
                className="absolute inset-y-0 left-0 h-full w-[58%] overflow-visible md:w-[60%]"
                viewBox="0 0 560 420"
                fill="none"
            >
                <path
                    d="M -18 -18 C 118 34, 268 38, 392 72 C 470 122, 520 164, 560 204 C 520 244, 470 292, 392 348 C 268 382, 118 386, -18 438 Z"
                    fill="#FFF7F0"
                    stroke="#0A0A0B"
                    strokeWidth="1.2"
                />
                <path
                    d="M 18 96 C 150 96, 272 122, 438 190 L 496 204"
                    stroke="#0A0A0B"
                    strokeWidth="1.4"
                    strokeLinecap="round"
                />
                <path
                    d="M 0 172 C 150 172, 286 184, 438 202 L 496 206"
                    stroke="#E5132B"
                    strokeWidth="1.6"
                    strokeLinecap="round"
                />
                <path
                    d="M 28 248 C 158 248, 286 226, 438 210 L 496 208"
                    stroke="#2348C9"
                    strokeWidth="1.5"
                    strokeLinecap="round"
                />
                <path
                    d="M 72 324 C 190 300, 310 248, 438 218 L 496 210"
                    stroke="#E9A721"
                    strokeWidth="1.5"
                    strokeLinecap="round"
                />
                <rect
                    x="96"
                    y="122"
                    width="110"
                    height="80"
                    rx="8"
                    fill="#F7F5F0"
                    stroke="#0A0A0B"
                    strokeWidth="1"
                />
                <circle cx="126" cy="162" r="8" fill="#E5132B" />
                <circle cx="153" cy="162" r="8" fill="#E9A721" />
                <circle cx="180" cy="162" r="8" fill="#2348C9" />
                <rect
                    x="232"
                    y="234"
                    width="128"
                    height="88"
                    rx="8"
                    fill="#F7F5F0"
                    stroke="#0A0A0B"
                    strokeWidth="1"
                />
                <path
                    d="M 252 286 L 286 262 L 312 278 L 344 250"
                    stroke="#1FA36B"
                    strokeWidth="2"
                    strokeLinecap="round"
                />
            </svg>

            <div className="absolute inset-y-0 left-0 w-[58%] md:w-[60%]">
                {teams.map((team, index) => (
                    <div
                        key={team.code}
                        className={`qualifier-token ${team.track} absolute left-0 top-0 z-10 flex items-center gap-2 rounded-xl border border-wc-ink bg-wc-surface px-3 py-2 shadow-sm`}
                        style={{ animationDelay: `${index * -0.7}s` }}
                    >
                        <img
                            src={team.flag}
                            alt=""
                            className="h-5 w-7 rounded-[2px] object-cover"
                        />
                        <span className="font-mono text-xs font-bold tracking-wider text-wc-ink">
                            {team.code}
                        </span>
                    </div>
                ))}
            </div>

            <div className="absolute left-[50%] top-1/2 z-20 flex -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-xl border border-wc-ink bg-wc-surface px-4 py-3 shadow-sm md:left-[49%]">
                <div className="grid size-16 place-items-center rounded-full border border-wc-ink bg-wc-gold text-wc-ink">
                    <span className="font-mono text-sm font-black">R32</span>
                </div>
            </div>

            <div className="absolute inset-y-0 left-1/2 right-0 flex items-center justify-start overflow-hidden pl-12 md:pl-20">
                <span className="pointer-events-none absolute left-0 top-1/2 h-px w-full -translate-y-1/2 bg-wc-ink/20" />
                <span className="pointer-events-none absolute left-0 top-1/2 h-0.5 w-[150%] -translate-y-1/2 bg-wc-primary" />

                <div
                    className="exit-track relative z-10 flex shrink-0 gap-8 md:gap-12"
                    style={{ animation: 'exit-flow 18s linear infinite' }}
                >
                    {[...teams, ...teams].map((team, index) => (
                        <div
                            key={`${team.code}-${index}`}
                            className="flex h-15 w-18 shrink-0 items-center justify-center rounded-2xl border border-wc-ink bg-wc-surface shadow-[0_0_0_4px_#F0D6DF]"
                        >
                            <img
                                src={team.flag}
                                alt=""
                                className="h-7 w-10 rounded-[3px] object-cover"
                            />
                        </div>
                    ))}
                </div>
            </div>

            <div className="absolute bottom-4 left-4 rounded-full border border-wc-ink bg-wc-surface px-3 py-1.5 font-mono text-[10px] font-semibold tracking-wider text-wc-ink uppercase">
                48 in
            </div>
            <div className="absolute bottom-4 right-4 rounded-full border border-wc-ink bg-wc-ink px-3 py-1.5 font-mono text-[10px] font-semibold tracking-wider text-wc-gold uppercase">
                1 champion
            </div>
        </div>
    );
}
