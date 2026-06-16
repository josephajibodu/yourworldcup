import { useEffect, useState } from 'react';

export interface PlayerCountState {
    count: number | null;
    isLoading: boolean;
    error: boolean;
}

/**
 * Fetches the live player count that gates the grand-prize unlock.
 *
 * Deliberately decoupled from the visual components that consume it, so the data
 * source can be swapped (endpoint, Inertia prop, realtime channel) without
 * touching the UI. Fetches once on mount; pass `pollMs` to refresh on an
 * interval. On a failed refresh the last known count is kept rather than reset.
 */
export function usePlayerCount(pollMs?: number): PlayerCountState {
    const [state, setState] = useState<PlayerCountState>({
        count: null,
        isLoading: true,
        error: false,
    });

    useEffect(() => {
        let active = true;
        const controller = new AbortController();

        const load = async () => {
            try {
                const response = await fetch('/players/count', {
                    headers: { Accept: 'application/json' },
                    signal: controller.signal,
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const data = (await response.json()) as { count: number };

                if (active) {
                    setState({
                        count: data.count,
                        isLoading: false,
                        error: false,
                    });
                }
            } catch {
                if (active && !controller.signal.aborted) {
                    setState((prev) => ({
                        count: prev.count,
                        isLoading: false,
                        error: true,
                    }));
                }
            }
        };

        load();

        const interval =
            pollMs && pollMs > 0 ? window.setInterval(load, pollMs) : undefined;

        return () => {
            active = false;
            controller.abort();

            if (interval) {
                window.clearInterval(interval);
            }
        };
    }, [pollMs]);

    return state;
}
