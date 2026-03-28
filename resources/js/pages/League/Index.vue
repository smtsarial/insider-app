<script setup lang="ts">
import ChampionshipPredictionsPanel from '@/components/League/ChampionshipPredictionsPanel.vue';
import LeagueMatchweekPanel from '@/components/League/LeagueMatchweekPanel.vue';
import LeaguePastTournamentsPanel from '@/components/League/LeaguePastTournamentsPanel.vue';
import LeagueStandingsTable from '@/components/League/LeagueStandingsTable.vue';
import UclCompetitionContext from '@/components/League/UclCompetitionContext.vue';
import { computed, onUnmounted, reactive, ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { fixtures, simulation } from '@/routes/league';
import {
    playAllWeeks,
    playNextWeek,
    reset,
    setViewWeek,
    startSimulation,
    storeFixtures,
    updateMatch,
} from '@/actions/App/Http/Controllers/LeagueController';
import { buildDoubleRoundRobinPreview } from '@/utils/leagueDoubleRoundRobinPreview';
import type {
    LeaguePredictionHistoryGrid,
    LeaguePredictionTrackRecord,
    LeagueStandingRow,
    LeagueTeam,
    LeagueWeekBlock,
} from '@/types/league';

const props = withDefaults(
    defineProps<{
        surface?: 'home' | 'wizard';
        leagueProgressStep?: 'teams' | 'fixtures' | 'simulation';
        step: 'teams' | 'fixtures' | 'simulation';
        teams?: LeagueTeam[];
        matchesByWeek?: LeagueWeekBlock[];
        currentViewWeek?: number;
        lastCompletedMatchweek?: number;
        nextPlayableWeek?: number | null;
        seasonComplete?: boolean;
        predictionActive?: boolean;
        standings?: LeagueStandingRow[];
        predictionHistoryGrid?: LeaguePredictionHistoryGrid;
        predictionTrackRecord?: LeaguePredictionTrackRecord | null;
        tournamentHistory?: Array<{
            id: number;
            archived_at: string;
            was_completed: boolean;
            champion_name: string | null;
            finished_matches: number;
            step_at_archive: string | null;
            table: Array<{ team_name: string; points: number; played: number; gd: number }>;
            standings_detail?: Array<{
                team_id: number;
                team_name: string;
                played: number;
                won: number;
                drawn: number;
                lost: number;
                gf: number;
                ga: number;
                gd: number;
                points: number;
            }>;
            prediction_snapshots?: Array<{
                after_matchweek: number;
                percentages: Record<string, number | string>;
                standings?: Array<{ team_id: number; points: number }>;
            }>;
        }>;
    }>(),
    {
        surface: 'wizard',
        leagueProgressStep: 'teams',
        teams: () => [],
        matchesByWeek: () => [],
        tournamentHistory: () => [],
        currentViewWeek: 1,
        lastCompletedMatchweek: 0,
        nextPlayableWeek: null,
        seasonComplete: false,
        predictionActive: false,
        standings: () => [],
        predictionHistoryGrid: () => ({ after_matchweeks: [], rows: [] }),
        predictionTrackRecord: null,
    },
);

// Global loading state driven by Inertia navigation events
const loading = ref(false);
const savedMatchId = ref<number | null>(null);
const unlistenStart = router.on('start', () => {
    loading.value = true;
});
const unlistenFinish = router.on('finish', () => {
    loading.value = false;
});
onUnmounted(() => {
    unlistenStart();
    unlistenFinish();
});

/** Normalized 1–6; avoids strict-equality misses (e.g. string vs number) from the wire. */
const currentViewWeekNum = computed((): number => {
    const n = Number(props.currentViewWeek);
    if (!Number.isFinite(n)) {
        return 1;
    }

    return Math.min(6, Math.max(1, Math.floor(n)));
});

/** Selected matchweek block only — do not fall back to week 1 or the wrong fixtures show. */
const currentBlock = computed((): LeagueWeekBlock | undefined => {
    const w = currentViewWeekNum.value;

    return props.matchesByWeek.find((b) => Number(b.week) === w);
});

const editScores = reactive<Record<number, { home: number; away: number } | undefined>>({});

watch(
    () => props.matchesByWeek,
    (weeks) => {
        for (const b of weeks) {
            for (const m of b.matches) {
                if (m.status === 'finished' && m.home_goals != null && m.away_goals != null) {
                    editScores[m.id] = { home: m.home_goals, away: m.away_goals };
                }
            }
        }
    },
    { immediate: true, deep: true },
);

const page = usePage();

const teamDraft = ref<LeagueTeam[]>([]);

/** Stable fingerprint so local form resets whenever the server sends new club data (Inertia may reuse array refs). */
const teamsFromServerKey = computed(() =>
    props.teams.map((t) => `${t.id}:${t.name}:${t.short_name}:${t.power}:${t.sort_order}`).join('|'),
);

watch(
    teamsFromServerKey,
    () => {
        teamDraft.value = props.teams.map((t) => ({ ...t }));
    },
    { immediate: true },
);

const clubsStepErrors = computed(() => {
    const raw = page.props.errors as Record<string, string | string[]> | undefined;
    if (!raw || typeof raw !== 'object') {
        return [];
    }
    const lines: string[] = [];
    for (const [key, val] of Object.entries(raw)) {
        if (key === 'teams' || key.startsWith('teams.')) {
            const parts = Array.isArray(val) ? val : [val];
            lines.push(...parts.map(String));
        }
    }
    return lines;
});

/** Live calendar from the form (matches PHP round-robin). */
const localSchedulePreview = computed(() => buildDoubleRoundRobinPreview(teamDraft.value));

/** Bust Vue VDOM reuse when matchup labels change. */
const schedulePreviewKey = computed(() =>
    localSchedulePreview.value.map((b) => b.matches.map((m) => `${m.home_team.name}-${m.away_team.name}`).join(',')).join('|'),
);

function drawScheduleAndSaveFixtures(): void {
    router.post(storeFixtures.url(), {
        teams: teamDraft.value.map((t) => ({
            id: t.id,
            name: t.name.trim(),
            short_name: t.short_name.trim().slice(0, 8).toUpperCase(),
            power: Number(t.power),
            sort_order: Number(t.sort_order),
        })),
    });
}

function weekStatus(w: number): 'done' | 'next' | 'upcoming' {
    if (w <= props.lastCompletedMatchweek) return 'done';
    if (w === props.nextPlayableWeek) return 'next';
    return 'upcoming';
}

function saveMatch(matchId: number): void {
    const s = editScores[matchId];
    if (!s) return;
    savedMatchId.value = matchId;
    router.patch(
        updateMatch.url({ league_match: matchId }),
        { home_goals: s.home, away_goals: s.away },
        {
            onSuccess: () => {
                setTimeout(() => {
                    savedMatchId.value = null;
                }, 2500);
            },
        },
    );
}

// Sorted standings by title_percent descending for the forecast bar
const forecastRows = computed(() =>
    [...props.standings].sort(
        (a, b) => (b.title_percent ?? 0) - (a.title_percent ?? 0),
    ),
);

function confirmResetCompetition(): boolean {
    return window.confirm(
        'Tüm sezon verisini silmek istiyor musun? / Reset all competition data (fixtures, results, predictions)?',
    );
}

function postResetLeague(): void {
    if (!confirmResetCompetition()) {
        return;
    }
    router.post(reset.url(), {}, { preserveScroll: false });
}

const statusStep = computed(() => (props.surface === 'home' ? props.leagueProgressStep : props.step));

const fixturesHref = computed(() => fixtures.url());
const simulationHref = computed(() => simulation.url());
</script>

<template>
    <div
        class="min-h-screen bg-gradient-to-b from-zinc-900 via-zinc-950 to-zinc-950 text-zinc-100 antialiased"
    >
        <Head
            :title="
                surface === 'home'
                    ? 'UEFA Champions League · Home'
                    : step === 'simulation'
                      ? 'UEFA Champions League · League phase'
                      : step === 'fixtures'
                        ? 'Fixture list · UCL league phase'
                        : 'Clubs · UCL league phase'
            "
        />

        <!-- Full-screen loading overlay -->
        <Transition
            enter-active-class="transition-opacity duration-150"
            leave-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            leave-to-class="opacity-0"
        >
            <div
                v-if="loading"
                class="fixed inset-0 z-50 flex items-center justify-center bg-zinc-950/70 backdrop-blur-sm"
            >
                <div class="flex flex-col items-center gap-3">
                    <svg
                        class="h-9 w-9 animate-spin text-emerald-400"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="3"
                        />
                        <path
                            class="opacity-80"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 22 6.477 22 12h-4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                        />
                    </svg>
                    <span class="text-xs font-medium text-zinc-400">Processing…</span>
                </div>
            </div>
        </Transition>

        <!-- Header -->
        <header class="border-b border-zinc-800 bg-zinc-900/80 backdrop-blur-sm">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-5 py-4">
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-indigo-500/40 bg-indigo-600/90 text-[11px] font-bold tracking-tight text-white shadow-md"
                        aria-hidden="true"
                    >
                        UCL
                    </div>
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-widest text-indigo-300/90">
                            UEFA
                        </p>
                        <p class="text-sm font-semibold text-white">Champions League</p>
                        <p class="text-[9px] font-medium uppercase tracking-wider text-zinc-500">League phase · demo</p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center justify-end gap-2">
                    <Link
                        v-if="surface === 'wizard' && step === 'simulation'"
                        :href="fixturesHref"
                        class="text-[11px] font-medium text-zinc-500 underline decoration-zinc-600 underline-offset-2 hover:text-zinc-300"
                    >
                        Fixtures
                    </Link>
                    <Link
                        v-if="surface === 'wizard'"
                        href="/"
                        class="text-[11px] font-medium text-indigo-400/90 underline decoration-indigo-500/50 underline-offset-2 hover:text-indigo-300"
                    >
                        Home
                    </Link>
                    <span
                        class="rounded-full border px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider"
                        :class="{
                            'border-zinc-700 text-zinc-500': statusStep === 'teams',
                            'border-amber-700/60 bg-amber-900/20 text-amber-400/90': statusStep === 'fixtures',
                            'border-emerald-700/60 bg-emerald-900/20 text-emerald-400':
                                statusStep === 'simulation',
                        }"
                    >
                        {{
                            statusStep === 'teams'
                                ? 'Participant clubs'
                                : statusStep === 'fixtures'
                                  ? 'Fixture draw'
                                  : 'League phase'
                        }}
                    </span>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-5 py-8">
            <!-- ─────────────────────── HOME (archives + club setup) ─────────────────────── -->
            <template v-if="surface === 'home'">
                <div
                    v-if="leagueProgressStep === 'fixtures'"
                    class="mb-6 flex flex-col gap-3 rounded-xl border border-amber-800/50 bg-amber-950/25 px-4 py-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <p class="text-sm text-amber-100/90">
                        A fixture calendar is saved. Continue to the draw step to review pairings or start the league
                        phase.
                    </p>
                    <Link
                        :href="fixturesHref"
                        class="inline-flex shrink-0 items-center justify-center rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white shadow-md transition hover:bg-amber-500"
                    >
                        Open fixtures
                    </Link>
                </div>
                <div
                    v-else-if="leagueProgressStep === 'simulation'"
                    class="mb-6 flex flex-col gap-3 rounded-xl border border-emerald-800/50 bg-emerald-950/25 px-4 py-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <p class="text-sm text-emerald-100/90">
                        League phase in progress. Open the matchweek view to play or edit results.
                    </p>
                    <Link
                        :href="simulationHref"
                        class="inline-flex shrink-0 items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-md transition hover:bg-emerald-500"
                    >
                        Open league phase
                    </Link>
                </div>

                <UclCompetitionContext class="mb-8" />

                <h1 class="mb-1 text-xl font-bold text-white">Participant clubs</h1>
                <p class="mb-2 text-sm text-zinc-500">
                    League-phase model · 4 clubs · 6 matchdays · home-and-away round robin
                </p>
                <p class="mb-6 text-sm leading-relaxed text-zinc-400">
                    Defaults load from the database (same four clubs after seed). Adjust names, codes, strength, or
                    <span class="text-zinc-300">draw positions (1–4)</span>, then
                    <span class="font-medium text-zinc-300">draw schedule &amp; save fixtures</span> — that
                    single step updates clubs and creates the tournament calendar.
                </p>

                <div class="mb-6 overflow-hidden rounded-xl border border-zinc-800 bg-zinc-900/50">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-zinc-800 text-[10px] uppercase tracking-wider text-zinc-500">
                                <th class="px-3 py-3 text-left font-medium">Club</th>
                                <th class="px-3 py-3 text-left font-medium">Code</th>
                                <th class="px-3 py-3 text-right font-medium">Power</th>
                                <th class="px-3 py-3 text-right font-medium">Draw #</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="t in teamDraft"
                                :key="t.id"
                                class="border-t border-zinc-800/60 hover:bg-zinc-800/30"
                            >
                                <td class="px-3 py-2">
                                    <input
                                        v-model="t.name"
                                        type="text"
                                        maxlength="255"
                                        class="w-full rounded border border-zinc-700 bg-zinc-950 px-2 py-1.5 text-white placeholder-zinc-600 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                        autocomplete="off"
                                    />
                                </td>
                                <td class="px-3 py-2">
                                    <input
                                        v-model="t.short_name"
                                        type="text"
                                        maxlength="8"
                                        class="w-20 rounded border border-zinc-700 bg-zinc-950 px-2 py-1.5 font-mono text-zinc-200 uppercase focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                        autocomplete="off"
                                    />
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <input
                                        v-model.number="t.power"
                                        type="number"
                                        min="40"
                                        max="99"
                                        class="w-16 rounded border border-zinc-700 bg-zinc-950 px-2 py-1.5 text-right text-emerald-300 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                    />
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <select
                                        v-model.number="t.sort_order"
                                        class="rounded border border-zinc-700 bg-zinc-950 px-2 py-1.5 text-zinc-200 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                    >
                                        <option v-for="n in 4" :key="n" :value="n">{{ n }}</option>
                                    </select>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="clubsStepErrors.length > 0"
                    class="mb-8 rounded-lg border border-red-800/60 bg-red-950/30 px-4 py-3 text-sm text-red-200"
                >
                    <p class="mb-1 font-semibold text-red-300">Could not create fixtures</p>
                    <ul class="list-inside list-disc space-y-0.5 text-red-200/90">
                        <li v-for="(err, i) in clubsStepErrors" :key="i">{{ err }}</li>
                    </ul>
                    <p class="mt-2 text-xs text-red-300/80">
                        Each draw position (1–4) must be used exactly once. Check for duplicate &quot;Draw #&quot;
                        values.
                    </p>
                </div>

                <template v-if="localSchedulePreview.length > 0">
                    <h2 class="mb-3 text-xs font-semibold uppercase tracking-wider text-zinc-500">
                        Provisional calendar — 6 matchdays
                    </h2>
                    <div :key="schedulePreviewKey" class="mb-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <div
                            v-for="block in localSchedulePreview"
                            :key="'pre-' + block.week"
                            class="rounded-xl border border-zinc-800 bg-zinc-900/50"
                        >
                            <div class="border-b border-zinc-800 px-3 py-2 text-xs font-semibold text-zinc-400">
                                Matchweek {{ block.week }}
                            </div>
                            <ul class="divide-y divide-zinc-800/80 text-sm">
                                <li
                                    v-for="m in block.matches"
                                    :key="m.id"
                                    class="flex items-center justify-between px-3 py-2 text-zinc-400"
                                >
                                    <span>{{ m.home_team.name }}</span>
                                    <span class="mx-2 text-[10px] text-zinc-600">vs</span>
                                    <span>{{ m.away_team.name }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </template>

                <button
                    type="button"
                    class="rounded-lg bg-emerald-600 px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-950/50 transition hover:bg-emerald-500 disabled:opacity-40"
                    :disabled="loading || teamDraft.length !== 4"
                    @click="drawScheduleAndSaveFixtures"
                >
                    Draw schedule &amp; save fixtures
                </button>

                <LeaguePastTournamentsPanel class="mt-12" :tournaments="tournamentHistory" />
            </template>

            <!-- ─────────────────────── WIZARD (fixtures · simulation) ─────────────────────── -->
            <template v-else>
                <UclCompetitionContext class="mb-8" />

            <!-- ─────────────────────── FIXTURES STEP ─────────────────────── -->
            <template v-if="step === 'fixtures'">
                <h1 class="mb-1 text-xl font-bold text-white">Fixture draw</h1>
                <p class="mb-6 text-sm text-zinc-500">12 fixtures · 6 matchdays (double round-robin)</p>

                <div class="mb-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="block in matchesByWeek"
                        :key="block.week"
                        class="overflow-hidden rounded-xl border border-zinc-800 bg-zinc-900/50"
                    >
                        <div class="border-b border-zinc-800 px-3 py-2 text-xs font-semibold text-zinc-400">
                            Matchweek {{ block.week }}
                        </div>
                        <ul class="divide-y divide-zinc-800/80 text-sm">
                            <li
                                v-for="m in block.matches"
                                :key="m.id"
                                class="flex items-center justify-between px-3 py-2 text-zinc-300"
                            >
                                <span>{{ m.home_team.name }}</span>
                                <span class="text-[10px] text-zinc-600">vs</span>
                                <span>{{ m.away_team.name }}</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button
                        type="button"
                        class="rounded-lg border border-zinc-600 bg-zinc-800/80 px-6 py-2.5 text-sm font-semibold text-zinc-200 transition hover:bg-zinc-700 disabled:opacity-40"
                        :disabled="loading"
                        title="Random reshuffle: same 12 double round-robin constraints, new pairing draw for the four clubs"
                        @click="router.post(storeFixtures.url())"
                    >
                        Redraw schedule
                    </button>
                    <button
                        type="button"
                        class="rounded-lg bg-emerald-600 px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-950/50 transition hover:bg-emerald-500 disabled:opacity-40"
                        :disabled="loading"
                        @click="router.post(startSimulation.url())"
                    >
                        Start league phase
                    </button>
                </div>
            </template>

            <!-- ─────────────────────── SIMULATION STEP (brief layout: table | results | predictions) ─────────────────────── -->
            <template v-else>
                <div class="mb-6">
                    <h1 class="text-xl font-bold tracking-tight text-white sm:text-2xl">
                        UEFA Champions League — league phase
                    </h1>
                    <p class="mt-1 text-sm text-zinc-500">
                        European points system (3-1-0) &amp; UEFA-style ranking · 4 clubs · title estimates from
                        matchday 4.
                    </p>
                    <p class="mt-1 text-sm text-zinc-400">
                        Results column: matchday {{ currentViewWeekNum }} of 6.
                    </p>
                </div>

                <!-- Week navigation strip -->
                <div class="mb-6 flex flex-wrap gap-2">
                    <button
                        v-for="w in 6"
                        :key="w"
                        type="button"
                        class="relative flex items-center gap-1.5 rounded-lg px-3.5 py-2 text-sm font-medium transition-all"
                        :class="{
                            'bg-emerald-600 text-white shadow-md shadow-emerald-950/50 ring-1 ring-emerald-400/30':
                                w === currentViewWeekNum,
                            'bg-zinc-800/80 text-zinc-400 ring-1 ring-zinc-700 hover:bg-zinc-800 hover:text-zinc-200':
                                w !== currentViewWeekNum,
                        }"
                        @click="router.post(setViewWeek.url(), { week: w })"
                    >
                        <svg
                            v-if="weekStatus(w) === 'done'"
                            class="h-3 w-3 shrink-0 text-emerald-400"
                            viewBox="0 0 12 12"
                            fill="none"
                        >
                            <path
                                d="M2 6l3 3 5-5"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                        <span
                            v-else-if="weekStatus(w) === 'next'"
                            class="h-1.5 w-1.5 shrink-0 rounded-full bg-amber-400"
                        />
                        <span>MW {{ w }}</span>
                    </button>
                </div>

                <!-- Three-column dashboard (Figure 1.a / 2.a) -->
                <div class="grid min-h-[32rem] grid-cols-1 gap-5 xl:grid-cols-12 xl:items-stretch">
                    <div class="flex min-h-0 flex-col xl:col-span-4">
                        <LeagueStandingsTable
                            :standings="standings"
                            :prediction-active="predictionActive"
                            :last-completed-matchweek="lastCompletedMatchweek"
                        >
                            <template #footer>
                                <button
                                    type="button"
                                    class="w-full rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-emerald-950/50 transition hover:bg-emerald-500 disabled:cursor-not-allowed disabled:opacity-40"
                                    :disabled="nextPlayableWeek === null || loading"
                                    @click="router.post(playAllWeeks.url())"
                                >
                                    Play all
                                </button>
                            </template>
                        </LeagueStandingsTable>
                    </div>
                    <div class="flex min-h-0 flex-col xl:col-span-4">
                        <LeagueMatchweekPanel
                            :current-block="currentBlock"
                            :current-view-week="currentViewWeekNum"
                            :edit-scores="editScores"
                            :loading="loading"
                            :saved-match-id="savedMatchId"
                            @save-match="saveMatch"
                        >
                            <template #footer>
                                <button
                                    type="button"
                                    class="w-full rounded-lg bg-zinc-800 px-4 py-2.5 text-sm font-semibold text-zinc-200 ring-1 ring-zinc-700 transition hover:bg-zinc-700 disabled:cursor-not-allowed disabled:opacity-40"
                                    :disabled="nextPlayableWeek === null || loading"
                                    @click="router.post(playNextWeek.url())"
                                >
                                    Next week
                                </button>
                            </template>
                        </LeagueMatchweekPanel>
                    </div>
                    <div class="flex min-h-0 flex-col xl:col-span-4">
                        <ChampionshipPredictionsPanel
                            :forecast-rows="forecastRows"
                            :prediction-active="predictionActive"
                            :season-complete="seasonComplete"
                        />
                    </div>
                </div>

                <!-- Season complete — champion banner -->
                <div
                    v-if="seasonComplete"
                    class="mt-5 overflow-hidden rounded-xl border border-amber-700/50 bg-amber-950/30"
                >
                    <div class="border-b border-amber-800/40 bg-amber-950/40 px-4 py-3">
                        <p class="text-sm font-bold text-amber-300">League phase complete</p>
                        <p class="mt-0.5 text-[11px] text-amber-700/80">Final table is locked (knockout rounds not simulated).</p>
                    </div>
                    <div class="space-y-2 p-4">
                        <div
                            v-for="(row, i) in forecastRows"
                            :key="row.team.id"
                            class="flex items-center gap-3"
                        >
                            <span
                                class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-bold"
                                :class="i === 0 ? 'bg-amber-500 text-zinc-900' : 'bg-zinc-800 text-zinc-400'"
                            >
                                {{ i + 1 }}
                            </span>
                            <span class="flex-1 text-sm font-medium" :class="i === 0 ? 'text-amber-200' : 'text-zinc-300'">
                                {{ row.team.name }}
                                <span v-if="i === 0" class="ml-1.5 text-[10px] font-bold uppercase tracking-wider text-amber-400">Table winner</span>
                            </span>
                            <span class="font-mono text-sm font-semibold tabular-nums" :class="i === 0 ? 'text-amber-300' : 'text-zinc-400'">
                                {{ row.points }} pts
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Global actions (play controls live under table / match columns per brief) -->
                <div class="mt-6 flex flex-wrap gap-3">
                    <button
                        v-if="seasonComplete"
                        type="button"
                        class="rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-emerald-950/50 transition hover:bg-emerald-500 disabled:cursor-not-allowed disabled:opacity-40"
                        :disabled="loading"
                        @click="postResetLeague"
                    >
                        Yeni turnuva / New tournament
                    </button>
                    <button
                        type="button"
                        class="rounded-lg border border-red-900/50 bg-red-950/30 px-4 py-2.5 text-sm font-semibold text-red-300 transition hover:bg-red-950/60"
                        :disabled="loading"
                        @click="postResetLeague"
                    >
                        Reset
                    </button>
                </div>

                <p v-if="seasonComplete" class="mt-4 text-sm font-medium text-emerald-400/90">
                    League phase finished — start another campaign with “New tournament” or Reset to clear data and
                    draw a new schedule.
                </p>

                <!-- Season chronicle (history matrix) -->
                <div
                    v-if="predictionHistoryGrid.after_matchweeks.length > 0"
                    class="mt-10 overflow-hidden rounded-xl border border-zinc-800 bg-zinc-900/40"
                >
                    <div
                        class="border-b border-zinc-800 bg-zinc-900/80 px-4 py-3 text-xs font-semibold uppercase tracking-wider text-zinc-400"
                    >
                        League phase timeline — points &amp; title probability by matchday
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[36rem] text-xs">
                            <thead>
                                <tr class="border-b border-zinc-800 text-[10px] uppercase tracking-wider">
                                    <th class="sticky left-0 z-10 bg-zinc-900 py-2 pl-4 pr-3 text-left text-zinc-500">Club</th>
                                    <template
                                        v-for="mw in predictionHistoryGrid.after_matchweeks"
                                        :key="'h-' + mw"
                                    >
                                        <th class="border-l border-zinc-800/70 px-2 py-2 text-center text-zinc-600" colspan="2">
                                            After MW {{ mw }}
                                        </th>
                                    </template>
                                </tr>
                                <tr class="border-b border-zinc-800/60">
                                    <th class="sticky left-0 z-10 bg-zinc-900/80 py-1 pl-4 pr-3" />
                                    <template
                                        v-for="mw in predictionHistoryGrid.after_matchweeks"
                                        :key="'s-' + mw"
                                    >
                                        <th class="border-l border-zinc-800/50 px-2 py-1 text-right font-normal text-zinc-600">Pts</th>
                                        <th class="px-2 py-1 text-right font-normal text-emerald-700/80">Title</th>
                                    </template>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="hr in predictionHistoryGrid.rows"
                                    :key="'r-' + hr.team.id"
                                    class="border-t border-zinc-800/40 hover:bg-zinc-800/20"
                                >
                                    <td class="sticky left-0 z-10 bg-zinc-900/95 py-2.5 pl-4 pr-3 font-medium text-zinc-200">
                                        {{ hr.team.name }}
                                    </td>
                                    <template
                                        v-for="(mw, wi) in predictionHistoryGrid.after_matchweeks"
                                        :key="hr.team.id + '-' + mw"
                                    >
                                        <td class="border-l border-zinc-800/40 px-2 py-2.5 text-right tabular-nums text-zinc-400">
                                            {{ hr.points[wi] != null ? hr.points[wi] : '—' }}
                                        </td>
                                        <td class="px-2 py-2.5 text-right font-mono tabular-nums text-emerald-500/80">
                                            {{ hr.percentages[wi] != null ? `${hr.percentages[wi]}%` : '—' }}
                                        </td>
                                    </template>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Model calibration (end of season) -->
                <div
                    v-if="predictionTrackRecord && predictionTrackRecord.favorite_total > 0"
                    class="mt-4 rounded-xl border border-zinc-800 bg-zinc-900/40 p-4"
                >
                    <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500">
                        Prediction accuracy
                    </p>
                    <p class="mt-2 text-sm leading-relaxed text-zinc-300">
                        Across
                        <span class="font-semibold text-white">{{ predictionTrackRecord.favorite_total }}</span>
                        weekly forecasts, the highest-rated team was the eventual champion
                        <span class="font-semibold text-white">{{ predictionTrackRecord.favorite_hits }}</span>
                        time(s) —
                        <span class="font-semibold text-emerald-400">{{ predictionTrackRecord.rate_percent }}%</span>
                        hit rate.
                    </p>
                </div>
            </template>

                <LeaguePastTournamentsPanel class="mt-10" :tournaments="tournamentHistory" />
            </template>
        </main>
    </div>
</template>
