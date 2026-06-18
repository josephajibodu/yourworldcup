export type MarketInputType =
    | 'single_select'
    | 'boolean'
    | 'scoreline'
    | 'integer';

export interface MarketOption {
    value: string;
    label: string;
}

export type MarketValue =
    | { selected: string }
    | { answer: boolean }
    | { home: number; away: number }
    | { value: number };

export interface PredictMarket {
    id: number;
    key: string;
    name: string;
    description: string | null;
    inputType: MarketInputType;
    options: MarketOption[] | null;
    points: number;
    locked: boolean;
    value: MarketValue | null;
    isBanker: boolean;
}

export interface PredictTeam {
    name: string;
    code: string;
    flag: string | null;
}

export interface PredictFixture {
    id: number;
    stage: string;
    stageLabel: string;
    group: string | null;
    status: string;
    kickoffAt: string;
    lockAt: string;
    locked: boolean;
    stadium: string | null;
    city: string | null;
    home: PredictTeam | null;
    away: PredictTeam | null;
    markets: PredictMarket[];
}
