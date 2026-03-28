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
    <div class="flex h-full flex-col overflow-hidden rounded-xl border border-zinc-300/20 bg-zinc-900/60 shadow-lg shadow-black/20 ring-1 ring-white/5">
        <div
            class="border-b border-zinc-800 bg-gradient-to-r from-zinc-800/90 to-zinc-900/90 px-4 py-3 text-xs font-semibold uppercase tracking-wider text-zinc-400"
        >
            Matchday {{ currentViewWeek }} · results
        </div>
        <ul class="min-h-0 flex-1 divide-y divide-zinc-800/80 overflow-y-auto">
            <li v-for="m in currentBlock?.matches ?? []" :key="m.id" class="px-4 py-4">
                <div class="flex flex-col gap-3">
                    <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-2 text-sm">
                        <span class="truncate font-medium text-white">{{ m.home_team.name }}</span>
                        <span
                            class="rounded-md px-3 py-1 text-center font-mono font-bold tabular-nums"
                            :class="
                                m.status === 'finished'
                                    ? 'bg-zinc-800 text-white'
                                    : 'border border-zinc-700 text-zinc-500'
                            "
                        >
                            {{ scoreLine(m) }}
                        </span>
                        <span class="truncate text-right font-medium text-white">{{ m.away_team.name }}</span>
                    </div>
                    <div class="flex justify-center">
                        <span
                            v-if="m.status === 'finished'"
                            class="rounded-md bg-zinc-800 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-zinc-400"
                        >
                            FT
                            <span v-if="m.is_manually_edited" class="ml-1 text-amber-400/70">edited</span>
                        </span>
                        <span
                            v-else
                            class="rounded-md border border-zinc-700/60 px-2 py-0.5 text-[10px] uppercase tracking-wide text-zinc-600"
                        >
                            Scheduled
                        </span>
                    </div>
                    <div
                        v-if="m.status === 'finished' && editScores[m.id]"
                        class="flex flex-wrap items-end gap-2 border-t border-zinc-800 pt-3"
                    >
                        <label class="flex flex-col gap-1 text-[10px] uppercase text-zinc-500">
                            Home
                            <input
                                v-model.number="editScores[m.id]!.home"
                                type="number"
                                min="0"
                                max="30"
                                class="w-16 rounded-md border border-zinc-700 bg-zinc-950 px-2 py-1.5 text-sm text-white"
                            />
                        </label>
                        <label class="flex flex-col gap-1 text-[10px] uppercase text-zinc-500">
                            Away
                            <input
                                v-model.number="editScores[m.id]!.away"
                                type="number"
                                min="0"
                                max="30"
                                class="w-16 rounded-md border border-zinc-700 bg-zinc-950 px-2 py-1.5 text-sm text-white"
                            />
                        </label>
                        <button
                            type="button"
                            class="rounded-md bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-500 disabled:opacity-50"
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
                                class="rounded-md bg-emerald-900/60 px-2 py-1 text-[10px] font-semibold text-emerald-300"
                            >
                                Saved ✓
                            </span>
                        </Transition>
                    </div>
                </div>
            </li>
        </ul>
        <div class="mt-auto border-t border-zinc-800/80 p-4">
            <slot name="footer" />
        </div>
    </div>
</template>
