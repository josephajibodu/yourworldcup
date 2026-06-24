import {
    createContext,
    useCallback,
    useContext,
    useMemo,
    useState
    
} from 'react';
import type {ReactNode} from 'react';

export type ScoreStepperSide = 'home' | 'away' | 'value';

interface ActiveScoreStepper {
    marketId: number;
    side: ScoreStepperSide;
}

interface ScoreStepperContextValue {
    isExpanded: (marketId: number, side: ScoreStepperSide) => boolean;
    activate: (marketId: number, side: ScoreStepperSide) => void;
    dismiss: () => void;
}

const ScoreStepperContext = createContext<ScoreStepperContextValue | null>(
    null,
);

export function ScoreStepperProvider({ children }: { children: ReactNode }) {
    const [active, setActive] = useState<ActiveScoreStepper | null>(null);

    const activate = useCallback(
        (marketId: number, side: ScoreStepperSide) => {
            setActive({ marketId, side });
        },
        [],
    );

    const dismiss = useCallback(() => {
        setActive(null);
    }, []);

    const value = useMemo(
        (): ScoreStepperContextValue => ({
            isExpanded: (marketId, side) =>
                active?.marketId === marketId && active?.side === side,
            activate,
            dismiss,
        }),
        [active, activate, dismiss],
    );

    return (
        <ScoreStepperContext.Provider value={value}>
            {children}
        </ScoreStepperContext.Provider>
    );
}

export function useScoreStepper(): ScoreStepperContextValue {
    const context = useContext(ScoreStepperContext);

    if (context === null) {
        throw new Error(
            'useScoreStepper must be used within ScoreStepperProvider',
        );
    }

    return context;
}
