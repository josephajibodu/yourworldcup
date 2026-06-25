import { Link, router, usePage } from '@inertiajs/react';
import { CalendarDays, ChevronLeft, ChevronRight } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import {
    rememberReturnUrl,
    store as storePredictions,
} from '@/actions/App/Http/Controllers/PredictController';
import { FixtureCard } from '@/components/predict/fixture-card';
import { PredictAuthDialog } from '@/components/predict/predict-auth-dialog';
import { PredictUpdateDialog } from '@/components/predict/predict-update-dialog';
import { ScoreStepperProvider } from '@/components/predict/score-stepper-context';
import type { MarketValue, PredictFixture } from '@/components/predict/types';
import { ProductShell } from '@/components/product-shell';
import { SeoHead } from '@/components/seo-head';
import { Button } from '@/components/ui/button';
import { useNow } from '@/hooks/use-now';
import {
    clearPredictDraft,
    loadPredictDraft,
    savePredictDraft,
} from '@/lib/predict-draft';
import { hasSavedPicksForDay } from '@/lib/predict-saved-picks';
import { seo } from '@/lib/seo';
import { predict } from '@/routes';
import type { Auth } from '@/types';

interface PredictPageProps {
    dates: string[];
    selectedDate: string | null;
    fixtures: PredictFixture[];
    canResetPassword: boolean;
    passwordRules: string;
    [key: string]: unknown;
}

function formatDay(date: string): string {
    return new Date(`${date}T12:00:00`).toLocaleDateString('en-GB', {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
    });
}

function initialPicks(fixtures: PredictFixture[]): Record<number, MarketValue> {
    const picks: Record<number, MarketValue> = {};

    for (const fixture of fixtures) {
        for (const market of fixture.markets) {
            if (market.value !== null) {
                picks[market.id] = market.value;
            }
        }
    }

    return picks;
}

function initialBanker(fixtures: PredictFixture[]): number | null {
    for (const fixture of fixtures) {
        for (const market of fixture.markets) {
            if (market.isBanker) {
                return market.id;
            }
        }
    }

    return null;
}

function hydrateFromDraft(
    fixtures: PredictFixture[],
    selectedDate: string | null,
): { picks: Record<number, MarketValue>; banker: number | null } {
    const picks = initialPicks(fixtures);
    const banker = initialBanker(fixtures);

    if (selectedDate === null) {
        return { picks, banker };
    }

    const draft = loadPredictDraft(selectedDate);

    if (draft === null) {
        return { picks, banker };
    }

    return {
        picks: { ...picks, ...draft.picks },
        banker: draft.banker ?? banker,
    };
}

