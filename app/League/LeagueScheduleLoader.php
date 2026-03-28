<?php

namespace App\League;

use App\League\Competition\LeagueCompetition;
use App\Models\LeagueMatch;
use App\Models\Team;

/**
 * Loads the active competition graph from persistence (teams + fixtures with relations).
 */
final class LeagueScheduleLoader
{
    public function load(): LeagueCompetition
    {
        $teams = Team::query()->orderBy('sort_order')->get();
        $matches = LeagueMatch::query()
            ->with(['homeTeam', 'awayTeam'])
            ->orderBy('matchweek')
            ->orderBy('id')
            ->get();

        return LeagueCompetition::fromCollections($teams, $matches);
    }
}
