import { cn } from '@/lib/utils';
import type { MarketValue, PredictMarket } from './types';

interface MarketInputProps {
    market: PredictMarket;
    value: MarketValue | null;
    disabled: boolean;
    onChange: (value: MarketValue) => void;
}

export function MarketInput({
    market,
    value,
    disabled,
    onChange,
}: MarketInputProps) {
    switch (market.inputType) {
        case 'single_select':
            return (
                <SingleSelect
                    market={market}
                    value={value}
                    disabled={disabled}
                    onChange={onChange}
                />
            );
        case 'boolean':
            return (
                <BooleanInput
                    value={value}
                    disabled={disabled}
                    onChange={onChange}
                />
            );
        case 'scoreline':
            return (
                <ScorelineInput
                    value={value}
                    disabled={disabled}
                    onChange={onChange}
                />
            );
        case 'integer':
            return (
                <IntegerInput
                    value={value}
                    disabled={disabled}
                    onChange={onChange}
                />
            );
    }
}

const segmentBase =
    'flex-1 rounded-md px-3 py-2 text-sm font-semibold transition-colors disabled:cursor-not-allowed disabled:opacity-60';

function SingleSelect({ market, value, disabled, onChange }: MarketInputProps) {
    const selected =
        value && 'selected' in value ? value.selected : null;

    return (
        <div className="flex gap-2">
            {(market.options ?? []).map((option) => {
                const active = selected === option.value;

                return (
                    <button
                        key={option.value}
                        type="button"
                        disabled={disabled}
                        onClick={() => onChange({ selected: option.value })}
                        className={cn(
                            segmentBase,
                            'border',
                            active
                                ? 'border-wc-primary bg-wc-primary text-white'
                                : 'border-border bg-background hover:border-wc-primary/40 hover:bg-secondary',
                        )}
                    >
                        <span className="line-clamp-1">{option.label}</span>
                    </button>
                );
            })}
        </div>
    );
}

function BooleanInput({
    value,
    disabled,
    onChange,
}: Omit<MarketInputProps, 'market'>) {
    const answer = value && 'answer' in value ? value.answer : null;

    return (
        <div className="flex gap-2">
            {[
                { label: 'Yes', val: true },
                { label: 'No', val: false },
            ].map((option) => {
                const active = answer === option.val;

                return (
                    <button
                        key={option.label}
                        type="button"
                        disabled={disabled}
                        onClick={() => onChange({ answer: option.val })}
                        className={cn(
                            segmentBase,
                            'border',
                            active
                                ? 'border-wc-primary bg-wc-primary text-white'
                                : 'border-border bg-background hover:border-wc-primary/40 hover:bg-secondary',
                        )}
                    >
                        {option.label}
                    </button>
                );
            })}
        </div>
    );
}

function ScorelineInput({
    value,
    disabled,
    onChange,
}: Omit<MarketInputProps, 'market'>) {
    const home = value && 'home' in value ? value.home : '';
    const away = value && 'away' in value ? value.away : '';

    const update = (side: 'home' | 'away', raw: string) => {
        const next = raw === '' ? 0 : Math.max(0, Math.min(30, Number(raw)));
        const current = {
            home: typeof home === 'number' ? home : 0,
            away: typeof away === 'number' ? away : 0,
        };
        onChange({ ...current, [side]: next });
    };

    return (
        <div className="flex items-center gap-2">
            <ScoreField
                value={home}
                disabled={disabled}
                onChange={(raw) => update('home', raw)}
            />
            <span className="font-mono text-sm text-muted-foreground">–</span>
            <ScoreField
                value={away}
                disabled={disabled}
                onChange={(raw) => update('away', raw)}
            />
        </div>
    );
}

function ScoreField({
    value,
    disabled,
    onChange,
}: {
    value: number | '';
    disabled: boolean;
    onChange: (raw: string) => void;
}) {
    return (
        <input
            type="number"
            min={0}
            max={30}
            inputMode="numeric"
            disabled={disabled}
            value={value}
            onChange={(event) => onChange(event.target.value)}
            className="h-11 w-14 rounded-md border border-border bg-background text-center font-mono text-lg font-semibold tabular-nums outline-none focus-visible:border-wc-ink focus-visible:ring-2 focus-visible:ring-wc-ink/25 disabled:opacity-60"
        />
    );
}

function IntegerInput({
    value,
    disabled,
    onChange,
}: Omit<MarketInputProps, 'market'>) {
    const current = value && 'value' in value ? value.value : '';

    return (
        <input
            type="number"
            min={0}
            inputMode="numeric"
            disabled={disabled}
            value={current}
            onChange={(event) =>
                onChange({
                    value:
                        event.target.value === ''
                            ? 0
                            : Math.max(0, Number(event.target.value)),
                })
            }
            className="h-11 w-20 rounded-md border border-border bg-background text-center font-mono text-lg font-semibold tabular-nums outline-none focus-visible:border-wc-ink focus-visible:ring-2 focus-visible:ring-wc-ink/25 disabled:opacity-60"
        />
    );
}
