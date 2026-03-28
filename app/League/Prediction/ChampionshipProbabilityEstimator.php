<?php

namespace App\League\Prediction;

use App\League\Competition\LeagueCompetition;
use App\League\Form\TeamFormAnalyzer;
use App\League\Simulation\MatchSimulationEngine;
use App\League\Standings\PremierLeagueStandingsCalculator;
use App\Models\LeagueMatch;
use App\Models\Team;
use Illuminate\Support\Collection;

/**
 * Title probability: (1) season over → official table leader; (2) mathematical clinch → 100%/0%;
 * (3) otherwise Monte Carlo on remaining fixtures, with power+form split on simulated pts/GD/GF ties,
 * then timeline blend toward a uniform prior for display stability early in the phase.
 */
class ChampionshipProbabilityEstimator
{
    public function __construct(
        private readonly MatchSimulationEngine $simulationEngine,
        private readonly PremierLeagueStandingsCalculator $standingsCalculator,
        private readonly TeamFormAnalyzer $formAnalyzer,
    ) {}

    public function estimate(LeagueCompetition $competition, ?int $iterations = null, ?int $baseSeed = null): TitleProbabilities
    {
        $iterations ??= (int) config('league.prediction_monte_carlo_iterations', 1200);

        $teams = $competition->teams();
        $finished = $competition->finishedMatches();
        $scheduled = $competition->scheduledMatchesSorted();

        if ($scheduled->isEmpty()) {
            $table = $this->standingsCalculator->calculate($teams, $finished);
            if ($table->isEmpty()) {
                return TitleProbabilities::allZeros($teams);
            }

            $shares = $this->championshipSharesForFinalTable($table);

            return TitleProbabilities::fromPercentByTeamId($this->sharesToPercentages($shares, $teams));
        }

        $clinch = $this->mathematicalClinchPercentages($teams, $finished, $scheduled);
        if ($clinch !== null) {
            return TitleProbabilities::fromPercentByTeamId($clinch);
        }

        $formBoost = $this->formAnalyzer->formStrengthAdjustment($teams, $finished);
        $weightFloor = (float) config('league.prediction_tie_weight_floor', 0.25);
        $timelineWeeks = max(1, (int) config('league.timeline_matchweeks', 6));
        $blendExponent = (float) config('league.title_probability_timeline_blend_exponent', 2);
        $completedFullMw = $competition->lastFullyCompletedMatchweek();

        /** @var list<array{home: Team, away: Team, home_team_id: int, away_team_id: int, matchweek: int}> $slots */
        $slots = [];
        foreach ($scheduled as $m) {
            $slots[] = [
                'home' => $m->homeTeam,
                'away' => $m->awayTeam,
                'home_team_id' => (int) $m->home_team_id,
                'away_team_id' => (int) $m->away_team_id,
                'matchweek' => (int) $m->matchweek,
            ];
        }

        /** @var array<int, float> $wins */
        $wins = [];
        foreach ($teams as $t) {
            $wins[$t->id] = 0.0;
        }

        $seedBase = $baseSeed ?? 0;

        for ($i = 0; $i < $iterations; $i++) {
            $seed = $seedBase + $i * 9973;
            /** @var list<LeagueMatch> $simulated */
            $simulated = [];

            foreach ($slots as $idx => $slot) {
                $home = $slot['home'];
                $away = $slot['away'];
                $hBoost = $formBoost[$home->id] ?? 0.0;
                $aBoost = $formBoost[$away->id] ?? 0.0;
                [$hg, $ga] = $this->simulationEngine->simulate($home, $away, $seed + $idx, $hBoost, $aBoost);

                $simulated[] = tap(LeagueMatch::make([
                    'home_team_id' => $slot['home_team_id'],
                    'away_team_id' => $slot['away_team_id'],
                    'matchweek' => $slot['matchweek'],
                    'home_goals' => $hg,
                    'away_goals' => $ga,
                    'status' => 'finished',
                ]), function (LeagueMatch $row) use ($home, $away): void {
                    $row->setRelation('homeTeam', $home);
                    $row->setRelation('awayTeam', $away);
                });
            }

            $combined = $finished->concat($simulated);
            $table = $this->standingsCalculator->calculate($teams, $combined);

            foreach ($this->championshipSharesForMonteCarlo($table, $formBoost, $weightFloor) as $teamId => $fraction) {
                $wins[$teamId] += $fraction;
            }
        }

        return TitleProbabilities::fromPercentByTeamId($this->fractionalWinsToPercentages(
            $wins,
            $iterations,
            $teams,
            $completedFullMw,
            $timelineWeeks,
            $blendExponent,
        ));
    }

    /**
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

        /** @var array<int, int> $maxPossible */
        $maxPossible = [];
        foreach ($teams as $t) {
            $maxPossible[$t->id] = $pts[$t->id] + 3 * $rem[$t->id];
        }

        $clinchedId = null;
        foreach ($teams as $t) {
            $tid = $t->id;
            $othersMax = 0;
            foreach ($teams as $o) {
                if ($o->id === $tid) {
                    continue;
                }
                $othersMax = max($othersMax, $maxPossible[$o->id]);
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
     * @param  Collection<int, array{team: Team, played: int, won: int, drawn: int, lost: int, gf: int, ga: int, gd: int, points: int}>  $table
     * @param  array<int, float>  $formBoost
     * @return array<int, float> team_id => share of this iteration (sums to 1.0)
     */
    private function championshipSharesForMonteCarlo(Collection $table, array $formBoost, float $weightFloor): array
    {
        /** @var list<array{team: Team, played: int, won: int, drawn: int, lost: int, gf: int, ga: int, gd: int, points: int}> $candidates */
        $candidates = $table->values()->all();
        if ($candidates === []) {
            return [];
        }

        foreach (['points', 'gd', 'gf'] as $key) {
            $max = max(array_map(fn (array $r): int => $r[$key], $candidates));
            $next = [];
            foreach ($candidates as $r) {
                if ($r[$key] === $max) {
                    $next[] = $r;
                }
            }
            if (count($next) === 1) {
                return [(int) $next[0]['team']->id => 1.0];
            }
            $candidates = $next;
        }

        $weights = [];
        foreach ($candidates as $row) {
            $team = $row['team'];
            $tid = $team->id;
            $fb = $formBoost[$tid] ?? 0.0;
            $weights[$tid] = max($weightFloor, (float) $team->power + $fb);
        }

        $sum = array_sum($weights);
        $count = count($weights);
        $out = [];
        foreach ($weights as $tid => $w) {
            $out[$tid] = $sum > 0 ? $w / $sum : 1.0 / max(1, $count);
        }

        return $out;
    }

    /**
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
     * @param  array<int, float>  $wins
     * @return array<int, float>
     */
    private function fractionalWinsToPercentages(
        array $wins,
        int $iterations,
        Collection $teams,
        int $completedFullMatchweeks,
        int $timelineWeeks,
        float $blendExponent,
    ): array {
        $n = $teams->count();
        $uniform = 100.0 / max(1, $n);
        $ratio = min(1.0, $completedFullMatchweeks / $timelineWeeks);
        $blendTowardSim = $ratio ** $blendExponent;

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
}
