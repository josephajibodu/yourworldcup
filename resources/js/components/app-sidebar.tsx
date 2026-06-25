import { Link, usePage } from '@inertiajs/react';
import {
    BookOpen,
    CalendarDays,
    FolderGit2,
    LayoutGrid,
    Medal,
    Network,
    Target,
    Trophy,
    Users,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { bracket, bestThirds, dashboard, leaderboard, predict } from '@/routes';
import { index as adminBracketSlotsIndex } from '@/routes/admin/bracket-slots';
import { index as adminFixturesIndex } from '@/routes/admin/fixtures';
import { leaderboard as adminLeaderboard } from '@/routes/admin';
import { index as adminUsersIndex } from '@/routes/admin/users';
import type { Auth, NavItem } from '@/types';

function mainNavItems(isAdmin: boolean): NavItem[] {
    if (isAdmin) {
        return [
            {
                title: 'Dashboard',
                href: dashboard(),
                icon: LayoutGrid,
            },
            {
                title: 'Users',
                href: adminUsersIndex(),
                icon: Users,
            },
            {
                title: 'Leaderboard',
                href: adminLeaderboard(),
                icon: Trophy,
            },
            {
                title: 'Fixtures',
                href: adminFixturesIndex(),
                icon: CalendarDays,
            },
            {
                title: 'Bracket slots',
                href: adminBracketSlotsIndex(),
                icon: Network,
            },
            {
                title: 'Best 3rd',
                href: bestThirds(),
                icon: Medal,
            },
        ];
    }

    return [
        {
            title: 'Predict',
            href: predict(),
            icon: Target,
        },
        {
            title: 'Bracket',
            href: bracket(),
            icon: Network,
        },
        {
            title: 'Best 3rd',
            href: bestThirds(),
            icon: Medal,
        },
        {
            title: 'Leaderboard',
            href: leaderboard(),
            icon: Trophy,
        },
    ];
}

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/react-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#react',
        icon: BookOpen,
    },
];

export function AppSidebar() {
    const { auth } = usePage<{ auth: Auth }>().props;
    const navItems = mainNavItems(auth.isAdmin);
    const homeHref = auth.isAdmin ? dashboard() : predict();

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={homeHref} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={navItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
