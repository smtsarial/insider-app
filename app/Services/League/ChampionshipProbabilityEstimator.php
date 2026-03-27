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
    ) {}

    /**
     * @param  Collection<int, Team>  $teams
     * @param  Collection<int, LeagueMatch>  $allMatches
     * @return array<int, float> team_id => percentage 0–100
     */
    public function estimate(Collection $teams, Collection $allMatches, int $iterations = 2000, ?int $baseSeed = null): array
    {
        $finished = $allMatches->filter(fn (LeagueMatch $m): bool => $m->isFinished())->values();
        $scheduled = $allMatches
            ->filter(fn (LeagueMatch $m): bool => ! $m->isFinished())
            ->sortBy(fn (LeagueMatch $m): array => [$m->matchweek, $m->id])
            ->values();

        if ($scheduled->isEmpty()) {
            $table = $this->standingsCalculator->calculate($teams, $finished);
            $winner = $table->first();
            if ($winner === null) {
                return $this->zeros($teams);
            }

            $wid = $winner['team']->id;
            $out = [];
            foreach ($teams as $t) {
                $out[$t->id] = $t->id === $wid ? 100.0 : 0.0;
            }

            return $out;
        }

        /** @var array<int, int> $wins */
        $wins = [];
        foreach ($teams as $t) {
            $wins[$t->id] = 0;
        }

        for ($i = 0; $i < $iterations; $i++) {
            $seed = ($baseSeed ?? 0) + $i * 9973;
            $simulatedRows = collect();

            foreach ($scheduled as $idx => $match) {
                /** @var LeagueMatch $match */
                $home = $match->homeTeam;
                $away = $match->awayTeam;
                [$hg, $ga] = $this->simulationEngine->simulate($home, $away, $seed + $idx);

                $copy = $match->replicate();
                $copy->home_goals = $hg;
                $copy->away_goals = $ga;
                $copy->status = 'finished';
                $simulatedRows->push($copy);
            }

            $combined = $finished->merge($simulatedRows);
            $table = $this->standingsCalculator->calculate($teams, $combined);
            $top = $table->first();
            if ($top !== null) {
                $wins[$top['team']->id]++;
            }
        }

        $out = [];
        foreach ($teams as $t) {
            $out[$t->id] = round(100.0 * ($wins[$t->id] / $iterations), 1);
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
