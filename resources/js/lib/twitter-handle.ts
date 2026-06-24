export function normalizeTwitterHandle(value: string): string {
    return value.trim().replace(/^@+/, '');
}

export function formatTwitterHandle(value: string): string {
    const handle = normalizeTwitterHandle(value);

    return handle === '' ? '' : `@${handle}`;
}
