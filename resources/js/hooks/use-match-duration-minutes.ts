import { usePage } from '@inertiajs/react';

export function useMatchDurationMinutes(): number {
    return usePage().props.predictions.matchDurationMinutes;
}
