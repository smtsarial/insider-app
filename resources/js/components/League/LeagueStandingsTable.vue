<script setup lang="ts">
import type { LeagueStandingRow } from '@/types/league';

defineProps<{
    standings: LeagueStandingRow[];
    predictionActive: boolean;
    lastCompletedMatchweek: number;
}>();
</script>

<template>
    <div class="flex h-full flex-col overflow-hidden rounded-xl border border-zinc-300/20 bg-zinc-900/60 shadow-lg shadow-black/20 ring-1 ring-white/5">
        <div
            class="border-b border-zinc-800 bg-gradient-to-r from-zinc-800/90 to-zinc-900/90 px-4 py-3 text-xs font-semibold uppercase tracking-wider text-zinc-400"
        >
            League phase table
        </div>
        <div class="min-h-0 flex-1 overflow-x-auto">
            <table class="w-full min-w-[18rem] text-left text-sm">
                <thead>
                    <tr class="border-b border-zinc-800/60 text-[10px] uppercase tracking-wider text-zinc-500">
                        <th class="py-2.5 pl-4 pr-2">#</th>
                        <th class="px-2 py-2.5">Team</th>
                        <th class="px-2 py-2.5 text-center">PTS</th>
                        <th class="px-2 py-2.5 text-center">P</th>
                        <th class="px-2 py-2.5 text-center">W</th>
                        <th class="px-2 py-2.5 text-center">D</th>
                        <th class="px-2 py-2.5 text-center">L</th>
                        <th class="px-2 py-2.5 text-center">GD</th>
                        <th v-if="predictionActive" class="px-3 py-2.5 text-right text-emerald-500/80">Title %</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="(row, i) in standings"
                        :key="row.team.id"
                        class="border-t border-zinc-800/50 transition-colors hover:bg-zinc-800/25"
                        :class="{ 'bg-zinc-800/15': i === 0 && lastCompletedMatchweek > 0 }"
                    >
                        <td class="py-3 pl-4 pr-2 text-zinc-500">{{ i + 1 }}</td>
                        <td class="px-2 py-3 font-medium text-white">{{ row.team.name }}</td>
                        <td class="px-2 py-3 text-center font-bold tabular-nums text-white">{{ row.points }}</td>
                        <td class="px-2 py-3 text-center tabular-nums text-zinc-400">{{ row.played }}</td>
                        <td class="px-2 py-3 text-center tabular-nums text-emerald-400/90">{{ row.won }}</td>
                        <td class="px-2 py-3 text-center tabular-nums text-zinc-400">{{ row.drawn }}</td>
                        <td class="px-2 py-3 text-center tabular-nums text-zinc-500">{{ row.lost }}</td>
                        <td
                            class="px-2 py-3 text-center tabular-nums"
                            :class="row.gd >= 0 ? 'text-emerald-400/80' : 'text-rose-400/80'"
                        >
                            {{ row.gd >= 0 ? '+' : '' }}{{ row.gd }}
                        </td>
                        <td
                            v-if="predictionActive"
                            class="px-3 py-3 text-right font-mono tabular-nums text-emerald-400/90"
                        >
                            {{ row.title_percent != null ? `${row.title_percent}%` : '—' }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p
            v-if="!predictionActive && lastCompletedMatchweek < 3"
            class="border-t border-zinc-800/80 px-4 py-2.5 text-xs text-zinc-500"
        >
            Championship probability appears from matchday 4 (after the first three matchdays are complete).
        </p>
        <div class="mt-auto border-t border-zinc-800/80 p-4">
            <slot name="footer" />
        </div>
    </div>
</template>
