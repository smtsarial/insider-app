<script setup lang="ts">
import { computed, reactive, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    playAllWeeks,
    playNextWeek,
    reset,
    setViewWeek,
    updateMatch,
} from '@/actions/App/Http/Controllers/LeagueController';

type Team = {
    id: number;
    name: string;
    short_name: string;
    power: number;
};

type StandingRow = {
    team: Team;
    played: number;
    won: number;
    drawn: number;
    lost: number;
    gf: number;
    ga: number;
    gd: number;
    points: number;
};

type MatchRow = {
    id: number;
    matchweek: number;
    home_team: Team;
    away_team: Team;
    home_goals: number | null;
    away_goals: number | null;
    status: string;
    is_manually_edited: boolean;
};

type WeekBlock = {
    week: number;
    matches: MatchRow[];
};

type PredictionRow = {
    team_id: number;
    name: string;
    percent: number;
};

const props = defineProps<{
    currentViewWeek: number;
    lastCompletedMatchweek: number;
    nextPlayableWeek: number | null;
    seasonComplete: boolean;
    predictionActive: boolean;
    standings: StandingRow[];
    matchesByWeek: WeekBlock[];
    predictions: PredictionRow[];
}>();

const currentBlock = computed(() => {
    return (
        props.matchesByWeek.find((b) => b.week === props.currentViewWeek) ??
        props.matchesByWeek[0]
    );
});

const editScores = reactive<
    Record<number, { home: number; away: number } | undefined>
>({});

watch(
    () => props.matchesByWeek,
    (weeks) => {
        for (const b of weeks) {
            for (const m of b.matches) {
                if (
                    m.status === 'finished' &&
                    m.home_goals !== null &&
                    m.away_goals !== null
                ) {
                    editScores[m.id] = {
                        home: m.home_goals,
                        away: m.away_goals,
                    };
                }
            }
        }
    },
    { immediate: true, deep: true },
);

function selectWeek(w: number): void {
    router.post(setViewWeek.url(), { week: w });
}

function playNext(): void {
    router.post(playNextWeek.url());
}

function playAll(): void {
    router.post(playAllWeeks.url());
}

function resetLeague(): void {
    if (confirm('Reset all league data and return to team selection?')) {
        router.post(reset.url());
    }
}

function saveMatch(matchId: number): void {
    const s = editScores[matchId];
    if (!s) {
        return;
    }
    router.patch(updateMatch.url({ league_match: matchId }), {
        home_goals: s.home,
        away_goals: s.away,
    });
}

function scoreLine(m: MatchRow): string {
    if (m.status === 'finished' && m.home_goals !== null && m.away_goals !== null) {
        return `${m.home_goals} - ${m.away_goals}`;
    }
    return '—';
}
</script>

