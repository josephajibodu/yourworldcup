import { Link, usePage } from '@inertiajs/react';
import { ArrowRight, UserPlus } from 'lucide-react';
import { ProductShell } from '@/components/product-shell';
import {
    ReferralShareCard,
    ReferralStatsCard,
} from '@/components/referrals/referral-cards';
import { SeoHead } from '@/components/seo-head';
import { Button } from '@/components/ui/button';
import { seo } from '@/lib/seo';
import { login, predict, register } from '@/routes';

interface ReferralRules {
    pointsPerReferral: number;
    dailyCap: number;
}

interface ReferralShare {
    code: string;
    url: string;
}

interface ReferralStats {
    totalReferrals: number;
    totalPoints: number;
    todayCount: number;
    hasMadePrediction: boolean;
}

interface ReferralsPageProps {
    rules: ReferralRules;
    share: ReferralShare | null;
    stats: ReferralStats | null;
    [key: string]: unknown;
}

export default function Referrals() {
    const { rules, share, stats, auth } = usePage<ReferralsPageProps>().props;
    const isAuthenticated = auth.user !== null;

    return (
        <>
            <SeoHead {...seo.referrals} />
            <ProductShell>
                <div className="mx-auto min-w-0 max-w-6xl px-4 sm:py-10 sm:px-6 md:py-14">
                    <div className="max-w-2xl">
                        <h1 className="text-4xl font-bold tracking-tight text-wc-ink md:text-5xl">
                            referrals
                        </h1>
                        <p className="mt-3 text-base leading-relaxed text-muted-foreground">
                            invite friends to play — when they sign up through
                            your link and make their first prediction, you pick
                            up bonus points on the daily and overall
                            leaderboards.
                        </p>
                    </div>

                    <div className="mt-8 flex min-w-0 gap-4 rounded-2xl border border-wc-primary/20 bg-wc-primary/6 p-5 md:items-center">
                        <div className="grid size-14 shrink-0 place-items-center rounded-full bg-wc-primary/12 ring-1 ring-wc-primary/25">
                            <UserPlus className="size-7 text-wc-primary" />
                        </div>
                        <div className="min-w-0">
                            <p className="font-mono text-[11px] font-bold tracking-[0.18em] text-wc-primary uppercase">
                                how it works
                            </p>
                            <p className="mt-1 text-sm font-semibold text-wc-ink">
                                {rules.pointsPerReferral} bonus point when a
                                friend you referred makes their first
                                prediction, up to {rules.dailyCap} per day.
                            </p>
                            <p className="mt-1 text-sm leading-relaxed text-wc-ink/60">
                                you must have made at least one prediction
                                before they do. referral codes cannot be added
                                after registration.
                            </p>
                        </div>
                    </div>

                    {isAuthenticated && share !== null && stats !== null ? (
                        <div className="mt-10 grid min-w-0 gap-8 md:grid-cols-2">
                            <ReferralShareCard
                                code={share.code}
                                url={share.url}
                            />
                            <ReferralStatsCard
                                totalReferrals={stats.totalReferrals}
                                totalPoints={stats.totalPoints}
                                todayCount={stats.todayCount}
                                dailyCap={rules.dailyCap}
                                hasMadePrediction={stats.hasMadePrediction}
                            />
                        </div>
                    ) : (
                        <div className="mt-10 rounded-xl border border-dashed border-wc-ink/15 bg-card px-6 py-12 text-center">
                            <p className="text-sm text-muted-foreground">
                                sign in to get your personal invite link and
                                track referral points.
                            </p>
                            <div className="mt-6 flex flex-wrap items-center justify-center gap-3">
                                <Button
                                    asChild
                                    variant="ink"
                                    size="lg"
                                    className="rounded-full"
                                >
                                    <Link href={register()}>
                                        create account
                                        <ArrowRight className="size-4" />
                                    </Link>
                                </Button>
                                <Button
                                    asChild
                                    variant="ghost"
                                    size="lg"
                                    className="rounded-full"
                                >
                                    <Link href={login()}>log in</Link>
                                </Button>
                            </div>
                        </div>
                    )}

                    {isAuthenticated && (
                        <p className="mt-8 text-sm text-muted-foreground">
                            referral points count toward the same leaderboards
                            as your predictions.{' '}
                            <Link
                                href={predict()}
                                className="font-semibold text-wc-ink underline-offset-4 hover:underline"
                            >
                                make today&apos;s picks
                            </Link>
                        </p>
                    )}
                </div>
            </ProductShell>
        </>
    );
}