function PredictDay({
    fixtures,
    selectedDate,
    dates,
    canResetPassword,
    passwordRules,
}: PredictPageProps) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const isGuest = auth.user === null;

    const [{ picks, banker }, setState] = useState(() =>
        hydrateFromDraft(fixtures, selectedDate),
    );

    const [errors, setErrors] = useState<Record<string, string>>({});
    const [processing, setProcessing] = useState(false);
    const [authDialogOpen, setAuthDialogOpen] = useState(false);
    const [updateDialogOpen, setUpdateDialogOpen] = useState(false);
    const autoSubmitHandled = useRef(false);
    const pendingSubmitRef = useRef<{
        picks: Record<number, MarketValue>;
        banker: number | null;
        clearDraftOnSuccess: boolean;
    } | null>(null);
    const hadSavedPicksOnLoad = useRef(hasSavedPicksForDay(fixtures));
    const now = useNow();

    useEffect(() => {
        if (auth.user !== null) {
            setAuthDialogOpen(false);
        }
    }, [auth.user]);

    const rememberReturn = useCallback(async () => {
        const returnUrl = window.location.pathname + window.location.search;

        const xsrf = document.cookie
            .split('; ')
            .find((row) => row.startsWith('XSRF-TOKEN='))
            ?.split('=')[1];

        await fetch(rememberReturnUrl.url(), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                ...(xsrf ? { 'X-XSRF-TOKEN': decodeURIComponent(xsrf) } : {}),
            },
            body: JSON.stringify({ return_url: returnUrl }),
        });
    }, []);

    const promptGuestAuthForSubmit = useCallback(() => {
        if (!isGuest) {
            return false;
        }

        if (selectedDate !== null) {
            savePredictDraft(selectedDate, {
                picks,
                banker,
                pendingSubmit: true,
            });
        }

        void rememberReturn();
        setAuthDialogOpen(true);

        return true;
    }, [banker, isGuest, picks, rememberReturn, selectedDate]);

    const submitPredictions = useCallback(
        (
            payload: {
                picks: Record<number, MarketValue>;
                banker: number | null;
            },
            clearDraftOnSuccess = true,
        ) => {
            const predictions = Object.entries(payload.picks).map(
                ([id, value]) => ({
                    fixture_market_id: Number(id),
                    value,
                }),
            );

            router.post(
                storePredictions.url(),
                {
                    date: selectedDate,
                    predictions,
                    banker_fixture_market_id: payload.banker,
                },
                {
                    preserveScroll: true,
                    onStart: () => setProcessing(true),
                    onFinish: () => setProcessing(false),
                    onSuccess: () => {
                        setErrors({});

                        if (clearDraftOnSuccess && selectedDate !== null) {
                            clearPredictDraft(selectedDate);
                        }

                        toast.success('Your picks are in.');
                    },
                    onError: (formErrors) => {
                        setErrors(formErrors as Record<string, string>);
                        const unique = [
                            ...new Set(Object.values(formErrors)),
                        ];
                        unique.forEach((msg) => toast.error(String(msg)));
                    },
                },
            );
        },
        [selectedDate],
    );

    const requestSubmit = useCallback(
        (
            payload: {
                picks: Record<number, MarketValue>;
                banker: number | null;
            },
            clearDraftOnSuccess = true,
        ) => {
            if (hadSavedPicksOnLoad.current) {
                pendingSubmitRef.current = {
                    picks: payload.picks,
                    banker: payload.banker,
                    clearDraftOnSuccess,
                };
                setUpdateDialogOpen(true);

                return;
            }

            submitPredictions(payload, clearDraftOnSuccess);
        },
        [submitPredictions],
    );

    const confirmUpdate = useCallback(() => {
        const pending = pendingSubmitRef.current;

        if (pending === null) {
            setUpdateDialogOpen(false);

            return;
        }

        submitPredictions(
            { picks: pending.picks, banker: pending.banker },
            pending.clearDraftOnSuccess,
        );
        pendingSubmitRef.current = null;
        setUpdateDialogOpen(false);
    }, [submitPredictions]);

    useEffect(() => {
        if (isGuest || selectedDate === null || autoSubmitHandled.current) {
            return;
        }

        const draft = loadPredictDraft(selectedDate);

        if (draft === null) {
            return;
        }

        if (Object.keys(draft.picks).length > 0 || draft.banker !== null) {
            setState((current) => ({
                picks: { ...current.picks, ...draft.picks },
                banker: draft.banker ?? current.banker,
            }));
        }

        if (!draft.pendingSubmit) {
            return;
        }

        autoSubmitHandled.current = true;

        savePredictDraft(selectedDate, {
            picks: draft.picks,
            banker: draft.banker,
            pendingSubmit: false,
        });

        requestSubmit(
            {
                picks: { ...initialPicks(fixtures), ...draft.picks },
                banker: draft.banker,
            },
            true,
        );
    }, [fixtures, isGuest, selectedDate, requestSubmit]);

    const handlePick = (marketId: number, value: MarketValue) => {
        setState((current) => {
            const nextPicks = { ...current.picks, [marketId]: value };

            if (isGuest && selectedDate !== null) {
                savePredictDraft(selectedDate, {
                    picks: nextPicks,
                    banker: current.banker,
                });
            }

            return { ...current, picks: nextPicks };
        });
    };

    const handleBanker = (marketId: number) => {
        setState((current) => {
            const nextBanker = current.banker === marketId ? null : marketId;

            if (isGuest && selectedDate !== null) {
                savePredictDraft(selectedDate, {
                    picks: current.picks,
                    banker: nextBanker,
                });
            }

            return { ...current, banker: nextBanker };
        });
    };

    const index = selectedDate ? dates.indexOf(selectedDate) : -1;
    const prevDate = index > 0 ? dates[index - 1] : null;
    const nextDate =
        index >= 0 && index < dates.length - 1 ? dates[index + 1] : null;

    const openCount = fixtures.reduce(
        (total, fixture) =>
            total + fixture.markets.filter((market) => !market.locked).length,
        0,
    );

    const pickCount = Object.keys(picks).length;

    const submit = () => {
        if (promptGuestAuthForSubmit()) {
            return;
        }

        requestSubmit({ picks, banker });
    };

    return (
        <ScoreStepperProvider>
            <div className="mx-auto w-full max-w-3xl px-4 py-6">
                <div className="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h1 className="font-display text-4xl tracking-wide">
                            make your picks
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            predict each market before kickoff. Star one pick as
                            your{' '}
                            <span className="font-semibold text-wc-gold-deep">
                                banker
                            </span>{' '}
                            to double its points.
                        </p>
                    </div>
                    <span className="inline-flex items-center gap-1.5 font-mono text-xs tracking-wider text-muted-foreground uppercase">
                        <CalendarDays className="size-3.5" />
                        {pickCount} picked · {openCount} open
                    </span>
                </div>

                <div className="mt-5 flex items-center justify-between gap-2 rounded-lg border bg-card px-2 py-2">
                    <Button
                        asChild={prevDate !== null}
                        variant="ghost"
                        size="sm"
                        disabled={prevDate === null}
                    >
                        {prevDate !== null ? (
                            <Link
                                href={predict({ query: { date: prevDate } })}
                                preserveScroll
                            >
                                <ChevronLeft className="size-4" />
                                <span className="hidden sm:inline">
                                    Prev day
                                </span>
                            </Link>
                        ) : (
                            <span>
                                <ChevronLeft className="size-4" />
                            </span>
                        )}
                    </Button>
                    <span className="font-display text-lg tracking-wide uppercase">
                        {selectedDate ? formatDay(selectedDate) : 'No fixtures'}
                    </span>
                    <Button
                        asChild={nextDate !== null}
                        variant="ghost"
                        size="sm"
                        disabled={nextDate === null}
                    >
                        {nextDate !== null ? (
                            <Link
                                href={predict({ query: { date: nextDate } })}
                                preserveScroll
                            >
                                <span className="hidden sm:inline">
                                    Next day
                                </span>
                                <ChevronRight className="size-4" />
                            </Link>
                        ) : (
                            <span>
                                <ChevronRight className="size-4" />
                            </span>
                        )}
                    </Button>
                </div>

                {nextDate !== null && (
                    <p className="mt-2 flex items-center justify-end gap-1 text-xs text-muted-foreground">
                        tomorrow&apos;s fixtures are open — tap
                        <ChevronRight className="size-3.5" aria-hidden />
                        to predict early
                    </p>
                )}

                {(errors.predictions || errors.banker) && (
                    <p className="mt-4 rounded-md bg-wc-primary/10 px-3 py-2 text-sm font-medium text-wc-primary">
                        {errors.predictions ?? errors.banker}
                    </p>
                )}

                <div className="mt-4 space-y-4">
                    {fixtures.length === 0 ? (
                        <div className="rounded-xl border border-dashed bg-card px-6 py-12 text-center text-sm text-muted-foreground">
                            No matches to predict on this day.
                        </div>
                    ) : (
                        fixtures.map((fixture) => (
                            <FixtureCard
                                key={fixture.id}
                                fixture={fixture}
                                picks={picks}
                                banker={banker}
                                errors={errors}
                                now={now}
                                onPick={handlePick}
                                onBanker={handleBanker}
                            />
                        ))
                    )}
                </div>

                {fixtures.length > 0 && (
                    <div className="sticky bottom-4 mt-6 flex justify-end">
                        <Button
                            type="button"
                            variant="gold"
                            size="lg"
                            disabled={processing || openCount === 0}
                            onClick={submit}
                            className="shadow-lg"
                        >
                            {openCount === 0 ? 'Day locked' : 'Save my picks'}
                        </Button>
                    </div>
                )}
            </div>

            <PredictAuthDialog
                open={authDialogOpen}
                onOpenChange={setAuthDialogOpen}
                canResetPassword={canResetPassword}
                passwordRules={passwordRules}
            />

            <PredictUpdateDialog
                open={updateDialogOpen}
                onOpenChange={setUpdateDialogOpen}
                onConfirm={confirmUpdate}
                processing={processing}
            />
        </ScoreStepperProvider>
    );
}

export default function Predict() {
    const props = usePage<PredictPageProps>().props;

    return (
        <>
            <SeoHead {...seo.predict} />
            <ProductShell>
                <PredictDay key={props.selectedDate ?? 'none'} {...props} />
            </ProductShell>
        </>
    );
}
