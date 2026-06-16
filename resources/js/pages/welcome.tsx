import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowRight, Gift, Network, Target, Trophy } from 'lucide-react';
import { dashboard, login, register } from '@/routes';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

const features = [
    {
        icon: Target,
        accent: 'bg-wc-primary/10 text-wc-primary',
        title: 'Daily predictions',
        body: 'Call the winner of every match — add the exact score for bonus points, and double down with one banker pick a day.',
    },
    {
        icon: Network,
        accent: 'bg-wc-blue/10 text-wc-blue',
        title: 'Living bracket',
        body: 'All 12 groups flow into the knockouts, updating in real time as results land. Follow any team’s road to the final.',
    },
    {
        icon: Gift,
        accent: 'bg-wc-green/10 text-wc-green',
        title: 'Real rewards',
        body: 'Top the daily board for airtime, paid straight to your verified phone. Climb the overall table for the grand prize.',
    },
];

export default function Welcome() {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Predict the World Cup, daily" />
            <div className="min-h-screen bg-background font-sans text-foreground">
                <header className="bg-wc-ink text-wc-surface">
                    <nav className="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
                        <Link href="/" className="flex items-center gap-2.5">
                            <span className="flex size-8 items-center justify-center rounded-md bg-wc-gold text-wc-ink">
                                <Trophy className="size-5" />
                            </span>
                            <span className="font-display text-2xl tracking-wide">
                                YOURWORLD<span className="text-wc-gold">CUP</span>
                            </span>
                        </Link>
                        <div className="hidden items-center gap-7 text-sm font-medium text-wc-surface/70 md:flex">
                            <span className="cursor-default">Bracket</span>
                            <span className="cursor-default">Predict</span>
                            <span className="cursor-default">Leaderboard</span>
                            <span className="cursor-default text-wc-surface/45">How it works</span>
                        </div>
                        <div className="flex items-center gap-2">
                            {auth.user ? (
                                <Button asChild variant="gold" size="sm">
                                    <Link href={dashboard()}>Dashboard</Link>
                                </Button>
                            ) : (
                                <>
                                    <Button
                                        asChild
                                        variant="ghost"
                                        size="sm"
                                        className="text-wc-surface hover:bg-wc-ink-2 hover:text-wc-surface"
                                    >
                                        <Link href={login()}>Log in</Link>
                                    </Button>
                                    <Button asChild variant="gold" size="sm">
                                        <Link href={register()}>Play free</Link>
                                    </Button>
                                </>
                            )}
                        </div>
                    </nav>
                </header>

                <section className="relative overflow-hidden bg-wc-primary text-white">
                    <div
                        aria-hidden
                        className="pointer-events-none absolute inset-0"
                        style={{
                            background:
                                'radial-gradient(120% 85% at 50% 115%, #E9A721 0%, rgba(233,167,33,0.35) 30%, transparent 62%)',
                        }}
                    />
                    <div className="relative mx-auto max-w-6xl px-6 py-20 md:py-28">
                        <Badge
                            variant="ink"
                            className="mb-6 gap-1.5 font-mono text-[11px] tracking-wider"
                        >
                            <span className="inline-block size-1.5 rounded-full bg-wc-gold" />
                            TODAY · 3 MATCHES · LOCKS 17:00 WAT
                        </Badge>
                        <h1 className="max-w-3xl font-display text-5xl leading-[0.95] tracking-tight uppercase md:text-7xl">
                            Predict every match.
                            <br />
                            <span className="text-wc-gold">Climb the board.</span>
                        </h1>
                        <p className="mt-6 max-w-xl text-lg text-white/90">
                            Make your daily picks before kickoff, watch the living
                            bracket update in real time, and top the leaderboard for
                            airtime — and the grand prize.
                        </p>
                        <div className="mt-9 flex flex-wrap items-center gap-3">
                            <Button asChild variant="gold" size="lg">
                                <Link href={auth.user ? dashboard() : register()}>
                                    Make today’s picks
                                    <ArrowRight className="size-4" />
                                </Link>
                            </Button>
                            <a
                                href="#how"
                                className="inline-flex items-center gap-2 rounded-md border border-white/45 px-6 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-white/10"
                            >
                                View the bracket
                            </a>
                        </div>
                        <div className="mt-10 flex flex-wrap gap-x-8 gap-y-2 font-mono text-sm text-white/80">
                            <span>
                                <span className="text-wc-gold">FREE</span> to play
                            </span>
                            <span>
                                <span className="text-wc-gold">SKILL</span>-ranked
                            </span>
                            <span>
                                <span className="text-wc-gold">DAILY</span> airtime
                            </span>
                        </div>
                    </div>
                </section>

                <section id="how" className="mx-auto max-w-6xl px-6 py-16 md:py-24">
                    <div className="grid gap-5 md:grid-cols-3">
                        {features.map((feature) => (
                            <div
                                key={feature.title}
                                className="rounded-xl border bg-card p-6 text-card-foreground"
                            >
                                <span
                                    className={`flex size-11 items-center justify-center rounded-lg ${feature.accent}`}
                                >
                                    <feature.icon className="size-5" />
                                </span>
                                <h2 className="mt-5 font-display text-xl tracking-wide uppercase">
                                    {feature.title}
                                </h2>
                                <p className="mt-2 text-sm leading-relaxed text-muted-foreground">
                                    {feature.body}
                                </p>
                            </div>
                        ))}
                    </div>
                </section>

                <footer className="bg-wc-ink text-wc-surface/60">
                    <div className="mx-auto flex max-w-6xl flex-col items-center justify-between gap-3 px-6 py-8 text-sm sm:flex-row">
                        <span className="font-display text-lg tracking-wide text-wc-surface">
                            YOURWORLD<span className="text-wc-gold">CUP</span>
                        </span>
                        <span className="font-mono text-xs tracking-wider">
                            FREE TO PLAY · SKILL-RANKED · WORLD CUP 2026
                        </span>
                    </div>
                </footer>
            </div>
        </>
    );
}
