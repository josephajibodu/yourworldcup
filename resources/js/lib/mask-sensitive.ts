const MASK = '•';

function maskPartial(value: string, visibleChars: number): string {
    if (value.length <= visibleChars) {
        return value;
    }

    return `${value.slice(0, visibleChars)}${MASK.repeat(3)}`;
}

export function maskEmail(email: string): string {
    const atIndex = email.indexOf('@');

    if (atIndex === -1) {
        return maskPartial(email, 2);
    }

    const local = email.slice(0, atIndex);
    const domain = email.slice(atIndex + 1);
    const lastDotIndex = domain.lastIndexOf('.');

    if (lastDotIndex === -1) {
        return `${maskPartial(local, 2)}@${maskPartial(domain, 1)}`;
    }

    const domainName = domain.slice(0, lastDotIndex);
    const tld = domain.slice(lastDotIndex + 1);

    return `${maskPartial(local, 2)}@${maskPartial(domainName, 1)}.${tld}`;
}

export function maskName(name: string): string {
    return name
        .split(/\s+/)
        .filter(Boolean)
        .map((part) => maskPartial(part, 1))
        .join(' ');
}

export function maskDigits(value: string, visibleTrailing = 4): string {
    const digits = value.replace(/\D/g, '');

    if (digits.length === 0) {
        return MASK.repeat(4);
    }

    if (digits.length <= visibleTrailing) {
        return `${MASK.repeat(4)}${digits}`;
    }

    return `${MASK.repeat(4)}${digits.slice(-visibleTrailing)}`;
}
