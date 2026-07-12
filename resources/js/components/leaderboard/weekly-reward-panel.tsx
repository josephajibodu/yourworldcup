import { Form, Link, usePage } from '@inertiajs/react';
import { Gift, LogIn, Sparkles } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { login } from '@/routes';
import { store } from '@/routes/rewards/weekly-claim';

type RewardPreference = 'airtime' | 'data' | 'cash';

interface EligibleSlot {
    rank: number;
    passedDown: boolean;
    passedFromName: string | null;
    passOnMessage: string | null;
}

interface SubmittedClaim {
    passedOn: boolean;
    preference: RewardPreference | null;
    passOnMessage: string | null;
}

export interface WeeklyRewardStatus {
    ready: boolean;
    considerationCeiling: number;
    pendingRanks: number[];
    eligible: EligibleSlot[];
    submitted: SubmittedClaim[];
}

const preferenceOptions: { value: RewardPreference; label: string; hint: string }[] = [
    { value: 'airtime', label: 'Airtime', hint: 'Top up your phone credit' },
    { value: 'data', label: 'Data', hint: 'Mobile data bundle' },
    { value: 'cash', label: 'Cash equivalent', hint: 'Same value as airtime or data' },
];

const mobileNetworks = [
    { value: 'mtn', label: 'MTN' },
    { value: 'airtel', label: 'Airtel' },
    { value: 'glo', label: 'Glo' },
    { value: '9mobile', label: '9mobile' },
] as const;

const selectClassName =
    'flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50';

function rankLabel(rank: number): string {
    if (rank === 1) {
        return '1st';
    }

    if (rank === 2) {
        return '2nd';
    }

    if (rank === 3) {
        return '3rd';
    }

    return `${rank}th`;
}

function formatPendingRanks(ranks: number[]): string {
    if (ranks.length === 0) {
        return '';
    }

    if (ranks.length === 1) {
        return rankLabel(ranks[0]);
    }

    if (ranks.length === 2) {
        return `${rankLabel(ranks[0])} and ${rankLabel(ranks[1])}`;
    }

    const last = ranks[ranks.length - 1];
    const rest = ranks.slice(0, -1).map(rankLabel).join(', ');

    return `${rest}, and ${rankLabel(last)}`;
}

function GuestRewardNudge({
    pendingRanks,
}: {
    pendingRanks: number[];
}) {
    return (
        <div className="rounded-2xl border border-wc-gold/30 bg-wc-gold/10 p-5">
            <div className="flex items-start gap-4">
                <div className="grid size-12 shrink-0 place-items-center rounded-full bg-wc-gold/15 ring-1 ring-wc-gold/35">
                    <Gift className="size-6 text-wc-gold" />
                </div>
                <div className="min-w-0 flex-1">
                    <p className="font-mono text-[11px] font-bold tracking-[0.18em] text-wc-gold uppercase">
                        weekly airtime reward
                    </p>
                    <h3 className="mt-1 text-lg font-bold text-wc-ink">
                        Claim window is open
                    </h3>
                    <p className="mt-1 text-sm leading-relaxed text-wc-ink/65">
                        If you finished{' '}
                        <span className="font-semibold text-wc-ink">
                            {formatPendingRanks(pendingRanks)}
                        </span>{' '}
                        this week, log in to claim your airtime or pass it on
                        to the next player.
                    </p>
                    <Button
                        asChild
                        className="mt-4 rounded-full"
                    >
                        <Link href={login()}>
                            <LogIn className="size-4" />
                            Log in to claim
                        </Link>
                    </Button>
                </div>
            </div>
        </div>
    );
}

function rewardHeading(): string {
    return "You're being considered for this week's airtime";
}

function rewardDescription(slot: EligibleSlot): string {
    if (slot.passedDown) {
        const from = slot.passedFromName
            ? `${slot.passedFromName} passed their spot on`
            : 'Someone above you passed their spot on';

        return `You finished ${rankLabel(slot.rank)} this week. ${from}, so you're now in the running too.`;
    }

    return `You finished ${rankLabel(slot.rank)} this week. Tell us how you'd like your reward, or pass it on so the next player can be considered.`;
}

