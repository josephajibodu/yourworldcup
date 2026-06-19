import { cn } from '@/lib/utils';
import { ScoreStepper } from './score-stepper';
import { useScoreStepper } from './score-stepper-context';
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
                    marketId={market.id}
                    value={value}
                    disabled={disabled}
                    onChange={onChange}
                />
            );
        case 'integer':
            return (
                <IntegerInput
                    marketId={market.id}
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

interface SteppedInputProps {
    marketId: number;
    value: MarketValue | null;
    disabled: boolean;
    onChange: (value: MarketValue) => void;
}

function ScorelineInput({
    marketId,
    value,
    disabled,
    onChange,
}: SteppedInputProps) {
    const { isExpanded, activate } = useScoreStepper();
    const home = value && 'home' in value ? value.home : 0;
    const away = value && 'away' in value ? value.away : 0;

    const update = (side: 'home' | 'away', next: number) => {
        onChange({
            home: side === 'home' ? next : home,
            away: side === 'away' ? next : away,
        });
    };

    return (
        <div className="flex items-center gap-2">
            <ScoreStepper
                label="home score"
                value={home}
                disabled={disabled}
                expanded={!disabled && isExpanded(marketId, 'home')}
                onActivate={() => activate(marketId, 'home')}
                onChange={(next) => update('home', next)}
            />
            <span className="font-mono text-sm font-semibold text-muted-foreground">
                –
            </span>
            <ScoreStepper
                label="away score"
                value={away}
                disabled={disabled}
                expanded={!disabled && isExpanded(marketId, 'away')}
                onActivate={() => activate(marketId, 'away')}
                onChange={(next) => update('away', next)}
            />
        </div>
    );
}

function IntegerInput({
    marketId,
    value,
    disabled,
    onChange,
}: SteppedInputProps) {
    const { isExpanded, activate } = useScoreStepper();
    const current = value && 'value' in value ? value.value : 0;

    return (
        <ScoreStepper
            label="value"
            value={current}
            disabled={disabled}
            expanded={!disabled && isExpanded(marketId, 'value')}
            onActivate={() => activate(marketId, 'value')}
            onChange={(next) => onChange({ value: next })}
        />
    );
}
