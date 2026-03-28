<?php

namespace App\League;

use App\League\Competition\LeagueCompetition;
use App\League\Prediction\ChampionshipProbabilityEstimator;
use App\League\Prediction\TitlePredictionSnapshotService;
use App\League\Standings\PremierLeagueStandingsCalculator;
use App\Models\LeagueState;

/**
 * Persists title-probability snapshots keyed by last fully completed matchweek.
 */
final class LeaguePredictionSnapshotPersister
{
    public function __construct(
        private readonly ChampionshipProbabilityEstimator $probabilityEstimator,
        private readonly PremierLeagueStandingsCalculator $standingsCalculator,
        private readonly TitlePredictionSnapshotService $titlePredictionSnapshots,
    ) {}

    public function persist(LeagueState $state, LeagueCompetition $competition, ?int $iterationsOverride = null): void
    {
        if ($state->step !== 'simulation' || ! $competition->hasMatches()) {
            return;
        }

        $afterMw = $competition->lastFullyCompletedMatchweek();
        $iter = $iterationsOverride ?? (int) config('league.prediction_monte_carlo_iterations', 1200);
        $pred = $this->probabilityEstimator->estimate($competition, $iter)->toArray();
        $finishedThrough = $competition->finishedThroughMatchweek($afterMw);
        $tableAtSnapshot = $this->standingsCalculator->calculate($competition->teams(), $finishedThrough);
        $standingsRows = $tableAtSnapshot->map(fn (array $r): array => [
            'team_id' => $r['team']->id,
            'played' => $r['played'],
            'won' => $r['won'],
            'drawn' => $r['drawn'],
            'lost' => $r['lost'],
            'gf' => $r['gf'],
            'ga' => $r['ga'],
            'gd' => $r['gd'],
            'points' => $r['points'],
        ])->values()->all();

        $merged = $this->titlePredictionSnapshots->upsert(
            is_array($state->prediction_snapshots) ? $state->prediction_snapshots : null,
            $afterMw,
            $pred,
            $standingsRows,
        );
        $state->update(['prediction_snapshots' => $merged]);
    }
}