function WeeklyRewardClaimCard({
    weekStart,
    slot,
}: {
    weekStart: string;
    slot: EligibleSlot;
}) {
    const [passedOn, setPassedOn] = useState(false);
    const [preference, setPreference] = useState<RewardPreference>('airtime');
    const [mobileNetwork, setMobileNetwork] = useState<string>('mtn');

    const prefersMobileTopUp =
        !passedOn &&
        (preference === 'airtime' || preference === 'data');
    const prefersCash = !passedOn && preference === 'cash';

    return (
        <div className="rounded-2xl border border-wc-gold/30 bg-wc-gold/10 p-5">
            <div className="flex items-start gap-4">
                <div className="grid size-12 shrink-0 place-items-center rounded-full bg-wc-gold/15 ring-1 ring-wc-gold/35">
                    <Gift className="size-6 text-wc-gold" />
                </div>
                <div className="min-w-0 flex-1">
                    <p className="font-mono text-[11px] font-bold tracking-[0.18em] text-wc-gold uppercase">
                        weekly airtime reward
                    </p>
                    <h3 className="mt-1 text-lg font-bold text-wc-ink">
                        {rewardHeading()}
                    </h3>
                    <p className="mt-1 text-sm leading-relaxed text-wc-ink/65">
                        {rewardDescription(slot)}
                    </p>

                    {slot.passedDown && slot.passOnMessage && (
                        <blockquote className="mt-3 rounded-xl border border-wc-gold/25 bg-white/60 px-4 py-3 text-sm leading-relaxed text-wc-ink/75">
                            <p className="font-mono text-[10px] font-bold tracking-[0.16em] text-wc-gold uppercase">
                                message from {slot.passedFromName ?? 'them'}
                            </p>
                            <p className="mt-1 italic">
                                &ldquo;{slot.passOnMessage}&rdquo;
                            </p>
                        </blockquote>
                    )}

                    <Form
                        {...store.form()}
                        options={{ preserveScroll: true }}
                        className="mt-5 space-y-5"
                    >
                        {({ processing, errors, wasSuccessful }) => (
                            <>
                                <input
                                    type="hidden"
                                    name="week_start"
                                    value={weekStart}
                                />
                                <input
                                    type="hidden"
                                    name="passed_on"
                                    value={passedOn ? '1' : '0'}
                                />
                                {!passedOn && (
                                    <input
                                        type="hidden"
                                        name="preference"
                                        value={preference}
                                    />
                                )}

                                <div className="space-y-3">
                                    <Label className="text-sm font-semibold text-wc-ink">
                                        How would you like your reward?
                                    </Label>
                                    <div className="grid gap-2 sm:grid-cols-3">
                                        {preferenceOptions.map((option) => (
                                            <label
                                                key={option.value}
                                                className={`cursor-pointer rounded-xl border p-3 transition-colors ${
                                                    !passedOn &&
                                                    preference === option.value
                                                        ? 'border-wc-gold bg-wc-gold/15'
                                                        : 'border-wc-ink/10 bg-white/60 hover:border-wc-gold/40'
                                                } ${passedOn ? 'opacity-50' : ''}`}
                                            >
                                                <input
                                                    type="radio"
                                                    className="sr-only"
                                                    checked={
                                                        preference ===
                                                        option.value
                                                    }
                                                    disabled={passedOn}
                                                    onChange={() =>
                                                        setPreference(
                                                            option.value,
                                                        )
                                                    }
                                                />
                                                <span className="block text-sm font-semibold text-wc-ink">
                                                    {option.label}
                                                </span>
                                                <span className="mt-0.5 block text-xs text-wc-ink/55">
                                                    {option.hint}
                                                </span>
                                            </label>
                                        ))}
                                    </div>
                                    <InputError message={errors.preference} />
                                </div>

                                {prefersMobileTopUp && (
                                    <div className="space-y-4 rounded-xl border border-wc-ink/10 bg-white/50 p-4">
                                        <p className="text-sm font-semibold text-wc-ink">
                                            Top-up details
                                        </p>
                                        <div className="grid gap-4 sm:grid-cols-2">
                                            <div className="space-y-2">
                                                <Label htmlFor={`phone-${slot.rank}`}>
                                                    Phone number
                                                </Label>
                                                <Input
                                                    id={`phone-${slot.rank}`}
                                                    name="phone_number"
                                                    type="tel"
                                                    inputMode="numeric"
                                                    autoComplete="tel"
                                                    placeholder="08012345678"
                                                    required
                                                />
                                                <InputError
                                                    message={errors.phone_number}
                                                />
                                            </div>
                                            <div className="space-y-2">
                                                <Label
                                                    htmlFor={`network-${slot.rank}`}
                                                >
                                                    Network
                                                </Label>
                                                <select
                                                    id={`network-${slot.rank}`}
                                                    name="mobile_network"
                                                    className={selectClassName}
                                                    value={mobileNetwork}
                                                    onChange={(event) =>
                                                        setMobileNetwork(
                                                            event.target.value,
                                                        )
                                                    }
                                                    required
                                                >
                                                    {mobileNetworks.map(
                                                        (network) => (
                                                            <option
                                                                key={
                                                                    network.value
                                                                }
                                                                value={
                                                                    network.value
                                                                }
                                                            >
                                                                {network.label}
                                                            </option>
                                                        ),
                                                    )}
                                                </select>
                                                <InputError
                                                    message={
                                                        errors.mobile_network
                                                    }
                                                />
                                            </div>
                                        </div>
                                    </div>
                                )}

                                {prefersCash && (
                                    <div className="space-y-4 rounded-xl border border-wc-ink/10 bg-white/50 p-4">
                                        <p className="text-sm font-semibold text-wc-ink">
                                            Bank details
                                        </p>
                                        <div className="space-y-4">
                                            <div className="space-y-2">
                                                <Label
                                                    htmlFor={`account-name-${slot.rank}`}
                                                >
                                                    Account name
                                                </Label>
                                                <Input
                                                    id={`account-name-${slot.rank}`}
                                                    name="account_holder_name"
                                                    autoComplete="name"
                                                    placeholder="Name on bank account"
                                                    required
                                                />
                                                <InputError
                                                    message={
                                                        errors.account_holder_name
                                                    }
                                                />
                                            </div>
                                            <div className="grid gap-4 sm:grid-cols-2">
                                                <div className="space-y-2">
                                                    <Label
                                                        htmlFor={`bank-name-${slot.rank}`}
                                                    >
                                                        Bank name
                                                    </Label>
                                                    <Input
                                                        id={`bank-name-${slot.rank}`}
                                                        name="bank_name"
                                                        placeholder="e.g. GTBank"
                                                        required
                                                    />
                                                    <InputError
                                                        message={
                                                            errors.bank_name
                                                        }
                                                    />
                                                </div>
                                                <div className="space-y-2">
                                                    <Label
                                                        htmlFor={`account-number-${slot.rank}`}
                                                    >
                                                        Account number
                                                    </Label>
                                                    <Input
                                                        id={`account-number-${slot.rank}`}
                                                        name="account_number"
                                                        inputMode="numeric"
                                                        autoComplete="off"
                                                        placeholder="0123456789"
                                                        maxLength={10}
                                                        required
                                                    />
                                                    <InputError
                                                        message={
                                                            errors.account_number
                                                        }
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                )}

                                <div className="space-y-3 rounded-xl border border-wc-ink/10 bg-white/50 p-4">
                                    <div className="flex items-start gap-3">
                                        <Checkbox
                                            id={`pass-on-${slot.rank}`}
                                            checked={passedOn}
                                            onCheckedChange={(checked) =>
                                                setPassedOn(checked === true)
                                            }
                                        />
                                        <div className="space-y-1">
                                            <Label
                                                htmlFor={`pass-on-${slot.rank}`}
                                                className="text-sm font-semibold text-wc-ink"
                                            >
                                                Pass it on to the next player
                                            </Label>
                                            <p className="text-xs leading-relaxed text-wc-ink/55">
                                                If you pass, the next
                                                unconsidered player can be
                                                considered for this week&apos;s
                                                airtime.
                                            </p>
                                        </div>
                                    </div>

                                    {passedOn && (
                                        <div className="space-y-2">
                                            <Label
                                                htmlFor={`pass-message-${slot.rank}`}
                                                className="text-sm text-wc-ink/75"
                                            >
                                                Optional message
                                            </Label>
                                            <textarea
                                                id={`pass-message-${slot.rank}`}
                                                name="pass_on_message"
                                                rows={3}
                                                maxLength={500}
                                                placeholder="Say something nice, or leave this blank."
                                                className="flex min-h-20 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                            />
                                            <InputError
                                                message={errors.pass_on_message}
                                            />
                                        </div>
                                    )}
                                </div>

                                <InputError message={errors.week_start} />

                                <Button
                                    type="submit"
                                    disabled={processing || wasSuccessful}
                                    className="rounded-full"
                                >
                                    {processing
                                        ? 'Submitting...'
                                        : passedOn
                                          ? 'Pass reward on'
                                          : 'Claim my reward'}
                                </Button>

                                {wasSuccessful && (
                                    <p className="text-sm font-medium text-wc-primary">
                                        Thanks — your response has been recorded.
                                    </p>
                                )}
                            </>
                        )}
                    </Form>
                </div>
            </div>
        </div>
    );
}

