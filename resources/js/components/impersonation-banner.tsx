import { router, usePage } from '@inertiajs/react';
import { VenetianMask } from 'lucide-react';
import { destroy as stopImpersonation } from '@/actions/App/Http/Controllers/ImpersonationController';
import { Button } from '@/components/ui/button';
import { formatTwitterHandle } from '@/lib/twitter-handle';

type ImpersonatingProps = {
    impersonating: {
        userName: string;
    } | null;
};

export function ImpersonationBanner() {
    const { impersonating } = usePage<ImpersonatingProps>().props;

    if (impersonating === null) {
        return null;
    }

    return (
        <div
            className="sticky top-0 z-[100] border-b border-wc-gold/30 bg-wc-gold/15 px-4 py-2"
            role="status"
            aria-live="polite"
        >
            <div className="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3">
                <div className="flex min-w-0 items-center gap-2 text-sm text-wc-ink">
                    <VenetianMask
                        className="size-4 shrink-0 text-wc-gold"
                        aria-hidden
                    />
                    <span>
                        Impersonation mode — viewing as{' '}
                        <span className="font-semibold">
                            {formatTwitterHandle(impersonating.userName)}
                        </span>
                    </span>
                </div>
                <Button
                    type="button"
                    variant="gold"
                    size="sm"
                    data-test="leave-impersonation"
                    onClick={() =>
                        router.delete(stopImpersonation.url(), {
                            preserveScroll: true,
                        })
                    }
                >
                    Leave impersonation
                </Button>
            </div>
        </div>
    );
}
