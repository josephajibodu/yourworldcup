import { cn } from '@/lib/utils';

interface LiveIndicatorProps {
    className?: string;
    label?: string;
}

export function LiveIndicator({ className, label = 'Live' }: LiveIndicatorProps) {
    return (
        <span
            className={cn('inline-flex items-center gap-1.5', className)}
            aria-label={label}
        >
            <span className="relative inline-flex size-2 shrink-0">
                <span className="absolute inline-flex size-full animate-ping rounded-full bg-wc-green opacity-75" />
                <span className="relative inline-flex size-2 rounded-full bg-wc-green" />
            </span>
            {label !== '' && (
                <span className="font-mono text-[10px] font-semibold tracking-wider text-wc-green uppercase">
                    {label}
                </span>
            )}
        </span>
    );
}
