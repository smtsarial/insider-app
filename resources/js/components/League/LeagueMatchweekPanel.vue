<script setup lang="ts">
import type { LeagueMatchRow, LeagueWeekBlock } from '@/types/league';

defineProps<{
    currentBlock: LeagueWeekBlock | undefined;
    currentViewWeek: number;
    editScores: Record<number, { home: number; away: number } | undefined>;
    loading: boolean;
    savedMatchId: number | null;
}>();

const emit = defineEmits<{
    'save-match': [matchId: number];
}>();

function scoreLine(m: LeagueMatchRow): string {
    if (m.status === 'finished' && m.home_goals != null && m.away_goals != null) {
        return `${m.home_goals} – ${m.away_goals}`;
    }

    return 'vs';
}
</script>

<template>
    <div
        class="flex h-full flex-col overflow-hidden rounded-2xl border border-zinc-800/80 bg-zinc-900/65 shadow-xl shadow-black/25 ring-1 ring-white/5"
    >
        <div
            class="border-b border-zinc-800/90 bg-linear-to-r from-zinc-800/95 to-zinc-900/90 px-4 py-3.5 text-xs font-semibold uppercase tracking-wider text-zinc-400"
        >
            Matchday {{ currentViewWeek }} · results
        </div>

        <div v-if="(currentBlock?.matches ?? []).length === 0" class="flex flex-1 flex-col items-center justify-center gap-2 px-6 py-16 text-center">
            <p class="text-sm font-medium text-zinc-400">No fixtures for this matchday</p>
            <p class="max-w-xs text-xs leading-relaxed text-zinc-600">Pick another matchday above or continue the season from the table column.</p>
        </div>

        <ul v-else class="min-h-0 flex-1 divide-y divide-zinc-800/80 overflow-y-auto">
            <li v-for="m in currentBlock?.matches ?? []" :key="m.id" class="px-4 py-5">
                <div class="flex flex-col gap-4">
                    <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-3 text-sm">
                        <span class="truncate font-medium text-white">{{ m.home_team.name }}</span>
                        <span
                            class="min-w-[4.5rem] rounded-lg px-3 py-1.5 text-center font-mono text-base font-bold tabular-nums"
                            :class="
                                m.status === 'finished'
                                    ? 'bg-zinc-800 text-white ring-1 ring-zinc-700/80'
                                    : 'border border-dashed border-zinc-600 text-zinc-500'
                            "
                        >
                            {{ scoreLine(m) }}
                        </span>
                        <span class="truncate text-right font-medium text-white">{{ m.away_team.name }}</span>
                    </div>
                    <div class="flex justify-center">
                        <span
                            v-if="m.status === 'finished'"
                            class="rounded-full bg-zinc-800/90 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-zinc-400 ring-1 ring-zinc-700/60"
                        >
                            FT
                            <span v-if="m.is_manually_edited" class="ml-1.5 text-amber-400/90">edited</span>
                        </span>
                        <span
                            v-else
                            class="rounded-full border border-zinc-700/70 px-2.5 py-1 text-[10px] uppercase tracking-wide text-zinc-600"
                        >
                            Scheduled
                        </span>
                    </div>
                    <div
                        v-if="m.status === 'finished' && editScores[m.id]"
                        class="flex flex-wrap items-end gap-3 border-t border-zinc-800/80 pt-4"
                    >
                        <label class="flex flex-col gap-1.5 text-[10px] font-medium uppercase tracking-wide text-zinc-500">
                            Home
                            <input
                                v-model.number="editScores[m.id]!.home"
                                type="number"
                                min="0"
                                max="30"
                                class="w-16 rounded-lg border border-zinc-700 bg-zinc-950 px-2 py-2 text-sm text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/40"
                            />
                        </label>
                        <label class="flex flex-col gap-1.5 text-[10px] font-medium uppercase tracking-wide text-zinc-500">
                            Away
                            <input
                                v-model.number="editScores[m.id]!.away"
                                type="number"
                                min="0"
                                max="30"
                                class="w-16 rounded-lg border border-zinc-700 bg-zinc-950 px-2 py-2 text-sm text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/40"
                            />
                        </label>
                        <button
                            type="button"
                            class="rounded-lg bg-emerald-600 px-4 py-2 text-xs font-semibold text-white transition hover:bg-emerald-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-950 disabled:opacity-50"
                            :disabled="loading"
                            @click="emit('save-match', m.id)"
                        >
                            Apply
                        </button>
                        <Transition
                            enter-active-class="transition-opacity duration-200"
                            leave-active-class="transition-opacity duration-300"
                            enter-from-class="opacity-0"
                            leave-to-class="opacity-0"
                        >
                            <span
                                v-if="savedMatchId === m.id"
                                class="rounded-lg bg-emerald-900/50 px-2.5 py-2 text-[10px] font-semibold text-emerald-300 ring-1 ring-emerald-700/40"
                            >
                                Saved ✓
                            </span>
                        </Transition>
                    </div>
                </div>
            </li>
        </ul>
        <div class="mt-auto border-t border-zinc-800/80 bg-zinc-950/20 p-4">
            <slot name="footer" />
        </div>
    </div>
</template>
