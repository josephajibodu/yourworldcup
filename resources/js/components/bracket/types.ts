export interface StandingRow {
    id: number;
    name: string;
    code: string;
    flag: string | null;
    played: number;
    won: number;
    drawn: number;
    lost: number;
    gf: number;
    ga: number;
    gd: number;
    points: number;
}

export interface GroupFixtureRef {
    kickoffAt: string;
    status: string;
    homeTeamId: number;
    awayTeamId: number;
}

export interface GroupTable {
    code: string;
    teams: StandingRow[];
    fixtures: GroupFixtureRef[];
}

export interface SlotTeam {
    name: string;
    code: string;
    flag: string | null;
}

export interface Slot {
    team: SlotTeam | null;
    label: string | null;
}

export interface KnockoutMatch {
    id: number;
    code: string;
    stage: string;
    stageLabel: string;
    status: string;
    kickoffAt: string;
    stadium: string | null;
    city: string | null;
    home: Slot;
    away: Slot;
    feeders: [number, number] | null;
}

export interface GroupNodeData {
    [key: string]: unknown;
    group: GroupTable;
    active?: boolean;
}

export interface MatchNodeData {
    [key: string]: unknown;
    match: KnockoutMatch;
    active?: boolean;
}

export interface TrophyNodeData {
    [key: string]: unknown;
    label: string;
}
