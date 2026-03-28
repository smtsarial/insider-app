<script setup lang="ts">
import { ref } from 'vue';

export type PastTournamentStandingDetail = {
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
};

export type ArchivedPredictionSnapshot = {
    after_matchweek: number;
    percentages: Record<string, number | string>;
    standings?: Array<{ team_id: number; points: number }>;
};

export type PastTournamentRow = {
    id: number;
    archived_at: string;
    was_completed: boolean;
    champion_name: string | null;
    finished_matches: number;
    step_at_archive: string | null;
    table: Array<{ team_name: string; points: number; played: number; gd: number }>;
    standings_detail?: PastTournamentStandingDetail[];
    prediction_snapshots?: ArchivedPredictionSnapshot[];
};

defineProps<{
    tournaments: PastTournamentRow[];
}>();

const expandedId = ref<number | null>(null);

function toggle(id: number): void {
    expandedId.value = expandedId.value === id ? null : id;
}

function formatWhen(iso: string): string {
    try {
        return new Intl.DateTimeFormat(undefined, {
            dateStyle: 'medium',
            timeStyle: 'short',
        }).format(new Date(iso));
    } catch {
        return iso;
    }
}

function stepLabel(step: string | null): string {
    if (!step) {
        return '—';
    }

    const map: Record<string, string> = {
        teams: 'Clubs',
        fixtures: 'Fixture draw',
        simulation: 'League phase',
    };

    return map[step] ?? step;
}

function sortedSnaps(t: PastTournamentRow): ArchivedPredictionSnapshot[] {
    const list = t.prediction_snapshots ?? [];

    return [...list].sort((a, b) => a.after_matchweek - b.after_matchweek);
}

function pctInSnapshot(snap: ArchivedPredictionSnapshot, teamId: number): string {
    const raw = snap.percentages[String(teamId)];

    if (raw === undefined || raw === null) {
        return '—';
    }

    return `${Number(raw)}%`;
}

function ptsInSnapshot(snap: ArchivedPredictionSnapshot, teamId: number): string {
    const rows = snap.standings ?? [];
    const row = rows.find((r) => r.team_id === teamId);

    return row !== undefined ? String(row.points) : '—';
}
</script>

