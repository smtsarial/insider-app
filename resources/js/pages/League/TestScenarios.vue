<script setup lang="ts">
import { Head, Link, useHttp } from '@inertiajs/vue3';
import { ref } from 'vue';
import { home } from '@/routes';
import { run as runLeagueUnitTests } from '@/routes/league/test-scenarios';

type StandingsRow = {
    team: string;
    played: number;
    won: number;
    drawn: number;
    lost: number;
    gf: number;
    ga: number;
    gd: number;
    points: number;
};

type RunTestsResponse = {
    success: boolean;
    exit_code: number;
    output: string;
    command: { binary: string; args: string[] };
};

withDefaults(
    defineProps<{
        canRunUnitTests?: boolean;
    matchSimulation: {
        deterministic: {
            test: string;
            seed: number;
            home: { id: number; power: number };
            away: { id: number; power: number };
            score: { home: number; away: number };
        };
        home_favourite_monte_carlo: {
            test: string;
            iterations: number;
            seeds: string;
            home: { id: number; power: number };
            away: { id: number; power: number };
            home_wins: number;
            draws: number;
            away_wins: number;
            assertions: string;
        };
        strong_away_monte_carlo: {
            test: string;
            iterations: number;
            seeds: string;
            home: { id: number; power: number };
            away: { id: number; power: number };
            away_wins: number;
            assertions: string;
        };
    };
    standings: {
        head_to_head_then_squad: {
            test: string;
            matches: Array<{ home: string; away: string; score: string }>;
            note: string;
            table: StandingsRow[];
            expect_top_team_id: number;
        };
        points_then_gd: {
            test: string;
            matches: Array<{ home: string; away: string; score: string }>;
            table: StandingsRow[];
            expect_top_team_id: number;
        };
    };
    fixtures: {
        twelve_fixtures: {
            test: string;
            fixture_count: number;
            matchweeks: number[];
        };
        shuffle_week_one: {
            test: string;
            week_one_ordered_pairings: string[];
            week_one_shuffled_pairings_seed_12345: string[];
            shuffled_differs_from_ordered: boolean;
        };
        ordered_preview: Array<{
            matchweek: number;
            home_team_id: number;
            away_team_id: number;
        }>;
    };
    titlePrediction: {
        upsert_same_matchweek: {
            test: string;
            final_snapshots: Array<{
                after_matchweek: number;
                percentages: Record<string, number>;
                standings: Array<Record<string, number>>;
            }>;
            expect: string;
        };
        favorite_vs_champion: {
            test: string;
            input_snapshots: Array<{ after_matchweek: number; percentages: Record<string, number> }>;
            champion_team_ids: number[];
            result: {
                favorite_hits: number;
                favorite_total: number;
                rate_percent: number | null;
                champion_team_ids: number[];
            } | null;
            expect: string;
        };
        first_place_team_ids: {
            test: string;
            match: string;
            first_place_team_ids: number[];
            expect: string;
        };
    };
    }>(),
    {
        canRunUnitTests: false,
    },
);

const http = useHttp();
const runningTests = ref(false);
const runError = ref<string | null>(null);
const lastRun = ref<RunTestsResponse | null>(null);

async function runUnitTests(): Promise<void> {
    runError.value = null;
    lastRun.value = null;
    runningTests.value = true;

    try {
        const data = (await http.submit(runLeagueUnitTests.post())) as RunTestsResponse;
        lastRun.value = data;
    } catch (e) {
        runError.value = e instanceof Error ? e.message : 'Request failed';
    } finally {
        runningTests.value = false;
    }
}
</script>

