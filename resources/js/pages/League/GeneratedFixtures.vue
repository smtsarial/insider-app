<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { startSimulation } from '@/actions/App/Http/Controllers/LeagueController';

type Team = {
    id: number;
    name: string;
    short_name: string;
    power: number;
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

defineProps<{
    matchesByWeek: WeekBlock[];
}>();

function start(): void {
    router.post(startSimulation.url());
}
</script>

<template>
    <div class="min-h-screen bg-white p-8">
        <Head title="Generated Fixtures" />

        <h1 class="mb-6 text-sm font-light text-slate-500">Generated Fixtures</h1>

        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            <div
                v-for="block in matchesByWeek"
                :key="block.week"
                class="overflow-hidden rounded border border-slate-200 bg-white shadow-sm"
            >
                <div
                    class="bg-slate-600 px-3 py-2 text-sm font-medium text-white"
                >
                    Week {{ block.week }}
                </div>
                <ul class="divide-y divide-slate-200 text-sm">
                    <li
                        v-for="m in block.matches"
                        :key="m.id"
                        class="px-3 py-2"
                    >
                        {{ m.home_team.name }} vs {{ m.away_team.name }}
                    </li>
                </ul>
            </div>
        </div>

        <button
            type="button"
            class="mt-8 rounded bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700"
            @click="start"
        >
            Start Simulation
        </button>
    </div>
</template>