<template>
    <div class="min-h-screen bg-slate-50 p-6 text-slate-900">
        <Head title="Simulation" />

        <h1 class="mb-8 text-sm font-light text-slate-500">Simulation</h1>

        <div class="mb-4 flex flex-wrap gap-2">
            <button
                v-for="w in 6"
                :key="w"
                type="button"
                class="rounded px-3 py-1 text-sm"
                :class="
                    w === currentViewWeek
                        ? 'bg-teal-600 text-white'
                        : 'bg-white text-slate-700 ring-1 ring-slate-200'
                "
                @click="selectWeek(w)"
            >
                Week {{ w }}
            </button>
        </div>

        <div
            class="grid gap-6 lg:grid-cols-3 lg:items-start"
        >
            <div class="overflow-hidden rounded border border-slate-200 bg-white shadow-sm">
                <div class="bg-slate-600 px-3 py-2 text-sm font-medium text-white">
                    League table
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[320px] text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-200 bg-slate-100">
                                <th class="px-2 py-2">Team</th>
                                <th class="px-1 py-2">Pl</th>
                                <th class="px-1 py-2">W</th>
                                <th class="px-1 py-2">D</th>
                                <th class="px-1 py-2">L</th>
                                <th class="px-1 py-2">GF</th>
                                <th class="px-1 py-2">GA</th>
                                <th class="px-1 py-2">GD</th>
                                <th class="px-1 py-2">Pts</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="row in standings"
                                :key="row.team.id"
                                class="border-t border-slate-100"
                            >
                                <td class="px-2 py-1.5 font-medium">
                                    {{ row.team.name }}
                                </td>
                                <td class="px-1 py-1.5">{{ row.played }}</td>
                                <td class="px-1 py-1.5">{{ row.won }}</td>
                                <td class="px-1 py-1.5">{{ row.drawn }}</td>
                                <td class="px-1 py-1.5">{{ row.lost }}</td>
                                <td class="px-1 py-1.5">{{ row.gf }}</td>
                                <td class="px-1 py-1.5">{{ row.ga }}</td>
                                <td class="px-1 py-1.5">{{ row.gd }}</td>
                                <td class="px-1 py-1.5">{{ row.points }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="overflow-hidden rounded border border-slate-200 bg-white shadow-sm">
                <div class="bg-slate-600 px-3 py-2 text-sm font-medium text-white">
                    Week {{ currentViewWeek }}
                </div>
                <ul class="divide-y divide-slate-200 text-sm">
                    <li
                        v-for="m in currentBlock?.matches ?? []"
                        :key="m.id"
                        class="space-y-2 px-3 py-3"
                    >
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span>
                                {{ m.home_team.name }}
                                <span class="mx-2 text-slate-400">—</span>
                                {{ m.away_team.name }}
                            </span>
                            <span class="font-mono text-slate-700">{{
                                scoreLine(m)
                            }}</span>
                        </div>
                        <div
                            v-if="m.status === 'finished' && editScores[m.id]"
                            class="flex flex-wrap items-end gap-2 border-t border-slate-100 pt-2"
                        >
                            <label class="text-xs text-slate-500">
                                Home
                                <input
                                    v-model.number="editScores[m.id]!.home"
                                    type="number"
                                    min="0"
                                    max="30"
                                    class="mt-0.5 w-16 rounded border border-slate-300 px-2 py-1"
                                />
                            </label>
                            <label class="text-xs text-slate-500">
                                Away
                                <input
                                    v-model.number="editScores[m.id]!.away"
                                    type="number"
                                    min="0"
                                    max="30"
                                    class="mt-0.5 w-16 rounded border border-slate-300 px-2 py-1"
                                />
                            </label>
                            <button
                                type="button"
                                class="rounded bg-teal-600 px-2 py-1 text-xs text-white hover:bg-teal-700"
                                @click="saveMatch(m.id)"
                            >
                                Save
                            </button>
                        </div>
                    </li>
                </ul>
            </div>

            <div class="overflow-hidden rounded border border-slate-200 bg-white shadow-sm">
                <div class="bg-slate-600 px-3 py-2 text-sm font-medium text-white">
                    Championship predictions
                </div>
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-100">
                            <th class="px-3 py-2">Team</th>
                            <th class="px-3 py-2">%</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="p in predictions"
                            :key="p.team_id"
                            class="border-t border-slate-100"
                        >
                            <td class="px-3 py-2">{{ p.name }}</td>
                            <td class="px-3 py-2 font-mono">
                                {{
                                    predictionActive ? `${p.percent}%` : '0%'
                                }}
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p
                    v-if="!predictionActive"
                    class="mt-2 px-3 pb-3 text-xs text-slate-500"
                >
                    Forecasts appear after week 3 is complete.
                </p>
            </div>
        </div>

        <div class="mt-8 flex flex-wrap gap-3">
            <button
                type="button"
                class="rounded bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700 disabled:opacity-40"
                :disabled="nextPlayableWeek === null"
                @click="playAll"
            >
                Play All Weeks
            </button>
            <button
                type="button"
                class="rounded bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700 disabled:opacity-40"
                :disabled="nextPlayableWeek === null"
                @click="playNext"
            >
                Play Next Week
            </button>
            <button
                type="button"
                class="rounded bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700"
                @click="resetLeague"
            >
                Reset Data
            </button>
        </div>

        <p v-if="seasonComplete" class="mt-4 text-sm font-medium text-teal-700">
            Season complete.
        </p>
    </div>
</template>
