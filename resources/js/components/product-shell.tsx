import { SiteFooter } from '@/components/site-footer';
import { SiteHeader } from '@/components/site-header';
import { cn } from '@/lib/utils';

interface ProductShellProps {
    children: React.ReactNode;
    className?: string;
    mainClassName?: string;
}

export function ProductShell({
    children,
    className,
    mainClassName,
}: ProductShellProps) {
    return (
        <div
            className={cn(
                'flex min-h-screen flex-col bg-background font-sans text-foreground',
                className,
            )}
        >
            <SiteHeader />
            <main className={cn('flex-1', mainClassName)}>{children}</main>
            <SiteFooter />
        </div>
    );
}
