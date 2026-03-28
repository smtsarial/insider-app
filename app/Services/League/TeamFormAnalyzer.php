<?php

namespace App\Services\League;

use App\Models\LeagueMatch;
use App\Models\Team;
use Illuminate\Support\Collection;

/**
 * Derives strength adjustments from results played so far for simulation weighting.
 */
class TeamFormAnalyzer
{
    /**
     * @param  Collection<int, Team>  $teams
     * @param  Collection<int, LeagueMatch>  $finishedMatches
     * @return array<int, float> team_id => adjustment (added to effective strength)
     */
    public function formStrengthAdjustment(Collection $teams, Collection $finishedMatches): array
    {
        /** @var array<int, array{played: int, gf: int, ga: int, pts: float}> $acc */
        $acc = [];
        foreach ($teams as $t) {
            $acc[$t->id] = ['played' => 0, 'gf' => 0, 'ga' => 0, 'pts' => 0.0];
        }

        foreach ($finishedMatches as $m) {
            if (! $m->isFinished()) {
                continue;
            }

            $hg = (int) $m->home_goals;
            $ag = (int) $m->away_goals;
            $hid = (int) $m->home_team_id;
            $aid = (int) $m->away_team_id;

            $acc[$hid]['played']++;
            $acc[$aid]['played']++;
            $acc[$hid]['gf'] += $hg;
            $acc[$hid]['ga'] += $ag;
            $acc[$aid]['gf'] += $ag;
            $acc[$aid]['ga'] += $hg;

            if ($hg > $ag) {
                $acc[$hid]['pts'] += 3;
            } elseif ($hg < $ag) {
                $acc[$aid]['pts'] += 3;
            } else {
                $acc[$hid]['pts'] += 1;
                $acc[$aid]['pts'] += 1;
            }
        }

        $out = [];
        foreach ($teams as $t) {
            $s = $acc[$t->id];
            if ($s['played'] === 0) {
                $out[$t->id] = 0.0;

                continue;
            }

            $gd = $s['gf'] - $s['ga'];
            $ppg = $s['pts'] / $s['played'];
            $adjustment = ($ppg - 1.5) * 1.75 + $gd * 0.075 + min(2.25, $s['gf'] * 0.048);
            $out[$t->id] = max(-4.0, min(8.0, $adjustment));
        }

        return $out;
    }
}
