<?php

namespace App\League;

use App\League\Competition\LeagueCompetition;
use App\League\Prediction\ChampionshipProbabilityEstimator;
use App\League\Prediction\TitlePredictionSnapshotService;
use App\League\Standings\PremierLeagueStandingsCalculator;
use App\Models\LeagueMatch;
use App\Models\LeagueState;
use App\Models\Team;

/**
 * Builds Inertia props for the live simulation surface (standings, fixtures, predictions).
 */
final class LeagueSimulationPresenter
{
    public function __construct(
        private readonly PremierLeagueStandingsCalculator $standingsCalculator,
        private readonly ChampionshipProbabilityEstimator $probabilityEstimator,
        private readonly TitlePredictionSnapshotService $titlePredictionSnapshots,
    ) {}

    /**
     * @param  callable(Team): array<string, mixed>  $teamPayload
     * @return array<string, mixed>
     */
    public function simulationProps(LeagueState $state, LeagueCompetition $competition, callable $teamPayload): array
    {
        $teams = $competition->teams();
        $finished = $competition->finishedMatches();
        $standings = $this->standingsCalculator->calculate($teams, $finished);
        $lastCompleted = $competition->lastFullyCompletedMatchweek();
        $nextPlayable = $competition->nextPlayableMatchweek();
        $seasonComplete = $competition->seasonComplete();
        $predictionActive = $competition->isTitlePredictionActive();

        $iterations = (int) config('league.prediction_monte_carlo_iterations', 1200);

        $predMap = $predictionActive
            ? $this->probabilityEstimator->estimate($competition, $iterations)->toArray()
            : [];

        /** @var list<array<string, mixed>>|null $snapshots */
        $snapshots = is_array($state->prediction_snapshots) ? $state->prediction_snapshots : null;
        $historyGrid = $this->titlePredictionSnapshots->buildHistoryGrid(
            $teams,
            $snapshots,
            $teamPayload,
        );

        $trackRecord = null;
        if ($seasonComplete) {
            $championIds = $this->standingsCalculator->firstPlaceTeamIds($teams, $finished);
            $trackRecord = $this->titlePredictionSnapshots->favoriteVersusChampionTrackRecord($snapshots, $championIds);
            if (is_array($trackRecord)) {
                $trackRecord['champion_names'] = $teams
                    ->whereIn('id', $trackRecord['champion_team_ids'])
                    ->pluck('name')
                    ->values()
                    ->all();
            }
        }

        return [
            'currentViewWeek' => $state->current_view_week,
            'lastCompletedMatchweek' => $lastCompleted,
            'nextPlayableWeek' => $nextPlayable,
            'seasonComplete' => $seasonComplete,
            'predictionActive' => $predictionActive,
            'standings' => $standings->map(function (array $row) use ($predMap, $predictionActive, $teamPayload): array {
                /** @var Team $team */
                $team = $row['team'];

                return [
                    'team' => $teamPayload($team),
                    'played' => $row['played'],
                    'won' => $row['won'],
                    'drawn' => $row['drawn'],
                    'lost' => $row['lost'],
                    'gf' => $row['gf'],
                    'ga' => $row['ga'],
                    'gd' => $row['gd'],
                    'points' => $row['points'],
                    'title_percent' => $predictionActive ? ($predMap[$team->id] ?? 0.0) : null,
                ];
            })->values()->all(),
            'matchesByWeek' => $this->matchesByWeekPayload($competition, $teamPayload),
            'predictionHistoryGrid' => $historyGrid,
            'predictionTrackRecord' => $trackRecord,
        ];
    }

    /**
     * @param  callable(Team): array<string, mixed>  $teamPayload
     * @return array<int, array{week: int, matches: list<array<string, mixed>>}>
     */
    public function matchesByWeekPayload(LeagueCompetition $competition, callable $teamPayload): array
    {
        $matches = $competition->matches();
        $grouped = $matches->groupBy('matchweek');
        $weeks = [];
        for ($w = 1; $w <= 6; $w++) {
            $list = $grouped->get($w, collect());
            $weeks[] = [
                'week' => $w,
                'matches' => $list->map(function (LeagueMatch $m) use ($teamPayload): array {
                    return [
                        'id' => $m->id,
                        'matchweek' => $m->matchweek,
                        'home_team' => $teamPayload($m->homeTeam),
                        'away_team' => $teamPayload($m->awayTeam),
                        'home_goals' => $m->home_goals,
                        'away_goals' => $m->away_goals,
                        'status' => $m->status,
                        'is_manually_edited' => $m->is_manually_edited,
                    ];
                })->values()->all(),
            ];
        }

        return $weeks;
    }
}
