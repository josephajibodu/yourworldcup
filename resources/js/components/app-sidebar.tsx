import { Link, usePage } from '@inertiajs/react';
import {
    BookOpen,
    FolderGit2,
    LayoutGrid,
    Network,
    Target,
    Trophy,
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
import { bracket, dashboard, leaderboard, predict } from '@/routes';
import type { Auth, NavItem } from '@/types';

function mainNavItems(isAdmin: boolean): NavItem[] {
    const items: NavItem[] = [
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
            title: 'Leaderboard',
            href: leaderboard(),
            icon: Trophy,
        },
    ];

    if (isAdmin) {
        items.unshift({
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        });
    }

    return items;
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
