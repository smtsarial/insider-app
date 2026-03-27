<?php

namespace App\Services\League;

use App\Models\LeagueMatch;
use App\Models\Team;
use Illuminate\Support\Collection;

class PremierLeagueStandingsCalculator
{
    /**
     * @param  Collection<int, Team>  $teams
     * @param  Collection<int, LeagueMatch>  $finishedMatches
     * @return Collection<int, array{
     *     team: Team,
     *     played: int,
     *     won: int,
     *     drawn: int,
     *     lost: int,
     *     gf: int,
     *     ga: int,
     *     gd: int,
     *     points: int
     * }>
     */
    public function calculate(Collection $teams, Collection $finishedMatches): Collection
    {
        /** @var array<int, array{played: int, won: int, drawn: int, lost: int, gf: int, ga: int, gd: int, points: int}> $stats */
        $stats = [];
        foreach ($teams as $team) {
            $stats[$team->id] = [
                'played' => 0,
                'won' => 0,
                'drawn' => 0,
                'lost' => 0,
                'gf' => 0,
                'ga' => 0,
                'gd' => 0,
                'points' => 0,
            ];
        }

        foreach ($finishedMatches as $match) {
            if (! $match->isFinished()) {
                continue;
            }

            $hg = (int) $match->home_goals;
            $ag = (int) $match->away_goals;
            $hid = (int) $match->home_team_id;
            $aid = (int) $match->away_team_id;

            $this->applyResult($stats, $hid, $aid, $hg, $ag);
        }

        $list = $teams->values()->all();

        usort($list, function (Team $a, Team $b) use ($stats, $finishedMatches, $teams): int {
            $sa = $stats[$a->id];
            $sb = $stats[$b->id];

            if ($sa['points'] !== $sb['points']) {
                return $sb['points'] <=> $sa['points'];
            }
            if ($sa['gd'] !== $sb['gd']) {
                return $sb['gd'] <=> $sa['gd'];
            }
            if ($sa['gf'] !== $sb['gf']) {
                return $sb['gf'] <=> $sa['gf'];
            }

            $tieIds = $teams->filter(function (Team $t) use ($stats, $sa): bool {
                $st = $stats[$t->id];

                return $st['points'] === $sa['points']
                    && $st['gd'] === $sa['gd']
                    && $st['gf'] === $sa['gf'];
            })->pluck('id');

            if ($tieIds->count() <= 1) {
                return 0;
            }

            $mini = $this->miniLeagueTable($tieIds, $finishedMatches);

            $ma = $mini[$a->id] ?? ['pts' => 0, 'gd' => 0, 'gf' => 0];
            $mb = $mini[$b->id] ?? ['pts' => 0, 'gd' => 0, 'gf' => 0];

            if ($ma['pts'] !== $mb['pts']) {
                return $mb['pts'] <=> $ma['pts'];
            }
            if ($ma['gd'] !== $mb['gd']) {
                return $mb['gd'] <=> $ma['gd'];
            }

            return $mb['gf'] <=> $ma['gf'];
        });

        return collect($list)->values()->map(function (Team $team) use ($stats): array {
            $s = $stats[$team->id];

            return [
                'team' => $team,
                'played' => $s['played'],
                'won' => $s['won'],
                'drawn' => $s['drawn'],
                'lost' => $s['lost'],
                'gf' => $s['gf'],
                'ga' => $s['ga'],
                'gd' => $s['gd'],
                'points' => $s['points'],
            ];
        });
    }

    /**
     * @param  array<int, array{played: int, won: int, drawn: int, lost: int, gf: int, ga: int, gd: int, points: int}>  $stats
     */
    private function applyResult(array &$stats, int $homeId, int $awayId, int $hg, int $ag): void
    {
        $stats[$homeId]['played']++;
        $stats[$awayId]['played']++;
        $stats[$homeId]['gf'] += $hg;
        $stats[$homeId]['ga'] += $ag;
        $stats[$awayId]['gf'] += $ag;
        $stats[$awayId]['ga'] += $hg;
        $stats[$homeId]['gd'] = $stats[$homeId]['gf'] - $stats[$homeId]['ga'];
        $stats[$awayId]['gd'] = $stats[$awayId]['gf'] - $stats[$awayId]['ga'];

        if ($hg > $ag) {
            $stats[$homeId]['won']++;
            $stats[$awayId]['lost']++;
            $stats[$homeId]['points'] += 3;
        } elseif ($hg < $ag) {
            $stats[$awayId]['won']++;
            $stats[$homeId]['lost']++;
            $stats[$awayId]['points'] += 3;
        } else {
            $stats[$homeId]['drawn']++;
            $stats[$awayId]['drawn']++;
            $stats[$homeId]['points']++;
            $stats[$awayId]['points']++;
        }
    }

    /**
     * @param  Collection<int, int|string>  $teamIds
     * @return array<int, array{pts: int, gd: int, gf: int}>
     */
    private function miniLeagueTable(Collection $teamIds, Collection $matches): array
    {
        $ids = $teamIds->map(fn (int|string $id): int => (int) $id);
        /** @var array<int, array{gf: int, ga: int, pts: int}> $m */
        $m = [];
        foreach ($ids as $id) {
            $m[$id] = ['gf' => 0, 'ga' => 0, 'pts' => 0];
        }

        foreach ($matches as $match) {
            if (! $match->isFinished()) {
                continue;
            }

            $hid = (int) $match->home_team_id;
            $aid = (int) $match->away_team_id;
            if (! $ids->contains($hid) || ! $ids->contains($aid)) {
                continue;
            }

            $hg = (int) $match->home_goals;
            $ag = (int) $match->away_goals;

            $m[$hid]['gf'] += $hg;
            $m[$hid]['ga'] += $ag;
            $m[$aid]['gf'] += $ag;
            $m[$aid]['ga'] += $hg;

            if ($hg > $ag) {
                $m[$hid]['pts'] += 3;
            } elseif ($hg < $ag) {
                $m[$aid]['pts'] += 3;
            } else {
                $m[$hid]['pts']++;
                $m[$aid]['pts']++;
            }
        }

        $out = [];
        foreach ($m as $id => $row) {
            $out[$id] = [
                'pts' => $row['pts'],
                'gd' => $row['gf'] - $row['ga'],
                'gf' => $row['gf'],
            ];
        }

        return $out;
    }
}
