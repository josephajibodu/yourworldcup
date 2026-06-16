import { Link, usePage } from '@inertiajs/react';
import { Trophy } from 'lucide-react';
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
import { bracket, home, leaderboard, login, predict, register } from '@/routes';

const navItems = [
    { title: 'Bracket', href: bracket() },
    { title: 'Predict', href: predict() },
    { title: 'Leaderboard', href: leaderboard() },
];

function NavLinks({ className }: { className?: string }) {
    const { isCurrentOrParentUrl } = useCurrentUrl();

    return (
        <div className={className}>
            {navItems.map((item) => (
                <Link
                    key={item.title}
                    href={item.href}
                    className={cn(
                        'rounded-full px-3.5 py-2 text-sm font-semibold transition-colors',
                        isCurrentOrParentUrl(item.href)
                            ? 'bg-wc-ink text-wc-surface'
                            : 'text-wc-ink/70 hover:bg-wc-surface-2 hover:text-wc-ink',
                    )}
                >
                    {item.title}
                </Link>
            ))}
        </div>
    );
}

export function SiteHeader() {
    const { auth } = usePage().props;
    const getInitials = useInitials();

    return (
        <header className="sticky top-0 z-30 bg-wc-ink px-4 py-4 text-wc-ink">
            <nav className="mx-auto flex max-w-6xl items-center justify-between gap-4 rounded-full border border-wc-surface-2/70 bg-wc-surface px-4 py-2 shadow-[0_18px_45px_rgba(0,0,0,0.28)] sm:px-5">
                <div className="flex min-w-0 items-center gap-6">
                    <Link
                        href={home()}
                        className="flex min-w-0 items-center gap-2.5"
                    >
                        <span className="flex size-8 shrink-0 items-center justify-center rounded-full bg-wc-ink text-wc-gold">
                            <Trophy className="size-4" />
                        </span>
                        <span className="truncate font-display text-xl tracking-[0.22em]">
                            YOURWORLD<span className="text-wc-gold">CUP</span>
                        </span>
                    </Link>
                    <NavLinks className="hidden items-center gap-1 lg:flex" />
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

            <NavLinks className="mx-auto mt-2 flex max-w-6xl items-center gap-1 overflow-x-auto rounded-full border border-wc-surface-2/70 bg-wc-surface p-1 lg:hidden" />
        </header>
    );
}
