<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { storeFixtures } from '@/actions/App/Http/Controllers/LeagueController';

type Team = {
    id: number;
    name: string;
    short_name: string;
    power: number;
};

defineProps<{
    teams: Team[];
}>();

function generateFixtures(): void {
    router.post(storeFixtures.url());
}
</script>

<template>
    <div class="min-h-screen bg-[#f8f9fa] p-8 text-slate-900">
        <Head title="Tournament Teams" />

        <h1 class="mb-8 text-sm font-light tracking-wide text-slate-500">
            Tournament Teams
        </h1>

        <div
            class="mx-auto max-w-lg overflow-hidden rounded border border-slate-200 bg-white shadow-sm"
        >
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="bg-slate-600 text-white">
                        <th class="px-4 py-2 font-medium">Team Name</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="t in teams"
                        :key="t.id"
                        class="border-t border-slate-200"
                    >
                        <td class="px-4 py-2">{{ t.name }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <button
            type="button"
            class="mt-6 rounded bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700"
            @click="generateFixtures"
        >
            Generate Fixtures
        </button>
    </div>
</template>
