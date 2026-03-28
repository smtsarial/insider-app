<?php

namespace App\Services\League;

use App\Models\LeagueMatch;
use App\Models\Team;
use Illuminate\Support\Collection;

class ChampionshipProbabilityEstimator
{
    public function __construct(
        private readonly MatchSimulationEngine $simulationEngine,
        private readonly PremierLeagueStandingsCalculator $standingsCalculator,
        private readonly TeamFormAnalyzer $formAnalyzer,
    ) {}

    /**
     * @param  Collection<int, Team>  $teams
     * @param  Collection<int, LeagueMatch>  $allMatches
     * @return array<int, float> team_id => percentage 0–100
     */
    public function estimate(Collection $teams, Collection $allMatches, int $iterations = 3000, ?int $baseSeed = null): array
    {
        $finished = $allMatches->filter(fn (LeagueMatch $m): bool => $m->isFinished())->values();
        $scheduled = $allMatches
            ->filter(fn (LeagueMatch $m): bool => ! $m->isFinished())
            ->sortBy(fn (LeagueMatch $m): array => [$m->matchweek, $m->id])
            ->values();

        if ($scheduled->isEmpty()) {
            $table = $this->standingsCalculator->calculate($teams, $finished);
            if ($table->isEmpty()) {
                return $this->zeros($teams);
            }

            // Season over: one champion from the official table order (H2H, then tie-breaks).
            $shares = $this->championshipSharesForFinalTable($table);

            return $this->sharesToPercentages($shares, $teams);
        }

        $clinch = $this->mathematicalClinchPercentages($teams, $finished, $scheduled);
        if ($clinch !== null) {
            return $clinch;
        }

        $formBoost = $this->formAnalyzer->formStrengthAdjustment($teams, $finished);

        /** @var array<int, float> $wins */
        $wins = [];
        foreach ($teams as $t) {
            $wins[$t->id] = 0.0;
        }

        for ($i = 0; $i < $iterations; $i++) {
            $seed = ($baseSeed ?? 0) + $i * 9973;
            $simulatedRows = collect();

            foreach ($scheduled as $idx => $match) {
                /** @var LeagueMatch $match */
                $home = $match->homeTeam;
                $away = $match->awayTeam;
                $hBoost = $formBoost[$home->id] ?? 0.0;
                $aBoost = $formBoost[$away->id] ?? 0.0;
                [$hg, $ga] = $this->simulationEngine->simulate($home, $away, $seed + $idx, $hBoost, $aBoost);

                $copy = $match->replicate();
                $copy->home_goals = $hg;
                $copy->away_goals = $ga;
                $copy->status = 'finished';
                $simulatedRows->push($copy);
            }

            $combined = $finished->merge($simulatedRows);
            $table = $this->standingsCalculator->calculate($teams, $combined);

            foreach ($this->championshipSharesForMonteCarlo($table) as $teamId => $fraction) {
                $wins[$teamId] += $fraction;
            }
        }

        return $this->fractionalWinsToPercentages(
            $wins,
            $iterations,
            $teams,
            $this->completedFullMatchweeks($allMatches),
        );
    }

    /**
     * If one team’s current points already exceed every rival’s maximum possible points, title is clinched (100% / 0%).
     *
     * @param  Collection<int, LeagueMatch>  $finished
     * @param  Collection<int, LeagueMatch>  $scheduled
     * @return array<int, float>|null
     */
    private function mathematicalClinchPercentages(Collection $teams, Collection $finished, Collection $scheduled): ?array
    {
        $table = $this->standingsCalculator->calculate($teams, $finished);
        if ($table->isEmpty()) {
            return null;
        }

        /** @var array<int, int> $pts */
        $pts = [];
        foreach ($table as $row) {
            $pts[$row['team']->id] = $row['points'];
        }

        /** @var array<int, int> $rem */
        $rem = [];
        foreach ($teams as $t) {
            $rem[$t->id] = 0;
        }
        foreach ($scheduled as $m) {
            $rem[(int) $m->home_team_id]++;
            $rem[(int) $m->away_team_id]++;
        }

        $clinchedId = null;
        foreach ($teams as $t) {
            $tid = $t->id;
            $othersMax = 0;
            foreach ($teams as $o) {
                if ($o->id === $tid) {
                    continue;
                }
                $othersMax = max($othersMax, $pts[$o->id] + 3 * $rem[$o->id]);
            }
            if ($pts[$tid] > $othersMax) {
                if ($clinchedId !== null && $clinchedId !== $tid) {
                    return null;
                }
                $clinchedId = $tid;
            }
        }

        if ($clinchedId === null) {
            return null;
        }

        $out = [];
        foreach ($teams as $t) {
            $out[$t->id] = $t->id === $clinchedId ? 100.0 : 0.0;
        }

        return $out;
    }

