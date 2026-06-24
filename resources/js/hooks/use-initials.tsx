import { useCallback } from 'react';

export type GetInitialsFn = (fullName: string) => string;

export function useInitials(): GetInitialsFn {
    return useCallback((handle: string): string => {
        const normalized = handle.trim().replace(/^@+/, '');

        if (normalized === '') {
            return '';
        }

        return normalized.slice(0, 2).toUpperCase();
    }, []);
}
