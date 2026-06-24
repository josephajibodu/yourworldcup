import { Form } from '@inertiajs/react';
import { SeoHead } from '@/components/seo-head';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { privatePageRobots } from '@/lib/seo';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

export default function VerifyEmail({ status }: { status?: string }) {
    return (
        <>
            <SeoHead
                title="verify email"
                description="Verify your email address to finish setting up your YourWorldCup account."
                path="/email/verify"
                robots={privatePageRobots}
            />

            {status === 'verification-link-sent' && (
                <div className="mb-4 text-center text-sm font-medium text-wc-green">
                    a new verification link has been sent to the email address
                    you used when signing up.
                </div>
            )}

            <Form {...send.form()} className="space-y-6 text-center">
                {({ processing }) => (
                    <>
                        <Button
                            disabled={processing}
                            variant="outline"
                            size="lg"
                            className="w-full rounded-full border-wc-ink/15"
                        >
                            {processing && <Spinner />}
                            resend verification email
                        </Button>

                        <TextLink
                            href={logout()}
                            className="mx-auto block text-sm"
                        >
                            log out
                        </TextLink>
                    </>
                )}
            </Form>
        </>
    );
}

VerifyEmail.layout = {
    title: 'verify your email',
    description:
        'check your inbox and click the link we sent to confirm your address',
};
