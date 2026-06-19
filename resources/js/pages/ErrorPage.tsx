import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowRight, RefreshCw } from 'lucide-react';
import AppLogoIcon from '@/components/app-logo-icon';
import { Button } from '@/components/ui/button';
import { home, predict } from '@/routes';

type ErrorDefinition = {
    title: string;
    description: string;
    offer?: string;
};

const ERROR_PAGES: Record<number, ErrorDefinition> = {
    403: {
        title: 'Access denied',
        description: 'You do not have permission to view this page.',
        offer: 'Sign in free, predict match results, and climb the daily board to win airtime.',
    },
    404: {
        title: 'Page not found',
        description: 'This link does not lead anywhere.',
        offer: 'Today’s matches are still live — call the winners, earn points, and win airtime on the daily board.',
    },
    419: {
        title: 'Page expired',
        description: 'Your session timed out for security.',
        offer: 'Refresh and pick up where you left off — your daily points are still up for grabs.',
    },
    500: {
        title: 'Something went wrong',
        description: 'We hit an unexpected error on our side.',
        offer: 'Give it a moment, then jump back in — today’s predictions are still worth points.',
    },
    503: {
        title: 'Be right back',
        description: 'YourWorldCup is briefly unavailable while we make updates.',
    },
};

interface ErrorPageProps {
    status: number;
}

export default function ErrorPage({ status }: ErrorPageProps) {
    const { auth } = usePage().props;
    const error = ERROR_PAGES[status] ?? ERROR_PAGES[500];
    const picksHref = predict();
    const picksLabel = auth.user ? 'Make today’s picks' : 'Get started free';

    return (
        <>
            <Head title={error.title} />
            <div className="flex min-h-screen flex-col bg-wc-surface font-sans text-wc-ink">
                <main className="mx-auto flex w-full max-w-lg flex-1 flex-col items-center justify-center px-6 py-16 text-center">
                    <Link href={home()} className="mb-10">
                        <AppLogoIcon className="size-16" />
                    </Link>

                    <p className="font-mono text-sm font-bold tracking-[0.28em] text-wc-gold uppercase">
                        Error {status}
                    </p>

                    <h1 className="mt-4 text-4xl leading-tight tracking-tight sm:text-5xl">
                        {error.title}
                    </h1>

                    <p className="mt-4 text-base leading-7 text-muted-foreground">
                        {error.description}
                    </p>

                    {error.offer ? (
                        <p className="mt-3 text-base leading-7 text-wc-ink">
                            {error.offer}
                        </p>
                    ) : null}

                    <div className="mt-8 flex w-full flex-col items-stretch gap-3 sm:w-auto sm:flex-row sm:items-center sm:justify-center">
                        {status === 419 ? (
                            <Button
                                type="button"
                                variant="ink"
                                size="lg"
                                className="rounded-full"
                                onClick={() => router.reload()}
                            >
                                Refresh page
                                <RefreshCw className="size-4" />
                            </Button>
                        ) : status === 500 || status === 503 ? (
                            <Button
                                type="button"
                                variant="ink"
                                size="lg"
                                className="rounded-full"
                                onClick={() => window.location.reload()}
                            >
                                Try again
                                <RefreshCw className="size-4" />
                            </Button>
                        ) : (
                            <Button
                                asChild
                                variant="ink"
                                size="lg"
                                className="rounded-full"
                            >
                                <Link href={picksHref}>
                                    {picksLabel}
                                    <ArrowRight className="size-4" />
                                </Link>
                            </Button>
                        )}

                        {status === 419 ||
                        status === 500 ||
                        status === 503 ? (
                            <Button
                                asChild
                                variant="outline"
                                size="lg"
                                className="rounded-full border-border bg-card hover:bg-wc-surface-2"
                            >
                                <Link href={picksHref}>
                                    {picksLabel}
                                    <ArrowRight className="size-4" />
                                </Link>
                            </Button>
                        ) : (
                            <Button
                                asChild
                                variant="ghost"
                                size="lg"
                                className="rounded-full text-wc-ink/70 hover:text-wc-ink"
                            >
                                <Link href={home()}>Back home</Link>
                            </Button>
                        )}
                    </div>
                </main>
            </div>
        </>
    );
}
