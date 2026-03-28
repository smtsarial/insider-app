<script setup lang="ts">
/** Recent finals — verify at https://www.uefa.com/uefachampionsleague/ */
const recentWinners = [
    { season: '2014–15', winner: 'Barcelona' },
    { season: '2015–16', winner: 'Real Madrid' },
    { season: '2016–17', winner: 'Real Madrid' },
    { season: '2017–18', winner: 'Real Madrid' },
    { season: '2018–19', winner: 'Liverpool' },
    { season: '2019–20', winner: 'Bayern Munich' },
    { season: '2020–21', winner: 'Chelsea' },
    { season: '2021–22', winner: 'Real Madrid' },
    { season: '2022–23', winner: 'Manchester City' },
    { season: '2023–24', winner: 'Real Madrid' },
];

const faqClass =
    'group rounded-lg border border-zinc-800/80 bg-zinc-950/50 [&_summary]:cursor-pointer [&_summary]:list-none [&_summary]:marker:hidden [&_summary::-webkit-details-marker]:hidden';
const summaryClass =
    'px-3 py-2.5 text-xs font-semibold text-indigo-200/90';
const bodyClass = 'space-y-2 border-t border-zinc-800/80 px-3 py-3 text-xs leading-relaxed text-zinc-400';
</script>

