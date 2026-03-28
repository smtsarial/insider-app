<?php

namespace App\League\Prediction;

use App\Models\Team;
use Illuminate\Support\Collection;

class TitlePredictionSnapshotService
{
    /**
     * @param  list<array<string, mixed>>|null  $snapshots
     * @param  array<int, float>  $percentages
     * @param  list<array{team_id: int, played: int, won: int, drawn: int, lost: int, gf: int, ga: int, gd: int, points: int}>  $standingsRows
     * @return list<array<string, mixed>>
     */
    public function upsert(?array $snapshots, int $afterMatchweek, array $percentages, array $standingsRows): array
    {
        /** @var list<array<string, mixed>> $list */
        $list = array_values(array_filter(
            $snapshots ?? [],
            fn (array $s): bool => (int) $s['after_matchweek'] !== $afterMatchweek,
        ));

        $normalized = [];
        foreach ($percentages as $teamId => $p) {
            $normalized[(string) $teamId] = round((float) $p, 1);
        }

        $list[] = [
            'after_matchweek' => $afterMatchweek,
            'percentages' => $normalized,
            'standings' => array_values($standingsRows),
        ];

        usort($list, fn (array $a, array $b): int => (int) $a['after_matchweek'] <=> (int) $b['after_matchweek']);

        return $list;
    }

    /**
     * @param  list<array<string, mixed>>|null  $snapshots
     * @return array<int, float>|null
     */
    public function findPercentages(?array $snapshots, int $afterMatchweek): ?array
    {
        $row = $this->findSnapshot($snapshots, $afterMatchweek);
        if ($row === null) {
            return null;
        }

        $out = [];
        foreach ($row['percentages'] as $tid => $p) {
            $out[(int) $tid] = (float) $p;
        }

        return $out;
    }

    /**
     * @param  list<array<string, mixed>>|null  $snapshots
     * @return list<array{team_id: int, played: int, won: int, drawn: int, lost: int, gf: int, ga: int, gd: int, points: int}>|null
     */
    public function findStandings(?array $snapshots, int $afterMatchweek): ?array
    {
        $row = $this->findSnapshot($snapshots, $afterMatchweek);
        if ($row === null || ! isset($row['standings'])) {
            return null;
        }

        /** @var list<array<string, int>> $raw */
        $raw = $row['standings'];
        $out = [];
        foreach ($raw as $line) {
            $out[] = [
                'team_id' => (int) $line['team_id'],
                'played' => (int) $line['played'],
                'won' => (int) $line['won'],
                'drawn' => (int) $line['drawn'],
                'lost' => (int) $line['lost'],
                'gf' => (int) $line['gf'],
                'ga' => (int) $line['ga'],
                'gd' => (int) $line['gd'],
                'points' => (int) $line['points'],
            ];
        }

        return $out;
    }

    /**
     * @param  list<array<string, mixed>>|null  $snapshots
     * @return array<string, mixed>|null
     */
    public function findSnapshot(?array $snapshots, int $afterMatchweek): ?array
    {
        if ($snapshots === null) {
            return null;
        }

        foreach ($snapshots as $row) {
            if ((int) $row['after_matchweek'] === $afterMatchweek) {
                return $row;
            }
        }

        return null;
    }

    /**
     * @param  list<array<string, mixed>>|null  $snapshots
     * @return array{favorite_hits: int, favorite_total: int, rate_percent: float|null, champion_team_ids: list<int>}|null
     */
    public function favoriteVersusChampionTrackRecord(?array $snapshots, array $championTeamIds): ?array
    {
        if ($snapshots === null || $championTeamIds === []) {
            return null;
        }

        $hits = 0;
        $total = 0;

        foreach ($snapshots as $row) {
            $mw = (int) $row['after_matchweek'];
            if ($mw < 0 || $mw > 5) {
                continue;
            }

            $p = [];
            foreach ($row['percentages'] as $tid => $val) {
                $p[(int) $tid] = (float) $val;
            }

            if ($p === []) {
                continue;
            }

            $favorites = $this->favoriteTeamIds($p);
            $total++;

            if (count(array_intersect($favorites, $championTeamIds)) > 0) {
                $hits++;
            }
        }

        if ($total === 0) {
            return null;
        }

        return [
            'favorite_hits' => $hits,
            'favorite_total' => $total,
            'rate_percent' => round(100.0 * $hits / $total, 1),
            'champion_team_ids' => array_values(array_unique($championTeamIds)),
        ];
    }

    /**
     * @param  array<int, float>  $percentages
     * @return list<int>
     */
    public function favoriteTeamIds(array $percentages): array
    {
        if ($percentages === []) {
            return [];
        }

        $max = max($percentages);
        $ids = [];

        foreach ($percentages as $teamId => $p) {
            if (abs($p - $max) < 0.0001) {
                $ids[] = (int) $teamId;
            }
        }

        return $ids;
    }

    /**
     * @param  Collection<int, Team>  $teams
     * @param  list<array<string, mixed>>|null  $snapshots
     * @return array{after_matchweeks: list<int>, rows: list<array{team: array{id: int, name: string, short_name: string, power: int}, points: list<int|null>, percentages: list<float|null>}>}
     */
    public function buildHistoryGrid(Collection $teams, ?array $snapshots, callable $teamPayload): array
    {
        if ($snapshots === null || $snapshots === []) {
            return ['after_matchweeks' => [], 'rows' => []];
        }

        $sorted = $snapshots;
        usort($sorted, fn (array $a, array $b): int => (int) $a['after_matchweek'] <=> (int) $b['after_matchweek']);
        $afterWeeks = array_values(array_unique(array_map(fn (array $s): int => (int) $s['after_matchweek'], $sorted)));
        sort($afterWeeks);

        $pctLookup = [];
        $ptsLookup = [];
        foreach ($sorted as $row) {
            $mw = (int) $row['after_matchweek'];
            $pctLookup[$mw] = $row['percentages'];
            if (isset($row['standings'])) {
                $byId = [];
                foreach ($row['standings'] as $line) {
                    $byId[(int) $line['team_id']] = (int) $line['points'];
                }
                $ptsLookup[$mw] = $byId;
            }
        }

        $rows = [];
        foreach ($teams->sortBy('sort_order')->values() as $team) {
            /** @var Team $team */
            $percents = [];
            $points = [];
            foreach ($afterWeeks as $mw) {
                $cellPct = $pctLookup[$mw][(string) $team->id] ?? $pctLookup[$mw][$team->id] ?? null;
                $percents[] = $cellPct !== null ? (float) $cellPct : null;
                $cellPts = $ptsLookup[$mw][$team->id] ?? null;
                $points[] = $cellPts;
            }
            $rows[] = [
                'team' => $teamPayload($team),
                'points' => $points,
                'percentages' => $percents,
            ];
        }

        return [
            'after_matchweeks' => $afterWeeks,
            'rows' => $rows,
        ];
    }
}