    /**
     * After a simulated season: split title probability when teams are tied on points, GD and goals for.
     * Do not use squad-strength ordering here — that would assign 100% of tied outcomes to the stronger
     * club and collapse forecasts (e.g. Liverpool vs Manchester City both on identical top-line stats).
     *
     * @param  Collection<int, array{team: Team, played: int, won: int, drawn: int, lost: int, gf: int, ga: int, gd: int, points: int}>  $table
     * @return array<int, float> team_id => share of this iteration (sums to 1.0)
     */
    private function championshipSharesForMonteCarlo(Collection $table): array
    {
        $rows = $table->values();
        if ($rows->isEmpty()) {
            return [];
        }

        $candidates = $rows;
        foreach (['points', 'gd', 'gf'] as $key) {
            $max = $candidates->max(fn (array $r): int => $r[$key]);
            $next = $candidates->filter(fn (array $r): bool => $r[$key] === $max)->values();
            if ($next->count() === 1) {
                return [(int) $next->first()['team']->id => 1.0];
            }
            $candidates = $next;
        }

        $n = $candidates->count();
        $share = 1.0 / $n;
        $out = [];
        foreach ($candidates as $row) {
            $out[(int) $row['team']->id] = $share;
        }

        return $out;
    }

    /**
     * All fixtures played: exactly one champion from the ordered league table (includes H2H and strength).
     *
     * @param  Collection<int, array{team: Team, played: int, won: int, drawn: int, lost: int, gf: int, ga: int, gd: int, points: int}>  $table
     * @return array<int, float>
     */
    private function championshipSharesForFinalTable(Collection $table): array
    {
        $first = $table->first();
        if ($first === null) {
            return [];
        }

        return [(int) $first['team']->id => 1.0];
    }

    /**
     * @param  array<int, float>  $shares
     * @return array<int, float>
     */
    private function sharesToPercentages(array $shares, Collection $teams): array
    {
        $out = [];
        foreach ($teams as $t) {
            $out[$t->id] = round(100.0 * ($shares[$t->id] ?? 0.0), 1);
        }

        return $out;
    }

    /**
     * Fully completed matchweeks in the schedule (all fixtures in that week finished).
     *
     * @param  Collection<int, LeagueMatch>  $allMatches
     */
    private function completedFullMatchweeks(Collection $allMatches): int
    {
        $max = 0;
        foreach ($allMatches->groupBy('matchweek') as $week => $inWeek) {
            if ($inWeek->isEmpty()) {
                continue;
            }
            if ($inWeek->every(fn (LeagueMatch $m): bool => $m->isFinished())) {
                $max = max($max, (int) $week);
            }
        }

        return $max;
    }

    /**
     * @param  array<int, float>  $wins
     * @return array<int, float>
     */
    private function fractionalWinsToPercentages(
        array $wins,
        int $iterations,
        Collection $teams,
        int $completedFullMatchweeks,
    ): array {
        $n = $teams->count();
        $uniform = 100.0 / max(1, $n);
        $totalWeeks = max(1, (int) config('league.timeline_matchweeks', 6));
        $exponent = (float) config('league.title_probability_timeline_blend_exponent', 2);
        $ratio = min(1.0, $completedFullMatchweeks / $totalWeeks);
        $blendTowardSim = $ratio ** $exponent;

        $out = [];
        foreach ($teams as $t) {
            $mcPct = $iterations > 0
                ? (100.0 * (($wins[$t->id] ?? 0.0) / $iterations))
                : $uniform;
            $mixed = (1.0 - $blendTowardSim) * $uniform + $blendTowardSim * $mcPct;
            $out[$t->id] = round($mixed, 1);
        }

        return $out;
    }

    /**
     * @param  Collection<int, Team>  $teams
     * @return array<int, float>
     */
    private function zeros(Collection $teams): array
    {
        $out = [];
        foreach ($teams as $t) {
            $out[$t->id] = 0.0;
        }

        return $out;
    }
}
