import { Minus, Plus } from 'lucide-react';
import { cn } from '@/lib/utils';

interface ScoreStepperProps {
    value: number;
    min?: number;
    max?: number;
    disabled?: boolean;
    expanded?: boolean;
    label: string;
    onChange: (value: number) => void;
    onActivate?: () => void;
}

export function ScoreStepper({
    value,
    min = 0,
    max = 30,
    disabled = false,
    expanded = false,
    label,
    onChange,
    onActivate,
}: ScoreStepperProps) {
    const canDecrease = value > min;
    const canIncrease = value < max;

    if (disabled) {
        return (
            <div
                className="flex h-9 min-w-9 items-center justify-center rounded-md border border-border bg-muted/30 px-2 font-mono text-sm font-semibold tabular-nums text-muted-foreground"
                aria-label={label}
            >
                {value}
            </div>
        );
    }

    const handleNumberClick = () => {
        onActivate?.();

        if (canIncrease) {
            onChange(value + 1);
        }
    };

    const sideButtonClass = cn(
        'flex shrink-0 items-center justify-center overflow-hidden bg-secondary/40 text-muted-foreground transition-[width,opacity] duration-200 ease-out hover:bg-secondary hover:text-foreground',
        expanded ? 'w-8 opacity-100' : 'w-0 opacity-0 pointer-events-none',
    );

    return (
        <div
            className="inline-flex h-9 items-stretch overflow-hidden rounded-md border border-border bg-background"
            aria-label={label}
        >
            <button
                type="button"
                disabled={!canDecrease}
                aria-label={`Decrease ${label}`}
                tabIndex={expanded ? 0 : -1}
                onClick={() => onChange(Math.max(min, value - 1))}
                className={cn(sideButtonClass, expanded && !canDecrease && 'opacity-40')}
            >
                <Minus className="size-3 shrink-0 stroke-[2.5]" />
            </button>
            <button
                type="button"
                aria-label={`${label}: ${value}. Tap to increase.`}
                onClick={handleNumberClick}
                className={cn(
                    'flex min-w-9 shrink-0 items-center justify-center px-2 font-mono text-sm font-semibold tabular-nums transition-colors hover:bg-secondary/60',
                    expanded && 'border-x border-border',
                )}
            >
                {value}
            </button>
            <button
                type="button"
                disabled={!canIncrease}
                aria-label={`Increase ${label}`}
                tabIndex={expanded ? 0 : -1}
                onClick={() => onChange(Math.min(max, value + 1))}
                className={cn(sideButtonClass, expanded && !canIncrease && 'opacity-40')}
            >
                <Plus className="size-3 shrink-0 stroke-[2.5]" />
            </button>
        </div>
    );
}