<template>
    <section
        class="overflow-hidden rounded-2xl border border-zinc-800/80 bg-zinc-900/55 shadow-xl shadow-black/20 ring-1 ring-white/5"
    >
        <div
            class="border-b border-zinc-800/90 bg-linear-to-r from-zinc-800/95 to-zinc-900/90 px-5 py-4 text-xs font-semibold uppercase tracking-wider text-zinc-400"
        >
            Previous league phases
            <span class="mt-1 block font-normal normal-case leading-relaxed text-zinc-500">
                Expand a row for the full table and saved title-% snapshots.
            </span>
        </div>
        <div v-if="tournaments.length === 0" class="px-5 py-10 text-center text-sm leading-relaxed text-zinc-500">
            No archived league phases yet. After you play matches and reset or draw a new schedule, the previous
            run appears here.
        </div>
        <ul v-else class="divide-y divide-zinc-800/60">
            <li v-for="t in tournaments" :key="t.id" class="bg-zinc-900/30">
                <button
                    type="button"
                    class="flex w-full items-start gap-3 px-4 py-4 text-left transition hover:bg-zinc-800/30 focus-visible:bg-zinc-800/25 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-indigo-500/50"
                    :aria-expanded="expandedId === t.id"
                    @click="toggle(t.id)"
                >
                    <span
                        class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded border border-zinc-700 text-[10px] text-zinc-500"
                    >
                        {{ expandedId === t.id ? '−' : '+' }}
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="flex flex-wrap items-baseline justify-between gap-2">
                            <span class="text-xs font-medium text-zinc-300">{{ formatWhen(t.archived_at) }}</span>
                            <span
                                class="rounded px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide"
                                :class="
                                    t.was_completed
                                        ? 'bg-emerald-900/50 text-emerald-300'
                                        : 'bg-amber-900/40 text-amber-200/90'
                                "
                            >
                                {{ t.was_completed ? 'Completed' : 'Partial' }}
                            </span>
                        </span>
                        <p class="mt-1 text-sm text-white">
                            <span v-if="t.champion_name" class="font-semibold text-emerald-300/90">{{
                                t.champion_name
                            }}</span>
                            <span v-else class="text-zinc-500">No champion</span>
                            <span class="text-zinc-500"> · {{ t.finished_matches }} FT matches</span>
                        </p>
                        <p class="mt-1 text-[11px] text-zinc-600">Archived during: {{ stepLabel(t.step_at_archive) }}</p>
                    </span>
                </button>

                <div
                    v-show="expandedId === t.id"
                    class="border-t border-zinc-800/80 bg-zinc-950/40 px-4 py-4"
                >
                    <template v-if="(t.standings_detail ?? []).length > 0">
                        <p class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-zinc-500">
                            Final table (at archive)
                        </p>
                        <div class="overflow-x-auto rounded-lg border border-zinc-800/80">
                            <table class="w-full min-w-lg text-sm">
                                <thead>
                                    <tr class="border-b border-zinc-800 text-[10px] uppercase tracking-wider text-zinc-500">
                                        <th class="px-3 py-2 text-left font-medium">Club</th>
                                        <th class="px-2 py-2 text-right font-medium">Pld</th>
                                        <th class="px-2 py-2 text-right font-medium">W</th>
                                        <th class="px-2 py-2 text-right font-medium">D</th>
                                        <th class="px-2 py-2 text-right font-medium">L</th>
                                        <th class="px-2 py-2 text-right font-medium">GF</th>
                                        <th class="px-2 py-2 text-right font-medium">GA</th>
                                        <th class="px-2 py-2 text-right font-medium">GD</th>
                                        <th class="px-3 py-2 text-right font-medium">Pts</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="line in t.standings_detail"
                                        :key="line.team_id + '-' + line.team_name"
                                        class="border-t border-zinc-800/50 text-zinc-300"
                                    >
                                        <td class="px-3 py-2 font-medium text-zinc-200">{{ line.team_name }}</td>
                                        <td class="px-2 py-2 text-right tabular-nums text-zinc-400">{{ line.played }}</td>
                                        <td class="px-2 py-2 text-right tabular-nums text-zinc-400">{{ line.won }}</td>
                                        <td class="px-2 py-2 text-right tabular-nums text-zinc-400">{{ line.drawn }}</td>
                                        <td class="px-2 py-2 text-right tabular-nums text-zinc-400">{{ line.lost }}</td>
                                        <td class="px-2 py-2 text-right tabular-nums text-zinc-400">{{ line.gf }}</td>
                                        <td class="px-2 py-2 text-right tabular-nums text-zinc-400">{{ line.ga }}</td>
                                        <td class="px-2 py-2 text-right tabular-nums text-zinc-400">{{ line.gd }}</td>
                                        <td class="px-3 py-2 text-right font-semibold tabular-nums text-emerald-400/90">
                                            {{ line.points }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </template>
                    <p v-else class="text-sm text-zinc-500">No standings were stored for this archive.</p>

                    <div
                        v-if="sortedSnaps(t).length > 0 && (t.standings_detail ?? []).length > 0"
                        class="mt-6 overflow-x-auto"
                    >
                        <p class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-zinc-500">
                            Points &amp; title % by matchweek (saved snapshots)
                        </p>
                        <table class="w-full min-w-xl text-xs">
                            <thead>
                                <tr class="border-b border-zinc-800 text-[10px] uppercase tracking-wider text-zinc-500">
                                    <th class="sticky left-0 z-10 bg-zinc-950 py-2 pl-2 pr-3 text-left">Club</th>
                                    <template v-for="snap in sortedSnaps(t)" :key="'h-' + t.id + '-' + snap.after_matchweek">
                                        <th
                                            class="border-l border-zinc-800/70 px-2 py-2 text-center text-zinc-600"
                                            colspan="2"
                                        >
                                            After MW {{ snap.after_matchweek }}
                                        </th>
                                    </template>
                                </tr>
                                <tr class="border-b border-zinc-800/60">
                                    <th class="sticky left-0 z-10 bg-zinc-950/90 py-1 pl-2 pr-3" />
                                    <template v-for="snap in sortedSnaps(t)" :key="'s-' + t.id + '-' + snap.after_matchweek">
                                        <th class="border-l border-zinc-800/50 px-2 py-1 text-right font-normal text-zinc-600">
                                            Pts
                                        </th>
                                        <th class="px-2 py-1 text-right font-normal text-emerald-700/80">Title</th>
                                    </template>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="line in t.standings_detail"
                                    :key="'snap-' + t.id + '-' + line.team_id"
                                    class="border-t border-zinc-800/40"
                                >
                                    <td
                                        class="sticky left-0 z-10 bg-zinc-950/95 py-2 pl-2 pr-3 font-medium text-zinc-200"
                                    >
                                        {{ line.team_name }}
                                    </td>
                                    <template v-for="snap in sortedSnaps(t)" :key="line.team_id + '-' + snap.after_matchweek">
                                        <td class="border-l border-zinc-800/40 px-2 py-2 text-right tabular-nums text-zinc-400">
                                            {{ ptsInSnapshot(snap, line.team_id) }}
                                        </td>
                                        <td class="px-2 py-2 text-right font-mono tabular-nums text-emerald-500/80">
                                            {{ pctInSnapshot(snap, line.team_id) }}
                                        </td>
                                    </template>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </li>
        </ul>
    </section>
</template>
