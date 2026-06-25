import { router } from '@inertiajs/react';
import { format, parseISO } from 'date-fns';
import { CalendarIcon } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { cn, toUrl } from '@/lib/utils';
import { daily } from '@/routes/admin/leaderboard';

interface LeaderboardDayPickerProps {
    selectedDate: string | null;
    rangeStart: string;
    rangeEnd: string;
}

function parseWatDate(value: string): Date {
    return parseISO(value);
}

export function LeaderboardDayPicker({
    selectedDate,
    rangeStart,
    rangeEnd,
}: LeaderboardDayPickerProps) {
    const [open, setOpen] = useState(false);
    const selected = selectedDate ? parseWatDate(selectedDate) : undefined;
    const min = parseWatDate(rangeStart);
    const max = parseWatDate(rangeEnd);

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    variant="outline"
                    data-empty={!selectedDate}
                    className={cn(
                        'w-[240px] justify-start text-left font-normal',
                        !selectedDate && 'text-muted-foreground',
                    )}
                >
                    <CalendarIcon />
                    {selectedDate ? (
                        format(selected!, 'PPP')
                    ) : (
                        <span>Pick a day</span>
                    )}
                </Button>
            </PopoverTrigger>
            <PopoverContent className="w-auto p-0" align="start">
                <Calendar
                    mode="single"
                    selected={selected}
                    defaultMonth={selected ?? min}
                    disabled={{ before: min, after: max }}
                    onSelect={(date) => {
                        if (!date) {
                            return;
                        }

                        const watDate = format(date, 'yyyy-MM-dd');

                        if (watDate < rangeStart || watDate > rangeEnd) {
                            return;
                        }

                        setOpen(false);
                        router.get(
                            toUrl(daily({ query: { date: watDate } })),
                            {},
                            { preserveScroll: true },
                        );
                    }}
                />
            </PopoverContent>
        </Popover>
    );
}
