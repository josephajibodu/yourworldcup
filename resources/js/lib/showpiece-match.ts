export const SHOWPIECE_NODE_W = 272;

/** Layout sections for showpiece cards (must match `ShowpieceMatchCard` spacing). */
export const SHOWPIECE_HEADER_H = 84;
export const SHOWPIECE_ROW_H = 36;
export const SHOWPIECE_FOOTER_H = 34;

export const SHOWPIECE_NODE_H =
    SHOWPIECE_HEADER_H + SHOWPIECE_ROW_H * 2 + SHOWPIECE_FOOTER_H;

/** Vertical offset from the node top to the bracket connector (between team rows). */
export const SHOWPIECE_CONNECTOR_Y = SHOWPIECE_HEADER_H + SHOWPIECE_ROW_H;

export const SHOWPIECE_HANDLE_TOP = `${
    (SHOWPIECE_CONNECTOR_Y / SHOWPIECE_NODE_H) * 100
}%`;

export function isShowpieceStage(stage: string): boolean {
    return stage === 'final' || stage === 'third_place';
}

export function formatShowpieceKickoff(
    kickoffAt: string,
    timezone?: string | null,
): string {
    const kickoff = new Date(kickoffAt);
    const options: Intl.DateTimeFormatOptions = {
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    };

    if (timezone) {
        options.timeZone = timezone;
    }

    const formatted = kickoff.toLocaleString('en-US', options);
    const [datePart, timePart] = formatted.split(', ');

    return timePart ? `${datePart} - ${timePart}` : formatted;
}
