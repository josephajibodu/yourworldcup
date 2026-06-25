import { Link } from '@inertiajs/react';
import { Medal } from 'lucide-react';
import { cn } from '@/lib/utils';
import { bestThirds } from '@/routes';

interface ThirdPlaceRankingFloatingLinkProps {
    className?: string;
}

export function ThirdPlaceRankingFloatingLink({
    className,
}: ThirdPlaceRankingFloatingLinkProps) {
    return (
        <Link
            href={bestThirds()}
            className={cn(
                'fixed top-28 left-4 z-40 inline-flex items-center gap-2 rounded-full border border-wc-gold/35 bg-wc-surface/95 px-3.5 py-2 text-xs font-semibold text-wc-ink shadow-[0_10px_24px_rgba(10,10,11,0.12)] backdrop-blur-sm transition-colors hover:bg-wc-gold/10 sm:left-6 sm:px-4 sm:py-2.5 sm:text-sm',
                className,
            )}
        >
            <Medal className="size-4 shrink-0 text-wc-gold" aria-hidden />
            <span className="max-w-[9rem] leading-tight sm:max-w-none">
                Check 3rd-place ranking
            </span>
        </Link>
    );
}
