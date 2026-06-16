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
                        'rounded-md px-3 py-1.5 text-sm font-medium transition-colors',
                        isCurrentOrParentUrl(item.href)
                            ? 'bg-wc-ink-3 text-wc-surface'
                            : 'text-wc-surface/65 hover:bg-wc-ink-2 hover:text-wc-surface',
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
        <header className="sticky top-0 z-30 bg-wc-ink text-wc-surface">
            <nav className="mx-auto flex max-w-6xl items-center justify-between gap-4 px-5 py-3">
                <div className="flex items-center gap-6">
                    <Link href={home()} className="flex items-center gap-2.5">
                        <span className="flex size-7 items-center justify-center rounded-md bg-wc-gold text-wc-ink">
                            <Trophy className="size-4" />
                        </span>
                        <span className="font-display text-xl tracking-wide">
                            YOURWORLD<span className="text-wc-gold">CUP</span>
                        </span>
                    </Link>
                    <NavLinks className="hidden items-center gap-1 md:flex" />
                </div>

                <div className="flex items-center gap-2">
                    {auth.user ? (
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button
                                    variant="ghost"
                                    className="size-9 rounded-full p-1 hover:bg-wc-ink-2"
                                >
                                    <Avatar className="size-8 overflow-hidden rounded-full">
                                        <AvatarImage
                                            src={auth.user.avatar}
                                            alt={auth.user.name}
                                        />
                                        <AvatarFallback className="rounded-full bg-wc-ink-3 text-wc-surface">
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
                                variant="ghost"
                                size="sm"
                                className="text-wc-surface hover:bg-wc-ink-2 hover:text-wc-surface"
                            >
                                <Link href={login()}>Log in</Link>
                            </Button>
                            <Button asChild variant="gold" size="sm">
                                <Link href={register()}>Play free</Link>
                            </Button>
                        </>
                    )}
                </div>
            </nav>

            <NavLinks className="flex items-center gap-1 overflow-x-auto border-t border-wc-ink-3 px-3 py-2 md:hidden" />
        </header>
    );
}
