import { Link, type LinkComponentProps } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { Button } from '@/components/ui/button';

interface DateNavProps {
    dates: string[];
    selectedDate: string | null;
    formatLabel: (date: string) => string;
    hrefForDate: (date: string) => LinkComponentProps['href'];
}

export function DateNav({
    dates,
    selectedDate,
    formatLabel,
    hrefForDate,
}: DateNavProps) {
    const index = selectedDate ? dates.indexOf(selectedDate) : -1;
    const prevDate = index > 0 ? dates[index - 1] : null;
    const nextDate =
        index >= 0 && index < dates.length - 1 ? dates[index + 1] : null;

    return (
        <div className="flex items-center justify-end gap-1">
            <Button
                asChild={prevDate !== null}
                variant="ghost"
                size="icon"
                className="rounded-full"
                disabled={prevDate === null}
            >
                {prevDate !== null ? (
                    <Link href={hrefForDate(prevDate)}>
                        <ChevronLeft className="size-4" />
                    </Link>
                ) : (
                    <span>
                        <ChevronLeft className="size-4" />
                    </span>
                )}
            </Button>
            <span className="min-w-36 text-center font-mono text-xs font-semibold tracking-wider text-wc-ink/70 uppercase tabular-nums">
                {selectedDate ? formatLabel(selectedDate) : '—'}
            </span>
            <Button
                asChild={nextDate !== null}
                variant="ghost"
                size="icon"
                className="rounded-full"
                disabled={nextDate === null}
            >
                {nextDate !== null ? (
                    <Link href={hrefForDate(nextDate)}>
                        <ChevronRight className="size-4" />
                    </Link>
                ) : (
                    <span>
                        <ChevronRight className="size-4" />
                    </span>
                )}
            </Button>
        </div>
    );
}
