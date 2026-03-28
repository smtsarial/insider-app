<?php

namespace App\League\Archive;

use App\League\LeagueScheduleLoader;
use App\League\Standings\PremierLeagueStandingsCalculator;
use App\Models\LeagueMatch;
use App\Models\LeagueState;
use App\Models\LeagueTournamentArchive;

class LeagueTournamentArchiveWriter
{
    public function __construct(
        private readonly LeagueScheduleLoader $scheduleLoader,
        private readonly PremierLeagueStandingsCalculator $standingsCalculator,
    ) {}

    /**
     * Persists the current competition before matches are wiped (reset / new fixtures).
     */
    public function archiveIfCompetitionExists(LeagueState $state): void
    {
        $competition = $this->scheduleLoader->load();
        if (! $competition->hasMatches()) {
            return;
        }

        $finished = $competition->finishedMatches();
        if ($finished->isEmpty()) {
            return;
        }

        $teams = $competition->teams();
        $wasCompleted = $competition->matches()->every(fn (LeagueMatch $m): bool => $m->isFinished());

        $standings = $this->standingsCalculator->calculate($teams, $finished);
        $standingsPayload = $standings->map(fn (array $r): array => [
            'team_id' => $r['team']->id,
            'team_name' => $r['team']->name,
            'played' => $r['played'],
            'won' => $r['won'],
            'drawn' => $r['drawn'],
            'lost' => $r['lost'],
            'gf' => $r['gf'],
            'ga' => $r['ga'],
            'gd' => $r['gd'],
            'points' => $r['points'],
        ])->values()->all();

        $championId = null;
        if ($wasCompleted) {
            $ids = $this->standingsCalculator->firstPlaceTeamIds($teams, $finished);
            $championId = $ids[0] ?? null;
        }

        LeagueTournamentArchive::query()->create([
            'archived_at' => now(),
            'was_completed' => $wasCompleted,
            'champion_team_id' => $championId,
            'finished_matches_count' => $finished->count(),
            'step_at_archive' => $state->step,
            'payload' => [
                'standings' => $standingsPayload,
                'prediction_snapshots' => is_array($state->prediction_snapshots) ? $state->prediction_snapshots : null,
            ],
        ]);
    }
}
