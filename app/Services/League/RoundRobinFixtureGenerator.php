<?php

namespace App\Services\League;

use App\Models\Team;
use Illuminate\Support\Collection;

class RoundRobinFixtureGenerator
{
    /**
     * Canonical double round-robin for four teams (demo schedule).
     * Teams must be ordered: Arsenal, Liverpool, Manchester City, Chelsea (by sort_order).
     *
     * @param  Collection<int, Team>  $teamsOrdered
     * @return list<array{matchweek: int, home_team_id: int, away_team_id: int}>
     */
    public function generate(Collection $teamsOrdered): array
    {
        if ($teamsOrdered->count() !== 4) {
            throw new \InvalidArgumentException('Exactly four teams are required.');
        }

        /** @var list<int> $ids */
        $ids = $teamsOrdered->values()->pluck('id')->map(fn (int|string $id): int => (int) $id)->all();

        /** @var list<list<array{0: int, 1: int}>> $weeks */
        $weeks = [
            [[0, 1], [2, 3]],
            [[2, 0], [3, 1]],
            [[0, 3], [1, 2]],
            [[1, 0], [3, 2]],
            [[0, 2], [1, 3]],
            [[3, 0], [2, 1]],
        ];

        $fixtures = [];
        foreach ($weeks as $weekIndex => $pairs) {
            $matchweek = $weekIndex + 1;
            foreach ($pairs as $pair) {
                $fixtures[] = [
                    'matchweek' => $matchweek,
                    'home_team_id' => $ids[$pair[0]],
                    'away_team_id' => $ids[$pair[1]],
                ];
            }
        }

        return $fixtures;
    }
}
