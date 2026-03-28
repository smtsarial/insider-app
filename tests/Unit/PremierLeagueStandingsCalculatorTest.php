<?php

use App\League\Fixtures\RoundRobinFixtureGenerator;
use App\League\Standings\PremierLeagueStandingsCalculator;
use App\Models\LeagueMatch;
use App\Models\Team;
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

test('breaks full tie on head to head then squad rating', function () {
    $tA = makeTeam(1, 'Alpha');
    $tA->power = 92;
    $tB = makeTeam(2, 'Beta');
    $tB->power = 71;

    $teams = collect([$tA, $tB]);

    $m1 = LeagueMatch::make([
        'home_team_id' => 1,
        'away_team_id' => 2,
        'home_goals' => 1,
        'away_goals' => 1,
        'status' => 'finished',
    ]);
    $m1->setRelation('homeTeam', $tA);
    $m1->setRelation('awayTeam', $tB);

    $m2 = LeagueMatch::make([
        'home_team_id' => 2,
        'away_team_id' => 1,
        'home_goals' => 1,
        'away_goals' => 1,
        'status' => 'finished',
    ]);
    $m2->setRelation('homeTeam', $tB);
    $m2->setRelation('awayTeam', $tA);

    $calc = new PremierLeagueStandingsCalculator;
    $table = $calc->calculate($teams, collect([$m1, $m2]));

    expect($table->first()['team']->id)->toBe(1)
        ->and($table->first()['points'])->toBe($table->last()['points']);
});

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

test('round robin generator shuffle slots changes week one pairings versus sort order', function () {
    $gen = new RoundRobinFixtureGenerator;
    $teams = collect([
        makeTeam(10, 'Arsenal', 1),
        makeTeam(20, 'Liverpool', 2),
        makeTeam(30, 'Manchester City', 3),
        makeTeam(40, 'Chelsea', 4),
    ])->sortBy('sort_order');

    $ordered = $gen->generate($teams, shuffleSlots: false);
    $shuffled = $gen->generate($teams, shuffleSlots: true, shuffleSeed: 12345);

    $weekOneOrdered = collect($ordered)
        ->where('matchweek', 1)
        ->map(fn (array $r): string => min($r['home_team_id'], $r['away_team_id']).'-'.max($r['home_team_id'], $r['away_team_id']))
        ->sort()
        ->values()
        ->all();
    $weekOneShuffled = collect($shuffled)
        ->where('matchweek', 1)
        ->map(fn (array $r): string => min($r['home_team_id'], $r['away_team_id']).'-'.max($r['home_team_id'], $r['away_team_id']))
        ->sort()
        ->values()
        ->all();

    expect($weekOneOrdered)->toBe(['10-20', '30-40'])
        ->and($weekOneShuffled)->not->toBe($weekOneOrdered)
        ->and($shuffled)->toHaveCount(12);
});
