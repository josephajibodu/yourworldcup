import { Link } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="flex min-h-screen flex-col bg-wc-surface font-sans text-wc-ink">
            <div className="flex flex-1 flex-col items-center justify-center px-6 py-10 md:py-14">
                <div className="w-full max-w-md rounded-2xl border border-wc-ink/10 bg-white/92 p-6 shadow-sm md:p-8">
                    <div className="mb-8">
                        <Link
                            href={home()}
                            className="mb-6 inline-flex items-center gap-2.5"
                        >
                            <AppLogoIcon className="size-10 shrink-0" />
                            <span className="font-display text-sm tracking-[0.22em] text-wc-ink">
                                YOURWORLD
                                <span className="text-wc-gold">CUP</span>
                            </span>
                        </Link>
                        <h1 className="text-3xl font-bold tracking-tight text-balance">
                            {title}
                        </h1>
                        {description ? (
                            <p className="mt-2 text-sm leading-relaxed text-muted-foreground">
                                {description}
                            </p>
                        ) : null}
                    </div>
                    {children}
                </div>
            </div>
        </div>
    );
}
