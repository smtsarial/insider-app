<script setup lang="ts">
import type { LeagueStandingRow } from '@/types/league';

const props = defineProps<{
    forecastRows: LeagueStandingRow[];
    predictionActive: boolean;
    seasonComplete: boolean;
}>();
</script>

<template>
    <div class="flex h-full flex-col overflow-hidden rounded-xl border border-zinc-300/20 bg-zinc-900/60 shadow-lg shadow-black/20 ring-1 ring-white/5">
        <div
            class="border-b border-zinc-800 bg-gradient-to-r from-zinc-800/90 to-zinc-900/90 px-4 py-3 text-xs font-semibold uppercase tracking-wider text-zinc-400"
        >
            Qualification outlook
        </div>
        <div class="flex flex-1 flex-col p-4">
            <template v-if="!predictionActive && !seasonComplete">
                <p class="text-sm leading-relaxed text-zinc-500">
                    After the first three matchdays are played, estimated league-title probabilities appear here
                    (Monte Carlo over remaining fixtures; ranking rules match the main table).
                </p>
            </template>
            <template v-else>
                <p v-if="!seasonComplete" class="mb-4 text-[11px] leading-relaxed text-zinc-500">
                    Estimates update after each result. Mathematical title clinch shows as 100% (clinched).
                </p>
                <p v-else class="mb-4 text-[11px] font-medium text-amber-200/80">Final forecast vs outcome</p>
                <div class="space-y-3">
                    <div
                        v-for="row in forecastRows"
                        :key="row.team.id"
                        class="flex items-center gap-2 sm:gap-3"
                    >
                        <span class="w-28 shrink-0 truncate text-sm font-medium text-zinc-200 sm:w-32">
                            {{ row.team.name }}
                        </span>
                        <div class="relative h-2.5 min-w-0 flex-1 overflow-hidden rounded-full bg-zinc-800">
                            <div
                                class="h-full rounded-full transition-all duration-700"
                                :class="row.title_percent === 100 ? 'bg-amber-400' : 'bg-emerald-500'"
                                :style="{ width: `${Math.min(100, row.title_percent ?? 0)}%` }"
                            />
                        </div>
                        <span
                            class="w-24 shrink-0 text-right font-mono text-sm tabular-nums sm:w-28"
                            :class="row.title_percent === 100 ? 'font-bold text-amber-300' : 'text-emerald-400/90'"
                        >
                            <template v-if="row.title_percent === 100">Clinched ✓</template>
                            <template v-else>{{ row.title_percent != null ? `${row.title_percent}%` : '—' }}</template>
                        </span>
                        <span class="hidden w-10 shrink-0 text-right font-mono text-xs text-zinc-500 sm:inline">
                            {{ row.points }} pts
                        </span>
                    </div>
                </div>
            </template>
        </div>
    </div>
</template>
