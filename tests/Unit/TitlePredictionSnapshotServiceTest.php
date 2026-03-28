<?php

use App\Models\LeagueMatch;
use App\Models\Team;
use App\Services\League\PremierLeagueStandingsCalculator;
use App\Services\League\TitlePredictionSnapshotService;
use Tests\TestCase;

uses(TestCase::class);

test('title prediction snapshot upsert replaces same matchweek', function () {
    $svc = new TitlePredictionSnapshotService;

    $standings = [
        ['team_id' => 1, 'played' => 0, 'won' => 0, 'drawn' => 0, 'lost' => 0, 'gf' => 0, 'ga' => 0, 'gd' => 0, 'points' => 0],
        ['team_id' => 2, 'played' => 0, 'won' => 0, 'drawn' => 0, 'lost' => 0, 'gf' => 0, 'ga' => 0, 'gd' => 0, 'points' => 0],
    ];

    $a = $svc->upsert(null, 0, [1 => 25.0, 2 => 25.0], $standings);
    $b = $svc->upsert($a, 0, [1 => 40.0, 2 => 20.0], $standings);

    expect($b)->toHaveCount(1)
        ->and($b[0]['after_matchweek'])->toBe(0)
        ->and($b[0]['percentages']['1'])->toBe(40.0)
        ->and($b[0]['standings'])->toHaveCount(2);
});

test('favorite versus champion track record counts snapshots after matchweek zero through five', function () {
    $svc = new TitlePredictionSnapshotService;

    $snapshots = [
        ['after_matchweek' => 0, 'percentages' => ['10' => 50.0, '20' => 30.0]],
        ['after_matchweek' => 1, 'percentages' => ['10' => 10.0, '20' => 60.0]],
        ['after_matchweek' => 6, 'percentages' => ['10' => 100.0, '20' => 0.0]],
    ];

    $result = $svc->favoriteVersusChampionTrackRecord($snapshots, [20]);

    expect($result)->not->toBeNull()
        ->and($result['favorite_total'])->toBe(2)
        ->and($result['favorite_hits'])->toBe(1)
        ->and($result['rate_percent'])->toBe(50.0);
});

test('first place team ids resolves single winner', function () {
    $tA = new Team;
    $tA->forceFill(['id' => 1, 'name' => 'A', 'short_name' => 'A', 'power' => 80, 'sort_order' => 1, 'supporter_factor' => 10, 'goalkeeper_factor' => 10]);
    $tA->syncOriginal();
    $tB = new Team;
    $tB->forceFill(['id' => 2, 'name' => 'B', 'short_name' => 'B', 'power' => 80, 'sort_order' => 2, 'supporter_factor' => 10, 'goalkeeper_factor' => 10]);
    $tB->syncOriginal();

    $m = LeagueMatch::make([
        'home_team_id' => 1,
        'away_team_id' => 2,
        'home_goals' => 1,
        'away_goals' => 0,
        'status' => 'finished',
    ]);
    $m->setRelation('homeTeam', $tA);
    $m->setRelation('awayTeam', $tB);

    $calc = new PremierLeagueStandingsCalculator;
    $ids = $calc->firstPlaceTeamIds(collect([$tA, $tB]), collect([$m]));

    expect($ids)->toBe([1]);
});