function SubmittedClaimSummary({ claim }: { claim: SubmittedClaim }) {
    return (
        <div className="rounded-xl border border-wc-ink/10 bg-white/50 px-4 py-3 text-sm text-wc-ink/75">
            {claim.passedOn ? (
                <>
                    <span className="font-semibold text-wc-ink">
                        Passed spot on
                    </span>
                    {claim.passOnMessage
                        ? ` — “${claim.passOnMessage}”`
                        : ''}
                </>
            ) : (
                <>
                    <span className="font-semibold text-wc-ink">Claimed</span>{' '}
                    as {claim.preference}
                </>
            )}
        </div>
    );
}

export function WeeklyRewardPanel({
    weekStart,
    status,
}: {
    weekStart: string;
    status: WeeklyRewardStatus;
}) {
    const { auth } = usePage().props as { auth: { user: unknown } };
    const isLoggedIn = auth.user !== null;
    const hasSubmitted = status.submitted.length > 0;

    if (!status.ready) {
        if (!isLoggedIn || !hasSubmitted) {
            return null;
        }

        return (
            <div className="mt-8 space-y-4">
                <div className="rounded-2xl border border-wc-ink/10 bg-card p-5">
                    <div className="mb-3 flex items-center gap-2">
                        <Sparkles className="size-4 text-wc-gold" />
                        <p className="text-sm font-semibold text-wc-ink">
                            Your response this week
                        </p>
                    </div>
                    <div className="space-y-2">
                        {status.submitted.map((claim, index) => (
                            <SubmittedClaimSummary
                                key={index}
                                claim={claim}
                            />
                        ))}
                    </div>
                </div>
            </div>
        );
    }

    if (!isLoggedIn) {
        if (status.pendingRanks.length === 0) {
            return null;
        }

        return (
            <div className="mt-8">
                <GuestRewardNudge pendingRanks={status.pendingRanks} />
            </div>
        );
    }

    if (status.eligible.length === 0 && status.submitted.length === 0) {
        return null;
    }

    return (
        <div className="mt-8 space-y-4">
            {status.eligible.map((slot) => (
                <WeeklyRewardClaimCard
                    key={slot.rank}
                    weekStart={weekStart}
                    slot={slot}
                />
            ))}

            {status.submitted.length > 0 && (
                <div className="rounded-2xl border border-wc-ink/10 bg-card p-5">
                    <div className="mb-3 flex items-center gap-2">
                        <Sparkles className="size-4 text-wc-gold" />
                        <p className="text-sm font-semibold text-wc-ink">
                            Your response this week
                        </p>
                    </div>
                    <div className="space-y-2">
                        {status.submitted.map((claim, index) => (
                            <SubmittedClaimSummary
                                key={index}
                                claim={claim}
                            />
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
}