<template>
    <div
        class="overflow-hidden rounded-xl border border-indigo-500/20 bg-linear-to-br from-indigo-950/40 via-zinc-900/60 to-zinc-950/80 ring-1 ring-white/5"
    >
        <div class="border-b border-indigo-500/15 bg-indigo-950/30 px-4 py-3">
            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-indigo-300/90">About the competition</p>
            <h2 class="mt-1 text-sm font-semibold text-white">UEFA Champions League — league phase</h2>
            <p class="mt-1 text-[11px] text-zinc-500">
                Quick answers to common questions, then how this demo models the league phase.
            </p>
        </div>

        <div class="space-y-2 px-4 py-4">
            <!-- 1. Football rules (points) -->
            <details :class="faqClass">
                <summary :class="summaryClass">
                    <span class="mr-1 inline-block transition-transform group-open:rotate-90">▸</span>
                    What are football rules? (Points)
                </summary>
                <div :class="bodyClass">
                    <p>
                        In association football leagues and many group tournaments,
                        <strong class="text-zinc-300">three points</strong>
                        are awarded for a win and
                        <strong class="text-zinc-300">none</strong>
                        to the loser. If the match is drawn,
                        <strong class="text-zinc-300">each team gets one point</strong>.
                    </p>
                    <p>
                        That system rewards wins more than draws: when teams are tied on points, rules often use
                        goal difference, goals scored, head-to-head, etc., so a team with more wins can rank above
                        one with more draws.
                    </p>
                    <p class="text-zinc-500">
                        This simulator uses the same 3-1-0 scoring; the ranking tie-break logic follows a
                        Premier League–style table among the four clubs.
                    </p>
                </div>
            </details>

            <!-- 2. What is a league? -->
            <details :class="faqClass">
                <summary :class="summaryClass">
                    <span class="mr-1 inline-block transition-transform group-open:rotate-90">▸</span>
                    What is a league (group stage)?
                </summary>
                <div :class="bodyClass">
                    <p>
                        In large tournaments the proper competition often starts with a
                        <strong class="text-zinc-300">group stage</strong>: for example, historically formats such
                        as
                        <strong class="text-zinc-300">32 teams in eight groups of four</strong>
                        have been used, with seeding at the draw, restrictions (e.g. clubs from the same nation not
                        in the same group), and each team playing the other three
                        <strong class="text-zinc-300">home and away</strong>
                        — a
                        <strong class="text-zinc-300">round robin</strong>
                        inside each group.
                    </p>
                    <p class="text-emerald-300/90">
                        <strong>This demo</strong>
                        keeps the same idea on a small scale:
                        <strong class="text-zinc-200">one group of four clubs</strong>, six matchdays, everyone
                        plays everyone twice (double round-robin).
                    </p>
                </div>
            </details>

            <!-- 3. Champions League -->
            <details :class="faqClass">
                <summary :class="summaryClass">
                    <span class="mr-1 inline-block transition-transform group-open:rotate-90">▸</span>
                    What is the Champions League?
                </summary>
                <div :class="bodyClass">
                    <p>
                        The
                        <strong class="text-zinc-200">UEFA Champions League</strong>
                        (<strong class="text-zinc-300">UCL</strong>) is UEFA’s annual club competition for top
                        European teams. Clubs usually qualify via their domestic leagues (champions and, for some
                        countries, additional places).
                    </p>
                    <p>
                        The path to the trophy traditionally includes a
                        <strong class="text-zinc-300">round-robin group or league phase</strong>, then
                        <strong class="text-zinc-300">two-legged knockouts</strong>
                        and a
                        <strong class="text-zinc-300">single-leg final</strong>.
                        Exact formats and access rules change by season — check UEFA for the current edition.
                    </p>
                    <p>
                        <strong class="text-zinc-500">Here you only simulate one compact league phase;</strong>
                        knockouts and the full UEFA calendar are not included.
                    </p>
                </div>
            </details>

            <!-- 4. Fixture / draw -->
            <details :class="faqClass">
                <summary :class="summaryClass">
                    <span class="mr-1 inline-block transition-transform group-open:rotate-90">▸</span>
                    What is a fixture? (Real draw vs this app)
                </summary>
                <div :class="bodyClass">
                    <p>
                        In the real Champions League,
                        <strong class="text-zinc-300">the draw</strong>
                        places clubs into groups using
                        <strong class="text-zinc-300">seeding pots</strong>
                        (holders, title holders from other competitions, national champions by coefficient, etc.),
                        with
                        <strong class="text-zinc-300">club coefficient rankings</strong>
                        shaping pots 2–4 in classic formats. Rules aim to avoid same-association clashes in a group
                        and to schedule TV nights (e.g. pairing clubs from the same country on different midweeks).
                    </p>
                    <p>
                        Each scheduled pairing is a
                        <strong class="text-zinc-300">fixture</strong>
                        (who plays whom, home or away, and which matchday).
                    </p>
                    <p class="text-emerald-300/90">
                        <strong>This app</strong>
                        does not run a UEFA-style pot draw: it uses a fixed
                        <strong class="text-zinc-200">double round-robin schedule</strong>
                        for the four registered clubs so you can focus on results, the table, and forecasts.
                    </p>
                </div>
            </details>

            <!-- Recent winners -->
            <details class="group rounded-lg border border-zinc-800/80 bg-zinc-950/50">
                <summary
                    class="cursor-pointer list-none px-3 py-2.5 text-xs font-semibold text-indigo-200/90 marker:hidden [&::-webkit-details-marker]:hidden"
                >
                    <span class="mr-1 inline-block transition-transform group-open:rotate-90">▸</span>
                    Recent winners (selected seasons)
                </summary>
                <div class="border-t border-zinc-800/80 px-3 py-2">
                    <table class="w-full text-xs text-zinc-500">
                        <thead>
                            <tr class="text-left text-[10px] uppercase tracking-wider text-zinc-600">
                                <th class="py-1.5 font-medium">Season</th>
                                <th class="py-1.5 font-medium">Winner</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="row in recentWinners"
                                :key="row.season"
                                class="border-t border-zinc-800/50 text-zinc-400"
                            >
                                <td class="py-1.5 font-mono tabular-nums text-zinc-500">{{ row.season }}</td>
                                <td class="py-1.5 text-zinc-300">{{ row.winner }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <a
                        href="https://www.uefa.com/uefachampionsleague/"
                        class="mt-2 inline-block text-[11px] font-medium text-indigo-400 underline-offset-2 hover:text-indigo-300"
                        rel="noopener noreferrer"
                        target="_blank"
                    >
                        Official competition hub → uefa.com
                    </a>
                </div>
            </details>

            <p class="px-1 pt-1 text-[11px] text-zinc-600">
                Educational demo — not affiliated with UEFA. For draws, pots, and regulations, always rely on UEFA’s
                official communications.
            </p>
        </div>
    </div>
</template>
