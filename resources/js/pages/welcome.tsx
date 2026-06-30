import { Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { FeatureExplorer } from '@/components/landing/feature-explorer';
import { PrizesReveal } from '@/components/landing/prizes-reveal';
import { QualificationFlow } from '@/components/landing/qualification-flow';
import { QualificationFlowMobile } from '@/components/landing/qualification-flow-mobile';
import { ProductShell } from '@/components/product-shell';
import { SeoHead } from '@/components/seo-head';
import { Button } from '@/components/ui/button';
import { seo } from '@/lib/seo';
import { bracket, predict } from '@/routes';

export default function Welcome() {
    return (
        <>
            <SeoHead {...seo.home} />
            <ProductShell>
                <section className="overflow-hidden bg-wc-surface text-wc-ink">
                    <div className="mx-auto flex min-h-[calc(100svh-8rem)] max-w-6xl flex-col items-center px-6 py-16 text-center lg:py-20">
                        <div className="max-w-4xl">
                            <h1 className="text-5xl leading-[0.96] tracking-tight text-balance sm:text-6xl">
                                follow the{' '}
                                <span className="font-['Caveat'] font-light text-wc-gold">
                                    World Cup
                                </span>{' '}
                                like it is happening in your hands.
                            </h1>
                            <p className="mx-auto mt-6 max-w-xl text-base leading-7 text-muted-foreground md:text-lg">
                                pick match winners, call exact scores, track the
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
                                    <Link href={predict()}>
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

                        <div className="block w-screen px-0 pt-8 md:hidden">
                            <QualificationFlowMobile />
                        </div>
                        <div className="hidden w-screen px-0 pt-10 md:block">
                            <QualificationFlow />
                        </div>
                    </div>
                </section>

                <section
                    id="how"
                    className="mx-auto max-w-6xl px-6 py-16 md:py-24"
                >
                    <div className="mx-auto mb-10 max-w-2xl text-center">
                        <h2 className="text-xl tracking-tight text-wc-ink sm:text-5xl">
                            everything you need to play
                        </h2>
                        <p className="mt-3 text-base leading-7 text-muted-foreground">
                            predict daily, follow the bracket as it unfolds, and
                            climb the board for real rewards.
                        </p>
                    </div>
                    <FeatureExplorer />
                </section>

                <PrizesReveal />
            </ProductShell>
        </>
    );
}
