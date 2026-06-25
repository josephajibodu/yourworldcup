import type { InertiaLinkProps } from '@inertiajs/react';
import { Link, usePage } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { UserMenuContent } from '@/components/user-menu-content';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { useInitials } from '@/hooks/use-initials';
import { cn } from '@/lib/utils';
import {
    bestThirds,
    bracket,
    home,
    leaderboard,
    login,
    predict,
    referrals,
    register,
} from '@/routes';

const navItems = [
    { title: 'Predict', href: predict() },
    { title: 'Leaderboard', href: leaderboard() },
    { title: 'Bracket', href: bracket() },
    { title: 'Referrals', href: referrals() },
];

const smNavItem = {
    title: '3rd-place ranking',
    href: bestThirds(),
};

function NavLinks({
    className,
    includeThirdPlace = false,
}: {
    className?: string;
    includeThirdPlace?: boolean;
}) {
    const { isCurrentOrParentUrl } = useCurrentUrl();

    const linkClass = (href: NonNullable<InertiaLinkProps['href']>) =>
        cn(
            'rounded-full px-3.5 py-2 text-xs font-semibold whitespace-nowrap transition-colors sm:text-sm',
            isCurrentOrParentUrl(href)
                ? 'bg-wc-ink text-wc-surface'
                : 'text-wc-ink/70 hover:bg-wc-surface-2 hover:text-wc-ink',
        );

    return (
        <div className={className}>
            {navItems.map((item) => (
                <Link key={item.title} href={item.href} className={linkClass(item.href)}>
                    {item.title}
                </Link>
            ))}
            {includeThirdPlace && (
                <Link href={smNavItem.href} className={linkClass(smNavItem.href)}>
                    {smNavItem.title}
                </Link>
            )}
        </div>
    );
}

interface SiteHeaderProps {
    variant?: 'surface' | 'dark';
}

export function SiteHeader({ variant = 'surface' }: SiteHeaderProps) {
    const { auth } = usePage().props;
    const { isCurrentOrParentUrl } = useCurrentUrl();
    const getInitials = useInitials();
    const isDark = variant === 'dark';

    return (
        <header
            className={cn(
                'sticky top-0 z-30 px-4 py-4 text-wc-ink',
                isDark ? 'bg-wc-ink' : 'bg-wc-surface',
            )}
        >
            <nav className="mx-auto flex max-w-6xl items-center justify-between gap-4 rounded-full border border-wc-ink/15 bg-wc-surface px-4 py-2 shadow-[0_14px_34px_rgba(10,10,11,0.1)] sm:px-5">
                <div className="flex min-w-0 items-center gap-6">
                    <Link
                        href={home()}
                        className="flex min-w-0 items-center gap-2.5"
                    >
                        <AppLogoIcon className="size-8 shrink-0" />
                        <span className="truncate font-display text-sm font-bold tracking-[0.22em] sm:text-xl sm:font-normal">
                            YOURWORLD<span className="text-wc-gold">CUP</span>
                        </span>
                    </Link>
                    <NavLinks className="hidden items-center gap-1 lg:flex" />
                    <Link
                        href={smNavItem.href}
                        className={cn(
                            'hidden rounded-full px-3.5 py-2 text-xs font-semibold transition-colors sm:inline-flex sm:text-sm lg:hidden',
                            isCurrentOrParentUrl(smNavItem.href)
                                ? 'bg-wc-ink text-wc-surface'
                                : 'text-wc-ink/70 hover:bg-wc-surface-2 hover:text-wc-ink',
                        )}
                    >
                        {smNavItem.title}
                    </Link>
                </div>

                <div className="flex shrink-0 items-center gap-2">
                    {auth.user ? (
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button
                                    variant="ghost"
                                    className="size-9 rounded-full p-1 hover:bg-wc-surface-2"
                                >
                                    <Avatar className="size-8 overflow-hidden rounded-full">
                                        <AvatarImage
                                            src={auth.user.avatar}
                                            alt={auth.user.name}
                                        />
                                        <AvatarFallback className="rounded-full bg-wc-ink text-wc-surface">
                                            {getInitials(auth.user.name)}
                                        </AvatarFallback>
                                    </Avatar>
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent className="w-56" align="end">
                                <UserMenuContent user={auth.user} />
                            </DropdownMenuContent>
                        </DropdownMenu>
                    ) : (
                        <>
                            <Button
                                asChild
                                size="sm"
                                variant="ghost"
                                className="hidden rounded-full px-4 text-wc-ink/65 underline-offset-4 hover:bg-transparent hover:text-wc-ink hover:underline sm:inline-flex"
                            >
                                <Link href={login()}>Member Login</Link>
                            </Button>
                            <Button
                                asChild
                                variant="ink"
                                size="sm"
                                className="rounded-full px-4"
                            >
                                <Link href={register()}>Get Started</Link>
                            </Button>
                        </>
                    )}
                </div>
            </nav>

            <NavLinks
                includeThirdPlace
                className="mx-auto mt-2 flex max-w-6xl items-center gap-1 overflow-x-auto rounded-full border border-wc-ink/15 bg-wc-surface p-1 shadow-[0_10px_24px_rgba(10,10,11,0.08)] lg:hidden"
            />
        </header>
    );
}
