<?php

namespace App\League;

use App\League\Simulation\MatchSimulationEngine;
use App\Models\LeagueMatch;

/**
 * Applies simulated results to every scheduled fixture in a single matchweek.
 */
final class LeagueMatchWeekSimulator
{
    public function __construct(
        private readonly MatchSimulationEngine $simulationEngine,
    ) {}

    public function simulateScheduledWeek(int $week): void
    {
        $matches = LeagueMatch::query()
            ->with(['homeTeam', 'awayTeam'])
            ->where('matchweek', $week)
            ->where('status', 'scheduled')
            ->orderBy('id')
            ->get();

        foreach ($matches as $match) {
            $seed = (int) ($match->id * 10007 + $week * 131);
            [$hg, $ga] = $this->simulationEngine->simulate($match->homeTeam, $match->awayTeam, $seed);
            $match->update([
                'home_goals' => $hg,
                'away_goals' => $ga,
                'status' => 'finished',
                'is_manually_edited' => false,
            ]);
        }
    }
}
