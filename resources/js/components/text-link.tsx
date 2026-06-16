import { Link } from '@inertiajs/react';
import type { ComponentProps } from 'react';
import { cn } from '@/lib/utils';

type Props = ComponentProps<typeof Link>;

export default function TextLink({
    className = '',
    children,
    ...props
}: Props) {
    return (
        <Link
            className={cn(
                'font-semibold text-wc-ink underline decoration-wc-ink/20 underline-offset-4 transition-colors hover:text-wc-gold-deep hover:decoration-wc-gold/40',
                className,
            )}
            {...props}
        >
            {children}
        </Link>
    );
}
