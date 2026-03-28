<?php

namespace App\Services\League;

use App\Models\Team;
use Illuminate\Support\Collection;
use Random\Engine\Mt19937;
use Random\Randomizer;

class RoundRobinFixtureGenerator
{
    /**
     * @param  Collection<int, Team>  $teamsOrdered  Clubs in draw order (sort_order) when not shuffling
     * @param  bool  $shuffleSlots  When true, assigns the four teams to template slots at random (new draw)
     * @param  int|null  $shuffleSeed  Optional seed for reproducible tests; only used when $shuffleSlots is true
     * @return list<array{matchweek: int, home_team_id: int, away_team_id: int}>
     */
    public function generate(Collection $teamsOrdered, bool $shuffleSlots = false, ?int $shuffleSeed = null): array
    {
        if ($teamsOrdered->count() !== 4) {
            throw new \InvalidArgumentException('Exactly four teams are required.');
        }

        $ordered = $this->teamsInDrawOrder($teamsOrdered, $shuffleSlots, $shuffleSeed);

        /** @var list<int> $ids */
        $ids = $ordered->pluck('id')->map(fn (int|string $id): int => (int) $id)->all();

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

        $this->assertValidDoubleRoundRobin($ids, $fixtures);

        return $fixtures;
    }

    /**
     * @param  Collection<int, Team>  $teamsOrdered
     * @return Collection<int, Team>
     */
    private function teamsInDrawOrder(Collection $teamsOrdered, bool $shuffleSlots, ?int $shuffleSeed): Collection
    {
        /** @var list<Team> $arr */
        $arr = $teamsOrdered->values()->all();

        if (! $shuffleSlots) {
            return collect($arr);
        }

        if ($shuffleSeed !== null) {
            $randomizer = new Randomizer(new Mt19937($shuffleSeed));
            for ($i = count($arr) - 1; $i > 0; $i--) {
                $j = $randomizer->getInt(0, $i);
                [$arr[$i], $arr[$j]] = [$arr[$j], $arr[$i]];
            }

            return collect($arr);
        }

        shuffle($arr);

        return collect($arr);
    }

    /**
     * @param  list<int>  $teamIds
     * @param  list<array{matchweek: int, home_team_id: int, away_team_id: int}>  $fixtures
     */
    private function assertValidDoubleRoundRobin(array $teamIds, array $fixtures): void
    {
        if (count($fixtures) !== 12) {
            throw new \RuntimeException('Double round-robin for four teams must produce 12 fixtures.');
        }

        $byWeek = [];
        foreach ($fixtures as $f) {
            $byWeek[$f['matchweek']] = ($byWeek[$f['matchweek']] ?? 0) + 1;
        }
        foreach (range(1, 6) as $w) {
            if (($byWeek[$w] ?? 0) !== 2) {
                throw new \RuntimeException("Matchweek {$w} must have exactly two fixtures.");
            }
        }

        $homeCount = array_fill_keys($teamIds, 0);
        $awayCount = array_fill_keys($teamIds, 0);
        $pairKeySeen = [];

        foreach ($fixtures as $f) {
            $homeCount[$f['home_team_id']]++;
            $awayCount[$f['away_team_id']]++;

            $a = min($f['home_team_id'], $f['away_team_id']);
            $b = max($f['home_team_id'], $f['away_team_id']);
            $k = $a.','.$b;
            $pairKeySeen[$k] = ($pairKeySeen[$k] ?? 0) + 1;
        }

        foreach ($teamIds as $id) {
            if ($homeCount[$id] !== 3 || $awayCount[$id] !== 3) {
                throw new \RuntimeException('Each team must play 3 home and 3 away matches.');
            }
        }

        foreach ($teamIds as $idA) {
            foreach ($teamIds as $idB) {
                if ($idA >= $idB) {
                    continue;
                }
                $k = min($idA, $idB).','.max($idA, $idB);
                if (($pairKeySeen[$k] ?? 0) !== 2) {
                    throw new \RuntimeException('Each pair of teams must meet exactly twice.');
                }
            }
        }
    }
}
