export type LeagueTeam = {
    id: number;
    name: string;
    short_name: string;
    power: number;
    sort_order: number;
};

export type LeagueStandingRow = {
    team: LeagueTeam;
    played: number;
    won: number;
    drawn: number;
    lost: number;
    gf: number;
    ga: number;
    gd: number;
    points: number;
    title_percent: number | null;
};

export type LeagueMatchRow = {
    id: number;
    matchweek: number;
    home_team: LeagueTeam;
    away_team: LeagueTeam;
    home_goals: number | null;
    away_goals: number | null;
    status: string;
    is_manually_edited: boolean;
};

export type LeagueWeekBlock = {
    week: number;
    matches: LeagueMatchRow[];
};

export type LeaguePredictionHistoryGrid = {
    after_matchweeks: number[];
    rows: Array<{
        team: LeagueTeam;
        points: (number | null)[];
        percentages: (number | null)[];
    }>;
};

export type LeaguePredictionTrackRecord = {
    favorite_hits: number;
    favorite_total: number;
    rate_percent: number | null;
    champion_team_ids: number[];
    champion_names: string[];
};
