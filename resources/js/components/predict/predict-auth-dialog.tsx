import { Form } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import PasskeyVerify from '@/components/passkey-verify';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';
import { store as loginStore } from '@/routes/login';
import { request } from '@/routes/password';
import { store as registerStore } from '@/routes/register';

type AuthMode = 'login' | 'register';

interface PredictAuthDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    canResetPassword: boolean;
    passwordRules: string;
}

export function PredictAuthDialog({
    open,
    onOpenChange,
    canResetPassword,
    passwordRules,
}: PredictAuthDialogProps) {
    const [mode, setMode] = useState<AuthMode>('login');

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-md">
                <DialogHeader className="text-center sm:text-center">
                    <DialogTitle className="font-display text-2xl tracking-wide">
                        save your picks
                    </DialogTitle>
                    <DialogDescription>
                        Sign in or create a free account — we&apos;ll bring you
                        right back here with your selections.
                    </DialogDescription>
                </DialogHeader>

                <div className="flex rounded-full border bg-secondary/50 p-1">
                    {(['login', 'register'] as const).map((tab) => (
                        <button
                            key={tab}
                            type="button"
                            onClick={() => setMode(tab)}
                            className={cn(
                                'flex-1 rounded-full px-3 py-2 text-sm font-semibold transition-colors',
                                mode === tab
                                    ? 'bg-wc-ink text-wc-surface shadow-sm'
                                    : 'text-muted-foreground hover:text-wc-ink',
                            )}
                        >
                            {tab === 'login' ? 'Log in' : 'Sign up'}
                        </button>
                    ))}
                </div>

                {mode === 'login' ? (
                    <>
                        <PasskeyVerify
                            label="Sign in with a passkey"
                            separator="or continue with email"
                            onSuccess={() => onOpenChange(false)}
                        />
                        <Form
                            {...loginStore.form()}
                            resetOnSuccess={['password']}
                            className="flex flex-col gap-4"
                            onSuccess={() => onOpenChange(false)}
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="predict-auth-email">
                                            email
                                        </Label>
                                        <Input
                                            id="predict-auth-email"
                                            type="email"
                                            name="email"
                                            required
                                            autoFocus
                                            autoComplete="email"
                                            placeholder="you@example.com"
                                        />
                                        <InputError message={errors.email} />
                                    </div>

                                    <div className="grid gap-2">
                                        <div className="flex items-center">
                                            <Label htmlFor="predict-auth-password">
                                                password
                                            </Label>
                                            {canResetPassword && (
                                                <TextLink
                                                    href={request()}
                                                    className="ml-auto text-sm"
                                                >
                                                    forgot password?
                                                </TextLink>
                                            )}
                                        </div>
                                        <PasswordInput
                                            id="predict-auth-password"
                                            name="password"
                                            required
                                            autoComplete="current-password"
                                            placeholder="your password"
                                        />
                                        <InputError message={errors.password} />
                                    </div>

                                    <div className="flex items-center space-x-3">
                                        <Checkbox
                                            id="predict-auth-remember"
                                            name="remember"
                                        />
                                        <Label htmlFor="predict-auth-remember">
                                            remember me
                                        </Label>
                                    </div>

                                    <Button
                                        type="submit"
                                        variant="ink"
                                        size="lg"
                                        className="w-full rounded-full"
                                        disabled={processing}
                                        data-test="predict-auth-login-button"
                                    >
                                        {processing && <Spinner />}
                                        log in
                                    </Button>
                                </>
                            )}
                        </Form>
                    </>
                ) : (
                    <Form
                        {...registerStore.form()}
                        resetOnSuccess={[
                            'password',
                            'password_confirmation',
                        ]}
                        disableWhileProcessing
                        className="flex flex-col gap-4"
                        onSuccess={() => onOpenChange(false)}
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="predict-auth-name">
                                        X handle
                                    </Label>
                                    <Input
                                        id="predict-auth-name"
                                        type="text"
                                        name="name"
                                        required
                                        autoFocus
                                        autoComplete="username"
                                        placeholder="@yourhandle"
                                        spellCheck={false}
                                    />
                                    <InputError message={errors.name} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="predict-auth-register-email">
                                        email
                                    </Label>
                                    <Input
                                        id="predict-auth-register-email"
                                        type="email"
                                        name="email"
                                        required
                                        autoComplete="email"
                                        placeholder="you@example.com"
                                    />
                                    <InputError message={errors.email} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="predict-auth-register-password">
                                        password
                                    </Label>
                                    <PasswordInput
                                        id="predict-auth-register-password"
                                        name="password"
                                        required
                                        autoComplete="new-password"
                                        placeholder="choose a password"
                                        passwordrules={passwordRules}
                                    />
                                    <InputError message={errors.password} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="predict-auth-password-confirmation">
                                        confirm password
                                    </Label>
                                    <PasswordInput
                                        id="predict-auth-password-confirmation"
                                        name="password_confirmation"
                                        required
                                        autoComplete="new-password"
                                        placeholder="repeat your password"
                                        passwordrules={passwordRules}
                                    />
                                    <InputError
                                        message={errors.password_confirmation}
                                    />
                                </div>

                                <Button
                                    type="submit"
                                    variant="ink"
                                    size="lg"
                                    className="w-full rounded-full"
                                    disabled={processing}
                                    data-test="predict-auth-register-button"
                                >
                                    {processing && <Spinner />}
                                    create account
                                </Button>
                            </>
                        )}
                    </Form>
                )}
            </DialogContent>
        </Dialog>
    );
}
