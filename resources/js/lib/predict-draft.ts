import type { MarketValue } from '@/components/predict/types';

export interface PredictDraft {
    picks: Record<number, MarketValue>;
    banker: number | null;
    pendingSubmit?: boolean;
}

const prefix = 'predict-draft:';

function draftKey(date: string): string {
    return `${prefix}${date}`;
}

export function savePredictDraft(date: string, draft: PredictDraft): void {
    if (typeof window === 'undefined') {
        return;
    }

    sessionStorage.setItem(draftKey(date), JSON.stringify(draft));
}

export function loadPredictDraft(date: string): PredictDraft | null {
    if (typeof window === 'undefined') {
        return null;
    }

    const raw = sessionStorage.getItem(draftKey(date));

    if (raw === null) {
        return null;
    }

    try {
        return JSON.parse(raw) as PredictDraft;
    } catch {
        return null;
    }
}

export function clearPredictDraft(date: string): void {
    if (typeof window === 'undefined') {
        return;
    }

    sessionStorage.removeItem(draftKey(date));
}
