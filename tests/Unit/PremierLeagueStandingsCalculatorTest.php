<?php

use App\Models\LeagueMatch;
use App\Models\Team;
use App\Services\League\MatchSimulationEngine;
use App\Services\League\PremierLeagueStandingsCalculator;
use App\Services\League\RoundRobinFixtureGenerator;
use Tests\TestCase;

uses(TestCase::class);

function makeTeam(int $id, string $name, int $sortOrder = 0): Team
{
    $t = new Team;
    $t->forceFill([
        'id' => $id,
        'name' => $name,
        'short_name' => substr($name, 0, 3),
        'power' => 80,
        'sort_order' => $sortOrder,
        'supporter_factor' => 10,
        'goalkeeper_factor' => 10,
    ]);
    $t->syncOriginal();

    return $t;
}

test('orders teams by points then goal difference', function () {
    $tA = makeTeam(1, 'Alpha');
    $tB = makeTeam(2, 'Beta');

    $teams = collect([$tA, $tB]);

    $m1 = LeagueMatch::make([
        'home_team_id' => 1,
        'away_team_id' => 2,
        'home_goals' => 3,
        'away_goals' => 0,
        'status' => 'finished',
    ]);
    $m1->setRelation('homeTeam', $tA);
    $m1->setRelation('awayTeam', $tB);

    $calc = new PremierLeagueStandingsCalculator;
    $table = $calc->calculate($teams, collect([$m1]));

    expect($table->first()['team']->id)->toBe(1)
        ->and($table->first()['points'])->toBe(3);
});

test('match simulation engine is deterministic with seed', function () {
    $engine = new MatchSimulationEngine;
    $home = makeTeam(1, 'Home');
    $away = makeTeam(2, 'Away');

    $a = $engine->simulate($home, $away, 42_001);
    $b = $engine->simulate($home, $away, 42_001);

    expect($a)->toBe($b);
});

test('round robin generator yields twelve fixtures', function () {
    $gen = new RoundRobinFixtureGenerator;
    $teams = collect([
        makeTeam(10, 'Arsenal', 1),
        makeTeam(20, 'Liverpool', 2),
        makeTeam(30, 'Manchester City', 3),
        makeTeam(40, 'Chelsea', 4),
    ])->sortBy('sort_order');

    $rows = $gen->generate($teams);

    expect($rows)->toHaveCount(12);
    expect(collect($rows)->pluck('matchweek')->unique()->sort()->values()->all())->toBe([1, 2, 3, 4, 5, 6]);
});
