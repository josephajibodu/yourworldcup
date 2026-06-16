import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowRight, Gift, Network, Target } from 'lucide-react';
import { TeamMarquee } from '@/components/landing/team-marquee';
import { SiteHeader } from '@/components/site-header';
import { Button } from '@/components/ui/button';
import { bracket, predict, register } from '@/routes';

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
                <SiteHeader />

                <section className="overflow-hidden bg-wc-surface text-wc-ink">
                    <div className="mx-auto flex min-h-[calc(100svh-8rem)] max-w-6xl flex-col items-center px-6 py-16 text-center lg:py-20">
                        <div className="max-w-4xl">
                            <p className="mx-auto mb-6 max-w-xs font-mono text-xs leading-relaxed tracking-wider text-muted-foreground uppercase">
                                Living bracket · daily predictions · skill-ranked
                                rewards
                            </p>
                            <h1 className="text-5xl leading-[0.96] font-extrabold tracking-tight text-balance sm:text-6xl md:text-7xl">
                                Follow the World Cup like it is happening in
                                your hands.
                            </h1>
                            <p className="mx-auto mt-6 max-w-xl text-base leading-7 text-muted-foreground md:text-lg">
                                Pick match winners, call exact scores, track the
                                bracket as teams move forward, and climb daily
                                and overall leaderboards.
                            </p>
                            <div className="mt-8 flex flex-wrap items-center justify-center gap-3">
                                <Button
                                    asChild
                                    variant="ink"
                                    size="lg"
                                    className="rounded-full"
                                >
                                    <Link
                                        href={auth.user ? predict() : register()}
                                    >
                                        Make today’s picks
                                        <ArrowRight className="size-4" />
                                    </Link>
                                </Button>
                                <Link
                                    href={bracket()}
                                    className="inline-flex items-center gap-2 rounded-full border border-border bg-card px-6 py-2.5 text-sm font-semibold text-wc-ink transition-colors hover:bg-wc-surface-2"
                                >
                                    View the bracket
                                </Link>
                            </div>
                        </div>

                        <div className="w-screen px-0 pt-10">
                            <TeamMarquee />
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
                                <h2 className="mt-5 text-lg font-bold tracking-tight">
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
                        <span className="text-lg font-extrabold tracking-tight text-wc-surface">
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
