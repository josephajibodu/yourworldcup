import { Form, Link, router } from '@inertiajs/react';
import { Eye, Pencil, Search, Users } from 'lucide-react';
import { useState } from 'react';
import { show } from '@/actions/App/Http/Controllers/Admin/UserController';
import { UserEditDialogContent } from '@/components/admin/user-edit-dialog';
import { UserViewDialogContent } from '@/components/admin/user-view-dialog';
import Heading from '@/components/heading';
import { SeoHead } from '@/components/seo-head';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { formatTwitterHandle } from '@/lib/twitter-handle';
import { cn } from '@/lib/utils';
import { privatePageRobots } from '@/lib/seo';
import { index as usersIndex } from '@/routes/admin/users';
import { dashboard } from '@/routes';
import type {
    AdminUserDetail,
    AdminUserSummary,
    Paginated,
} from '@/types/admin';

type PageProps = {
    users: Paginated<AdminUserSummary>;
    filters: {
        search: string;
    };
    selectedUser: AdminUserDetail | null;
};

type ActiveModal = 'view' | 'edit' | null;

function formatDate(value: string): string {
    return new Date(value).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

export default function AdminUsersIndex({
    users,
    filters,
    selectedUser,
}: PageProps) {
    const [activeModal, setActiveModal] = useState<ActiveModal>(null);
    const [activeUserId, setActiveUserId] = useState<number | null>(null);
    const [editUser, setEditUser] = useState<AdminUserSummary | null>(null);
    const [isFetchingUser, setIsFetchingUser] = useState(false);

    function fetchUserDetails(userId: number): void {
        setIsFetchingUser(true);

        router.get(
            show.url(userId),
            {},
            {
                preserveState: true,
                preserveScroll: true,
                only: ['selectedUser'],
                onFinish: () => setIsFetchingUser(false),
            },
        );
    }

    function openView(userId: number): void {
        setActiveUserId(userId);
        setActiveModal('view');
        fetchUserDetails(userId);
    }

    function openEdit(user: AdminUserSummary): void {
        setActiveUserId(user.id);
        setEditUser(user);
        setActiveModal('edit');
    }

    function closeModals(): void {
        setActiveModal(null);
        setActiveUserId(null);
        setEditUser(null);
        setIsFetchingUser(false);
    }

    const viewUser =
        selectedUser !== null && selectedUser.id === activeUserId
            ? selectedUser
            : null;

    const editingUser = editUser;

    return (
        <>
            <SeoHead
                title="Users"
                description="Manage YourWorldCup users."
                path="/admin/users"
                robots={privatePageRobots}
            />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <Heading
                        title="Users"
                        description="View and manage registered players."
                    />

                    <Form
                        {...usersIndex.form({
                            query: filters.search
                                ? { search: filters.search }
                                : undefined,
                        })}
                        className="flex w-full max-w-sm items-center gap-2"
                        preserveScroll
                    >
                        <div className="relative flex-1">
                            <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                name="search"
                                defaultValue={filters.search}
                                placeholder="Search by handle or email"
                                className="pl-9"
                            />
                        </div>
                        <Button type="submit" variant="secondary">
                            Search
                        </Button>
                    </Form>
                </div>

                {users.data.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-wc-ink/15 bg-card px-6 py-12 text-center text-sm text-muted-foreground">
                        <Users className="mx-auto mb-3 size-8 opacity-40" />
                        {filters.search
                            ? 'No users match your search.'
                            : 'No users yet.'}
                    </div>
                ) : (
                    <div className="overflow-hidden rounded-xl border border-wc-ink/10 bg-card">
                        <div className="hidden border-b border-wc-ink/8 px-4 py-3 text-xs font-medium tracking-wide text-muted-foreground uppercase md:grid md:grid-cols-[minmax(0,1.2fr)_minmax(0,1.4fr)_9rem_4rem_11rem] md:gap-4">
                            <span>Handle</span>
                            <span>Email</span>
                            <span className="text-right">Joined</span>
                            <span className="text-right">Picks</span>
                            <span className="text-right">Actions</span>
                        </div>

                        <ul>
                            {users.data.map((user) => (
                                <li
                                    key={user.id}
                                    className="flex flex-col gap-3 border-b border-wc-ink/8 px-4 py-4 last:border-b-0 md:grid md:grid-cols-[minmax(0,1.2fr)_minmax(0,1.4fr)_9rem_4rem_11rem] md:items-center md:gap-4"
                                >
                                    <div className="min-w-0">
                                        <p className="truncate text-sm font-medium">
                                            {formatTwitterHandle(user.name)}
                                        </p>
                                        {user.isSiteAdmin && (
                                            <Badge
                                                variant="gold"
                                                className="mt-1"
                                            >
                                                Site admin
                                            </Badge>
                                        )}
                                    </div>

                                    <p className="truncate text-sm text-muted-foreground">
                                        {user.email}
                                    </p>

                                    <p className="text-sm text-muted-foreground md:text-right">
                                        {formatDate(user.createdAt)}
                                    </p>

                                    <p className="font-mono text-sm tabular-nums md:text-right">
                                        {user.predictionsCount}
                                    </p>

                                    <div className="grid grid-cols-2 gap-1 md:justify-self-end">
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            className="justify-start md:w-full"
                                            onClick={() => openView(user.id)}
                                            data-test={`view-user-${user.id}`}
                                        >
                                            <Eye className="size-4" />
                                            View
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            className="justify-start md:w-full"
                                            onClick={() => openEdit(user)}
                                            data-test={`edit-user-${user.id}`}
                                        >
                                            <Pencil className="size-4" />
                                            Edit
                                        </Button>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    </div>
                )}

                {users.last_page > 1 && (
                    <nav
                        aria-label="Users pagination"
                        className="flex flex-wrap items-center justify-center gap-1"
                    >
                        {users.links.map((link, index) => {
                            if (link.url === null) {
                                return (
                                    <span
                                        key={index}
                                        className="px-3 py-2 text-sm text-muted-foreground"
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                    />
                                );
                            }

                            return (
                                <Link
                                    key={index}
                                    href={link.url}
                                    preserveScroll
                                    className={cn(
                                        'rounded-md px-3 py-2 text-sm transition-colors',
                                        link.active
                                            ? 'bg-wc-ink text-wc-surface'
                                            : 'text-muted-foreground hover:bg-secondary hover:text-foreground',
                                    )}
                                    dangerouslySetInnerHTML={{
                                        __html: link.label,
                                    }}
                                />
                            );
                        })}
                    </nav>
                )}
            </div>

            <Dialog
                open={activeModal === 'view'}
                onOpenChange={(open) => {
                    if (!open) {
                        closeModals();
                    }
                }}
            >
                <DialogContent className="sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle>User details</DialogTitle>
                        <DialogDescription>
                            Account information and activity summary.
                        </DialogDescription>
                    </DialogHeader>

                    {isFetchingUser || viewUser === null ? (
                        <div className="flex justify-center py-8">
                            <Spinner className="size-6" />
                        </div>
                    ) : (
                        <>
                            <UserViewDialogContent user={viewUser} />
                            <div className="flex justify-end gap-2">
                                <Button
                                    type="button"
                                    variant="secondary"
                                    onClick={closeModals}
                                >
                                    Close
                                </Button>
                                <Button
                                    type="button"
                                    onClick={() => {
                                        setEditUser(viewUser);
                                        setActiveModal('edit');
                                    }}
                                    data-test="edit-user-from-view"
                                >
                                    Edit user
                                </Button>
                            </div>
                        </>
                    )}
                </DialogContent>
            </Dialog>

            <Dialog
                open={activeModal === 'edit' && editingUser !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        closeModals();
                    }
                }}
            >
                <DialogContent className="sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle>Edit user</DialogTitle>
                        <DialogDescription>
                            Update this player&apos;s X handle and email
                            address.
                        </DialogDescription>
                    </DialogHeader>

                    {editingUser && (
                        <UserEditDialogContent
                            user={editingUser}
                            onCancel={closeModals}
                        />
                    )}
                </DialogContent>
            </Dialog>
        </>
    );
}

AdminUsersIndex.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
        {
            title: 'Users',
            href: usersIndex(),
        },
    ],
};
