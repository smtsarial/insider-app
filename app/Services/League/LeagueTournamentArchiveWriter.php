<?php

namespace App\Services\League;

use App\Models\LeagueMatch;
use App\Models\LeagueState;
use App\Models\LeagueTournamentArchive;
use App\Models\Team;

class LeagueTournamentArchiveWriter
{
    public function __construct(
        private readonly PremierLeagueStandingsCalculator $standingsCalculator,
    ) {}

    /**
     * Persists the current competition before matches are wiped (reset / new fixtures).
     */
    public function archiveIfCompetitionExists(LeagueState $state): void
    {
        $matches = LeagueMatch::query()
            ->with(['homeTeam', 'awayTeam'])
            ->orderBy('matchweek')
            ->orderBy('id')
            ->get();

        if ($matches->isEmpty()) {
            return;
        }

        $finished = $matches->filter(fn (LeagueMatch $m): bool => $m->isFinished())->values();
        if ($finished->isEmpty()) {
            return;
        }

        $teams = Team::query()->orderBy('sort_order')->get();
        $wasCompleted = $matches->every(fn (LeagueMatch $m): bool => $m->isFinished());

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