<template>
    <div
        class="min-h-screen bg-linear-to-b from-zinc-900 via-zinc-950 to-black text-zinc-100 antialiased selection:bg-violet-500/30 selection:text-violet-50"
    >
        <Head title="Unit test scenarios (live)" />

        <header
            class="sticky top-0 z-40 border-b border-zinc-800/90 bg-zinc-950/85 backdrop-blur-md supports-backdrop-filter:bg-zinc-950/75"
        >
            <div class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-4 py-3 sm:px-5">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-violet-300/90">Dev</p>
                    <h1 class="text-base font-semibold text-white sm:text-lg">League unit tests · live data</h1>
                    <p class="mt-0.5 text-xs text-zinc-500">
                        Same in-memory setups as
                        <code class="rounded bg-zinc-800/80 px-1 py-0.5 text-[10px] text-zinc-300">tests/Unit/*</code>
                    </p>
                </div>
                <Link
                    :href="home.url()"
                    class="shrink-0 rounded-lg px-3 py-1.5 text-xs font-medium text-zinc-400 transition hover:bg-zinc-800/80 hover:text-zinc-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-500/70 focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-950"
                >
                    Home
                </Link>
            </div>
        </header>

        <main class="mx-auto max-w-5xl space-y-12 px-4 pb-20 pt-8 sm:px-5">
            <section
                v-if="canRunUnitTests"
                class="rounded-2xl border border-violet-500/25 bg-violet-950/20 p-5 shadow-lg ring-1 ring-violet-500/10 sm:p-6"
            >
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 class="text-sm font-bold uppercase tracking-wider text-violet-300/90">Run Pest on this machine</h2>
                        <p class="mt-1 text-xs text-zinc-500">
                            Executes
                            <code class="rounded bg-zinc-900/80 px-1 py-0.5 font-mono text-[10px] text-zinc-300">php artisan test</code>
                            for the three league unit files (subprocess). Local and testing environments only.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="inline-flex shrink-0 items-center justify-center rounded-xl bg-violet-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md transition hover:bg-violet-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-400 focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-950 disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="runningTests"
                        @click="runUnitTests"
                    >
                        {{ runningTests ? 'Running…' : 'Run unit tests' }}
                    </button>
                </div>
                <p v-if="runError" class="mt-3 text-sm text-red-400">{{ runError }}</p>
                <div v-if="lastRun" class="mt-4 space-y-2">
                    <p
                        class="text-sm font-semibold"
                        :class="lastRun.success ? 'text-emerald-400' : 'text-amber-400'"
                    >
                        {{ lastRun.success ? 'All passed' : 'Finished with failures' }}
                        <span class="ml-2 font-mono text-xs font-normal text-zinc-500">exit {{ lastRun.exit_code }}</span>
                    </p>
                    <pre
                        class="max-h-96 overflow-auto rounded-lg border border-zinc-800 bg-zinc-950 p-3 text-[11px] leading-relaxed text-zinc-300 whitespace-pre-wrap"
                    >{{ lastRun.output }}</pre>
                    <p class="text-[10px] text-zinc-600">
                        <span class="font-mono">{{ lastRun.command.binary }}</span>
                        <span class="ml-1 font-mono text-zinc-500">{{ lastRun.command.args.join(' ') }}</span>
                    </p>
                </div>
            </section>
            <p
                v-else
                class="rounded-xl border border-zinc-800/80 bg-zinc-900/30 px-4 py-3 text-xs text-zinc-500"
            >
                The in-browser test runner is disabled in this environment (
                <code class="rounded bg-zinc-900 px-1 font-mono text-zinc-400">local</code>
                or
                <code class="rounded bg-zinc-900 px-1 font-mono text-zinc-400">testing</code>
                only).
            </p>

            <!-- MatchSimulationEngineTest -->
            <section class="rounded-2xl border border-zinc-800/80 bg-zinc-900/40 p-5 shadow-lg ring-1 ring-white/5 sm:p-6">
                <h2 class="text-sm font-bold uppercase tracking-wider text-emerald-400/90">
                    MatchSimulationEngineTest
                </h2>

                <div class="mt-6 space-y-6">
                    <div>
                        <p class="text-xs font-medium text-zinc-400">{{ matchSimulation.deterministic.test }}</p>
                        <dl class="mt-2 grid gap-2 text-sm sm:grid-cols-2">
                            <div class="rounded-lg border border-zinc-800/60 bg-zinc-950/50 px-3 py-2">
                                <dt class="text-[10px] uppercase text-zinc-500">Seed</dt>
                                <dd class="font-mono text-zinc-200">{{ matchSimulation.deterministic.seed }}</dd>
                            </div>
                            <div class="rounded-lg border border-zinc-800/60 bg-zinc-950/50 px-3 py-2">
                                <dt class="text-[10px] uppercase text-zinc-500">Score</dt>
                                <dd class="font-mono text-zinc-200">
                                    {{ matchSimulation.deterministic.score.home }} –
                                    {{ matchSimulation.deterministic.score.away }}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <div>
                        <p class="text-xs font-medium text-zinc-400">
                            {{ matchSimulation.home_favourite_monte_carlo.test }}
                        </p>
                        <p class="mt-1 text-xs text-zinc-500">
                            {{ matchSimulation.home_favourite_monte_carlo.iterations }} runs · seeds
                            {{ matchSimulation.home_favourite_monte_carlo.seeds }}
                        </p>
                        <div class="mt-3 grid grid-cols-3 gap-2 text-center text-sm">
                            <div class="rounded-lg border border-zinc-800 bg-zinc-950/60 py-3">
                                <p class="text-[10px] uppercase text-zinc-500">Home wins</p>
                                <p class="mt-1 text-lg font-semibold text-emerald-300">
                                    {{ matchSimulation.home_favourite_monte_carlo.home_wins }}
                                </p>
                            </div>
                            <div class="rounded-lg border border-zinc-800 bg-zinc-950/60 py-3">
                                <p class="text-[10px] uppercase text-zinc-500">Draws</p>
                                <p class="mt-1 text-lg font-semibold text-amber-300">
                                    {{ matchSimulation.home_favourite_monte_carlo.draws }}
                                </p>
                            </div>
                            <div class="rounded-lg border border-zinc-800 bg-zinc-950/60 py-3">
                                <p class="text-[10px] uppercase text-zinc-500">Away wins</p>
                                <p class="mt-1 text-lg font-semibold text-violet-300">
                                    {{ matchSimulation.home_favourite_monte_carlo.away_wins }}
                                </p>
                            </div>
                        </div>
                        <p class="mt-2 text-[11px] text-zinc-500">
                            Assertions:
                            <span class="font-mono text-zinc-400">{{
                                matchSimulation.home_favourite_monte_carlo.assertions
                            }}</span>
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-medium text-zinc-400">
                            {{ matchSimulation.strong_away_monte_carlo.test }}
                        </p>
                        <p class="mt-1 text-xs text-zinc-500">
                            {{ matchSimulation.strong_away_monte_carlo.iterations }} runs · seeds
                            {{ matchSimulation.strong_away_monte_carlo.seeds }}
                        </p>
                        <p class="mt-3 text-2xl font-semibold text-sky-300">
                            {{ matchSimulation.strong_away_monte_carlo.away_wins }}
                            <span class="text-sm font-normal text-zinc-500">away wins</span>
                        </p>
                        <p class="mt-2 text-[11px] text-zinc-500">
                            Assertions:
                            <span class="font-mono text-zinc-400">{{
                                matchSimulation.strong_away_monte_carlo.assertions
                            }}</span>
                        </p>
                    </div>
                </div>
            </section>

            <!-- PremierLeagueStandingsCalculatorTest -->
            <section class="rounded-2xl border border-zinc-800/80 bg-zinc-900/40 p-5 shadow-lg ring-1 ring-white/5 sm:p-6">
                <h2 class="text-sm font-bold uppercase tracking-wider text-sky-400/90">
                    PremierLeagueStandingsCalculatorTest
                </h2>

                <div class="mt-6 space-y-8">
                    <div>
                        <p class="text-xs font-medium text-zinc-400">{{ standings.head_to_head_then_squad.test }}</p>
                        <ul class="mt-2 list-inside list-disc text-xs text-zinc-500">
                            <li v-for="(m, i) in standings.head_to_head_then_squad.matches" :key="i">
                                {{ m.home }} vs {{ m.away }} · {{ m.score }}
                            </li>
                        </ul>
                        <p class="mt-2 text-xs text-zinc-400">{{ standings.head_to_head_then_squad.note }}</p>
                        <div class="mt-3 overflow-x-auto rounded-lg border border-zinc-800">
                            <table class="w-full min-w-[480px] text-left text-sm">
                                <thead class="border-b border-zinc-800 bg-zinc-950/80 text-[10px] uppercase text-zinc-500">
                                    <tr>
                                        <th class="px-3 py-2 font-medium">Team</th>
                                        <th class="px-2 py-2 font-medium">P</th>
                                        <th class="px-2 py-2 font-medium">W</th>
                                        <th class="px-2 py-2 font-medium">D</th>
                                        <th class="px-2 py-2 font-medium">L</th>
                                        <th class="px-2 py-2 font-medium">GF</th>
                                        <th class="px-2 py-2 font-medium">GA</th>
                                        <th class="px-2 py-2 font-medium">GD</th>
                                        <th class="px-2 py-2 font-medium">Pts</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="(row, i) in standings.head_to_head_then_squad.table"
                                        :key="i"
                                        class="border-b border-zinc-800/80 last:border-0"
                                    >
                                        <td class="px-3 py-2 font-medium text-zinc-200">{{ row.team }}</td>
                                        <td class="px-2 py-2 text-zinc-400">{{ row.played }}</td>
                                        <td class="px-2 py-2 text-zinc-400">{{ row.won }}</td>
                                        <td class="px-2 py-2 text-zinc-400">{{ row.drawn }}</td>
                                        <td class="px-2 py-2 text-zinc-400">{{ row.lost }}</td>
                                        <td class="px-2 py-2 text-zinc-400">{{ row.gf }}</td>
                                        <td class="px-2 py-2 text-zinc-400">{{ row.ga }}</td>
                                        <td class="px-2 py-2 text-zinc-400">{{ row.gd }}</td>
                                        <td class="px-2 py-2 font-semibold text-white">{{ row.points }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p class="mt-2 text-xs text-zinc-500">
                            Expected leader team id:
                            <span class="font-mono text-zinc-300">{{
                                standings.head_to_head_then_squad.expect_top_team_id
                            }}</span>
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-medium text-zinc-400">{{ standings.points_then_gd.test }}</p>
                        <ul class="mt-2 list-inside list-disc text-xs text-zinc-500">
                            <li v-for="(m, i) in standings.points_then_gd.matches" :key="i">
                                {{ m.home }} vs {{ m.away }} · {{ m.score }}
                            </li>
                        </ul>
                        <div class="mt-3 overflow-x-auto rounded-lg border border-zinc-800">
                            <table class="w-full min-w-[480px] text-left text-sm">
                                <thead class="border-b border-zinc-800 bg-zinc-950/80 text-[10px] uppercase text-zinc-500">
                                    <tr>
                                        <th class="px-3 py-2 font-medium">Team</th>
                                        <th class="px-2 py-2 font-medium">Pts</th>
                                        <th class="px-2 py-2 font-medium">GD</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="(row, i) in standings.points_then_gd.table"
                                        :key="i"
                                        class="border-b border-zinc-800/80 last:border-0"
                                    >
                                        <td class="px-3 py-2 font-medium text-zinc-200">{{ row.team }}</td>
                                        <td class="px-2 py-2 font-semibold text-white">{{ row.points }}</td>
                                        <td class="px-2 py-2 text-zinc-400">{{ row.gd }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>

            <!-- RoundRobinFixtureGenerator -->
            <section class="rounded-2xl border border-zinc-800/80 bg-zinc-900/40 p-5 shadow-lg ring-1 ring-white/5 sm:p-6">
                <h2 class="text-sm font-bold uppercase tracking-wider text-amber-400/90">
                    RoundRobinFixtureGenerator (standings test file)
                </h2>

                <div class="mt-6 space-y-6">
                    <div>
                        <p class="text-xs font-medium text-zinc-400">{{ fixtures.twelve_fixtures.test }}</p>
                        <p class="mt-2 text-sm text-zinc-300">
                            {{ fixtures.twelve_fixtures.fixture_count }} fixtures · matchweeks
                            {{ fixtures.twelve_fixtures.matchweeks.join(', ') }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-400">{{ fixtures.shuffle_week_one.test }}</p>
                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            <div class="rounded-lg border border-zinc-800 bg-zinc-950/50 p-3">
                                <p class="text-[10px] font-semibold uppercase text-zinc-500">Week 1 · ordered</p>
                                <p class="mt-2 font-mono text-sm text-zinc-200">
                                    {{ fixtures.shuffle_week_one.week_one_ordered_pairings.join(' · ') }}
                                </p>
                            </div>
                            <div class="rounded-lg border border-zinc-800 bg-zinc-950/50 p-3">
                                <p class="text-[10px] font-semibold uppercase text-zinc-500">
                                    Week 1 · shuffled (seed 12345)
                                </p>
                                <p class="mt-2 font-mono text-sm text-zinc-200">
                                    {{ fixtures.shuffle_week_one.week_one_shuffled_pairings_seed_12345.join(' · ') }}
                                </p>
                            </div>
                        </div>
                        <p class="mt-2 text-xs" :class="fixtures.shuffle_week_one.shuffled_differs_from_ordered ? 'text-emerald-400/90' : 'text-red-400'">
                            {{
                                fixtures.shuffle_week_one.shuffled_differs_from_ordered
                                    ? 'Shuffled week 1 ≠ ordered (expected).'
                                    : 'Shuffled equals ordered (unexpected).'
                            }}
                        </p>
                    </div>
                    <div>
                        <p class="text-[10px] font-semibold uppercase text-zinc-500">First rows · ordered schedule</p>
                        <ul class="mt-2 space-y-1 font-mono text-xs text-zinc-400">
                            <li v-for="(r, i) in fixtures.ordered_preview" :key="i">
                                MW{{ r.matchweek }} · {{ r.home_team_id }} vs {{ r.away_team_id }}
                            </li>
                        </ul>
                    </div>
                </div>
            </section>

            <!-- TitlePredictionSnapshotServiceTest -->
            <section class="rounded-2xl border border-zinc-800/80 bg-zinc-900/40 p-5 shadow-lg ring-1 ring-white/5 sm:p-6">
                <h2 class="text-sm font-bold uppercase tracking-wider text-fuchsia-400/90">
                    TitlePredictionSnapshotServiceTest
                </h2>

                <div class="mt-6 space-y-8">
                    <div>
                        <p class="text-xs font-medium text-zinc-400">{{ titlePrediction.upsert_same_matchweek.test }}</p>
                        <pre class="mt-3 max-h-48 overflow-auto rounded-lg border border-zinc-800 bg-zinc-950 p-3 text-[11px] leading-relaxed text-zinc-300">{{
                            JSON.stringify(titlePrediction.upsert_same_matchweek.final_snapshots, null, 2)
                        }}</pre>
                        <p class="mt-2 text-xs text-zinc-500">{{ titlePrediction.upsert_same_matchweek.expect }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-400">{{ titlePrediction.favorite_vs_champion.test }}</p>
                        <p class="mt-2 text-xs text-zinc-500">
                            Champion ids:
                            <span class="font-mono text-zinc-300">{{
                                titlePrediction.favorite_vs_champion.champion_team_ids.join(', ')
                            }}</span>
                            · only matchweeks 0–5 count toward the track record.
                        </p>
                        <pre class="mt-2 max-h-36 overflow-auto rounded-lg border border-zinc-800 bg-zinc-950 p-3 text-[11px] text-zinc-300">{{
                            JSON.stringify(titlePrediction.favorite_vs_champion.result, null, 2)
                        }}</pre>
                        <p class="mt-2 text-xs text-zinc-500">{{ titlePrediction.favorite_vs_champion.expect }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-400">{{ titlePrediction.first_place_team_ids.test }}</p>
                        <p class="mt-2 text-sm text-zinc-300">{{ titlePrediction.first_place_team_ids.match }}</p>
                        <p class="mt-2 font-mono text-lg text-fuchsia-300">
                            {{ titlePrediction.first_place_team_ids.first_place_team_ids.join(', ') }}
                        </p>
                        <p class="mt-1 text-xs text-zinc-500">Expected {{ titlePrediction.first_place_team_ids.expect }}</p>
                    </div>
                </div>
            </section>
        </main>
    </div>
</template>
