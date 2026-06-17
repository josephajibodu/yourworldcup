import { Link, usePage } from '@inertiajs/react';
import { ArrowRight, Trophy } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { bracket, home, leaderboard, predict, referrals, register } from '@/routes';

const TWITTER_HANDLE = 'joseph_ajibodu';
const TWITTER_URL = `https://x.com/${TWITTER_HANDLE}`;

const navLinks = [
    { title: 'Bracket', href: bracket() },
    { title: 'Predict', href: predict() },
    { title: 'Leaderboard', href: leaderboard() },
    { title: 'Referrals', href: referrals() },
];

function XIcon({ className }: { className?: string }) {
    return (
        <svg
            viewBox="0 0 24 24"
            aria-hidden="true"
            className={className}
            fill="currentColor"
        >
            <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
        </svg>
    );
}

export function SiteFooter() {
    const { auth } = usePage().props;

    return (
        <footer className="border-t border-wc-ink-3 bg-wc-ink text-wc-surface/65">
            <div className="mx-auto max-w-6xl px-6 py-14 md:py-16">
                <div className="grid gap-12 md:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)_minmax(0,1fr)] md:gap-10 lg:gap-16">
                    {/* Brand + social */}
                    <div className="space-y-6">
                        <Link
                            href={home()}
                            className="inline-flex items-center gap-2.5"
                        >
                            <span className="flex size-9 shrink-0 items-center justify-center rounded-full bg-wc-surface/10 text-wc-gold">
                                <Trophy className="size-4" />
                            </span>
                            <span className="font-display text-xl tracking-[0.22em] text-wc-surface">
                                YOURWORLD
                                <span className="text-wc-gold">CUP</span>
                            </span>
                        </Link>
                        <p className="max-w-sm text-sm leading-relaxed text-wc-surface/55">
                            daily world cup predictions, a living bracket, and
                            real rewards, free to play, skill-ranked.
                        </p>
                        <a
                            href={TWITTER_URL}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="inline-flex items-center gap-2.5 rounded-full border border-wc-surface/15 bg-wc-surface/5 px-4 py-2.5 text-sm font-semibold text-wc-surface transition-colors hover:border-wc-gold/40 hover:bg-wc-gold/10 hover:text-wc-surface"
                        >
                            <XIcon className="size-4 shrink-0" />
                            follow @{TWITTER_HANDLE}
                        </a>
                    </div>

                    {/* Play */}
                    <div>
                        <p className="font-mono text-[11px] font-bold tracking-[0.18em] text-wc-gold uppercase">
                            Play
                        </p>
                        <ul className="mt-4 space-y-2.5">
                            {navLinks.map((item) => (
                                <li key={item.title}>
                                    <Link
                                        href={item.href}
                                        className="text-sm font-semibold text-wc-surface/70 transition-colors hover:text-wc-surface"
                                    >
                                        {item.title}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </div>

                    {/* CTA */}
                    <div>
                        <p className="font-mono text-[11px] font-bold tracking-[0.18em] text-wc-gold uppercase">
                            get in
                        </p>
                        <p className="mt-4 text-sm leading-relaxed text-wc-surface/55">
                            make today&apos;s picks, track the bracket, and
                            climb the board for airtime and the grand prize.
                        </p>
                        <Button
                            asChild
                            variant="default"
                            size="sm"
                            className="mt-5 rounded-full bg-wc-gold px-5 font-bold text-wc-ink hover:bg-wc-gold/90"
                        >
                            <Link href={auth.user ? predict() : register()}>
                                {auth.user
                                    ? 'make today’s picks'
                                    : 'get started free'}
                                <ArrowRight className="size-4" />
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="mt-12 flex flex-col items-start justify-between gap-4 border-t border-wc-surface/10 pt-8 sm:flex-row sm:items-center">
                    <p className="text-xs text-wc-surface/40">
                        © {new Date().getFullYear()} YourWorldCup. Built for
                        World Cup 2026.
                    </p>
                    <p className="font-mono text-[11px] tracking-wider text-wc-surface/35 uppercase">
                        Free to play · Skill-ranked · World Cup 2026
                    </p>
                </div>
            </div>
        </footer>
    );
}
