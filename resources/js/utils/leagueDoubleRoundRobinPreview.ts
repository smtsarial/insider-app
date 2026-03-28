import type { LeagueMatchRow, LeagueTeam, LeagueWeekBlock } from '@/types/league';

/** Same pairing matrix as `RoundRobinFixtureGenerator` (four teams, double round-robin). */
const WEEK_INDEX_PAIRS: [number, number][][] = [
    [
        [0, 1],
        [2, 3],
    ],
    [
        [2, 0],
        [3, 1],
    ],
    [
        [0, 3],
        [1, 2],
    ],
    [
        [1, 0],
        [3, 2],
    ],
    [
        [0, 2],
        [1, 3],
    ],
    [
        [3, 0],
        [2, 1],
    ],
];

function teamPayload(t: LeagueTeam): LeagueTeam {
    return {
        id: t.id,
        name: t.name,
        short_name: t.short_name,
        power: t.power,
        sort_order: t.sort_order,
    };
}

/**
 * Build a read-only 6-matchweek preview from the current club list and draw order.
 */
export function buildDoubleRoundRobinPreview(teams: LeagueTeam[]): LeagueWeekBlock[] {
    if (teams.length !== 4) {
        return [];
    }

    const sorted = [...teams].sort((a, b) => a.sort_order - b.sort_order);
    const ids = sorted.map((t) => t.id);
    const byId = new Map(sorted.map((t) => [t.id, t] as const));

    const weeks: LeagueWeekBlock[] = [];

    for (let wi = 0; wi < WEEK_INDEX_PAIRS.length; wi++) {
        const matchweek = wi + 1;
        const pairs = WEEK_INDEX_PAIRS[wi];
        const matches: LeagueMatchRow[] = pairs.map((pair, pairIndex) => {
            const homeId = ids[pair[0]];
            const awayId = ids[pair[1]];
            const home = byId.get(homeId);
            const away = byId.get(awayId);
            if (!home || !away) {
                throw new Error('Invalid team slot in round-robin preview.');
            }

            return {
                id: -(matchweek * 100 + pairIndex),
                matchweek,
                home_team: teamPayload(home),
                away_team: teamPayload(away),
                home_goals: null,
                away_goals: null,
                status: 'scheduled',
                is_manually_edited: false,
            };
        });

        weeks.push({ week: matchweek, matches });
    }

    return weeks;
}
