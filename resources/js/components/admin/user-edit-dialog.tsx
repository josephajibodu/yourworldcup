import { Form } from '@inertiajs/react';
import UserController from '@/actions/App/Http/Controllers/Admin/UserController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    DialogClose,
    DialogFooter,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { AdminUserDetail, AdminUserSummary } from '@/types/admin';

interface UserEditDialogContentProps {
    user: AdminUserSummary | AdminUserDetail;
    onCancel: () => void;
}

export function UserEditDialogContent({
    user,
    onCancel,
}: UserEditDialogContentProps) {
    return (
        <Form
            {...UserController.update.form(user.id)}
            options={{
                preserveScroll: true,
            }}
            className="space-y-4"
            onSuccess={onCancel}
        >
            {({ processing, errors, resetAndClearErrors }) => (
                <>
                    <div className="grid gap-2">
                        <Label htmlFor={`edit-user-name-${user.id}`}>
                            X handle
                        </Label>
                        <Input
                            id={`edit-user-name-${user.id}`}
                            name="name"
                            defaultValue={user.name}
                            required
                            autoComplete="username"
                            placeholder="@yourhandle"
                            spellCheck={false}
                        />
                        <InputError message={errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor={`edit-user-email-${user.id}`}>
                            Email address
                        </Label>
                        <Input
                            id={`edit-user-email-${user.id}`}
                            type="email"
                            name="email"
                            defaultValue={user.email}
                            required
                            autoComplete="email"
                        />
                        <InputError message={errors.email} />
                    </div>

                    <DialogFooter className="gap-2 sm:justify-end">
                        <DialogClose asChild>
                            <Button
                                type="button"
                                variant="secondary"
                                onClick={() => resetAndClearErrors()}
                            >
                                Cancel
                            </Button>
                        </DialogClose>
                        <Button
                            type="submit"
                            disabled={processing}
                            data-test="save-user-button"
                        >
                            Save changes
                        </Button>
                    </DialogFooter>
                </>
            )}
        </Form>
    );
}
