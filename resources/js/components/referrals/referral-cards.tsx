import { Check, Copy, Link2, Users } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

interface ReferralShareCardProps {
    code: string;
    url: string;
}

async function copyText(text: string): Promise<boolean> {
    try {
        await navigator.clipboard.writeText(text);
        return true;
    } catch {
        return false;
    }
}

export function ReferralShareCard({ code, url }: ReferralShareCardProps) {
    const [copied, setCopied] = useState<'code' | 'url' | null>(null);

    const handleCopy = async (value: string, kind: 'code' | 'url') => {
        const ok = await copyText(value);
        if (!ok) {
            return;
        }

        setCopied(kind);
        window.setTimeout(() => setCopied(null), 2000);
    };

    return (
        <div className="min-w-0 overflow-hidden rounded-xl border border-wc-ink/10 bg-card p-5 md:p-6">
            <div className="flex items-center gap-2">
                <h2 className="text-lg font-bold tracking-tight text-wc-ink">
                    your invite link
                </h2>
            </div>
            <p className="mt-2 text-sm leading-relaxed text-muted-foreground">
                share this link — when a friend signs up and makes their first
                prediction, you earn bonus leaderboard points.
            </p>

            <div className="mt-5 space-y-4">
                <div>
                    <p className="font-mono text-[11px] font-bold tracking-[0.18em] text-wc-ink/50 uppercase">
                        referral code
                    </p>
                    <div className="mt-2 flex min-w-0 items-center gap-2">
                        <code className="block min-w-0 flex-1 truncate rounded-lg border border-wc-ink/10 bg-wc-surface px-4 py-3 font-mono text-lg font-bold tracking-widest text-wc-ink tabular-nums">
                            {code}
                        </code>
                        <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            className="size-11 shrink-0 rounded-full"
                            onClick={() => handleCopy(code, 'code')}
                            aria-label="Copy referral code"
                        >
                            {copied === 'code' ? (
                                <Check className="size-4 text-wc-green" />
                            ) : (
                                <Copy className="size-4" />
                            )}
                        </Button>
                    </div>
                </div>

                <div>
                    <p className="font-mono text-[11px] font-bold tracking-[0.18em] text-wc-ink/50 uppercase">
                        share link
                    </p>
                    <div className="mt-2 flex min-w-0 flex-col gap-2 sm:flex-row sm:items-center">
                        <div className="min-w-0 truncate rounded-lg border border-wc-ink/10 bg-wc-surface px-4 py-3 text-sm text-wc-ink/80 sm:flex-1">
                            {url}
                        </div>
                        <Button
                            type="button"
                            variant="ink"
                            className="w-full shrink-0 rounded-full px-4 sm:w-auto"
                            onClick={() => handleCopy(url, 'url')}
                        >
                            {copied === 'url' ? (
                                <>
                                    <Check className="size-4" />
                                    copied
                                </>
                            ) : (
                                <>
                                    <Copy className="size-4" />
                                    copy link
                                </>
                            )}
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    );
}

interface ReferralStatsCardProps {
    totalReferrals: number;
    totalPoints: number;
    todayCount: number;
    dailyCap: number;
    hasMadePrediction: boolean;
}

function EligibilityItem({ met, label }: { met: boolean; label: string }) {
    return (
        <li className="flex items-start gap-2 text-sm">
            <span
                className={cn(
                    'mt-0.5 grid size-5 shrink-0 place-items-center rounded-full font-mono text-[10px] font-bold',
                    met
                        ? 'bg-wc-green/15 text-wc-green'
                        : 'bg-wc-ink/8 text-wc-ink/40',
                )}
            >
                {met ? '✓' : '·'}
            </span>
            <span className={met ? 'text-wc-ink' : 'text-wc-ink/60'}>
                {label}
            </span>
        </li>
    );
}

export function ReferralStatsCard({
    totalReferrals,
    totalPoints,
    todayCount,
    dailyCap,
    hasMadePrediction,
}: ReferralStatsCardProps) {
    return (
        <div className="min-w-0 overflow-hidden rounded-xl border border-wc-ink/10 bg-card p-5 md:p-6">
            <div className="flex items-center gap-2">
                <h2 className="text-lg font-bold tracking-tight text-wc-ink">
                    your stats
                </h2>
            </div>

            <dl className="mt-5 grid min-w-0 grid-cols-2 gap-4">
                <div className="rounded-lg bg-wc-surface px-4 py-3">
                    <dt className="font-mono text-[10px] font-bold tracking-[0.16em] text-wc-ink/50 uppercase">
                        referrals
                    </dt>
                    <dd className="mt-1 font-mono text-2xl font-bold text-wc-ink tabular-nums">
                        {totalReferrals}
                    </dd>
                </div>
                <div className="rounded-lg bg-wc-surface px-4 py-3">
                    <dt className="font-mono text-[10px] font-bold tracking-[0.16em] text-wc-ink/50 uppercase">
                        bonus pts
                    </dt>
                    <dd className="mt-1 font-mono text-2xl font-bold text-wc-ink tabular-nums">
                        {totalPoints}
                    </dd>
                </div>
            </dl>

            <div className="mt-4 rounded-lg border border-wc-ink/8 bg-wc-surface/60 px-4 py-3">
                <p className="font-mono text-[10px] font-bold tracking-[0.16em] text-wc-ink/50 uppercase">
                    today&apos;s credits
                </p>
                <p className="mt-1 font-mono text-sm font-semibold text-wc-ink tabular-nums">
                    {todayCount}
                    <span className="text-wc-ink/40"> / {dailyCap}</span>
                </p>
            </div>

            {!hasMadePrediction && (
                <div className="mt-5 border-t border-wc-ink/8 pt-5">
                    <p className="text-sm font-semibold text-wc-ink">
                        earn referral points when you:
                    </p>
                    <ul className="mt-3 space-y-2">
                        <EligibilityItem
                            met={hasMadePrediction}
                            label="make your first prediction before your friend does"
                        />
                    </ul>
                </div>
            )}
        </div>
    );
}
