<?php

use App\Models\LeagueMatch;
use App\Models\Team;
use App\Services\League\ChampionshipProbabilityEstimator;
use App\Services\League\MatchSimulationEngine;
use App\Services\League\PremierLeagueStandingsCalculator;
use App\Services\League\TeamFormAnalyzer;
use Tests\TestCase;

uses(TestCase::class);

function estTeam(int $id, string $name): Team
{
    $t = new Team;
    $t->forceFill([
        'id' => $id,
        'name' => $name,
        'short_name' => substr($name, 0, 3),
        'power' => 80,
        'sort_order' => $id,
        'supporter_factor' => 10,
        'goalkeeper_factor' => 10,
    ]);
    $t->syncOriginal();

    return $t;
}

function estMatch(
    Team $home,
    Team $away,
    int $week,
    string $status,
    ?int $hg,
    ?int $ag,
): LeagueMatch {
    $m = LeagueMatch::make([
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
        'matchweek' => $week,
        'home_goals' => $hg,
        'away_goals' => $ag,
        'status' => $status,
    ]);
    $m->setRelation('homeTeam', $home);
    $m->setRelation('awayTeam', $away);

    return $m;
}

test('mathematical clinch assigns 100 percent to uncatchable leader', function () {
    $t1 = estTeam(1, 'Alpha');
    $t2 = estTeam(2, 'Beta');
    $t3 = estTeam(3, 'Gamma');
    $t4 = estTeam(4, 'Delta');
    $teams = collect([$t1, $t2, $t3, $t4]);

    $finished = collect([
        estMatch($t1, $t2, 1, 'finished', 2, 0),
        estMatch($t1, $t3, 1, 'finished', 2, 0),
        estMatch($t1, $t4, 1, 'finished', 2, 0),
        estMatch($t2, $t1, 2, 'finished', 0, 2),
        estMatch($t3, $t1, 2, 'finished', 0, 2),
        estMatch($t4, $t1, 2, 'finished', 0, 2),
    ]);

    $scheduled = collect([
        estMatch($t2, $t3, 3, 'scheduled', null, null),
        estMatch($t2, $t4, 3, 'scheduled', null, null),
        estMatch($t3, $t4, 4, 'scheduled', null, null),
        estMatch($t3, $t2, 4, 'scheduled', null, null),
        estMatch($t4, $t2, 5, 'scheduled', null, null),
        estMatch($t4, $t3, 5, 'scheduled', null, null),
    ]);

    $all = $finished->merge($scheduled);

    $estimator = new ChampionshipProbabilityEstimator(
        new MatchSimulationEngine,
        new PremierLeagueStandingsCalculator,
        new TeamFormAnalyzer,
    );

    $pct = $estimator->estimate($teams, $all, iterations: 100, baseSeed: 1);

    expect($pct[1])->toBe(100.0)
        ->and($pct[2])->toBe(0.0)
        ->and($pct[3])->toBe(0.0)
        ->and($pct[4])->toBe(0.0);
});

test('finished season awards full title percent to table leader when points gd gf are tied', function () {
    $t1 = estTeam(1, 'Alpha');
    $t1->power = 94;
    $t2 = estTeam(2, 'Beta');
    $t2->power = 68;
    $teams = collect([$t1, $t2]);

    $finished = collect([
        estMatch($t1, $t2, 1, 'finished', 1, 1),
        estMatch($t2, $t1, 2, 'finished', 1, 1),
    ]);

    $estimator = new ChampionshipProbabilityEstimator(
        new MatchSimulationEngine,
        new PremierLeagueStandingsCalculator,
        new TeamFormAnalyzer,
    );

    $pct = $estimator->estimate($teams, $finished, iterations: 50, baseSeed: 1);

    expect($pct[1])->toBe(100.0)
        ->and($pct[2])->toBe(0.0);
});

test('monte carlo splits iterations when simulated final table ties on points gd and gf', function () {
    $t1 = estTeam(1, 'Alpha');
    $t1->power = 80;
    $t2 = estTeam(2, 'Beta');
    $t2->power = 80;
    $t3 = estTeam(3, 'Gamma');
    $t3->power = 80;
    $t4 = estTeam(4, 'Delta');
    $t4->power = 80;
    $teams = collect([$t1, $t2, $t3, $t4]);

    $matches = collect([
        estMatch($t1, $t2, 1, 'finished', 1, 0),
        estMatch($t3, $t4, 1, 'finished', 0, 1),
        estMatch($t1, $t3, 2, 'finished', 0, 1),
        estMatch($t2, $t4, 2, 'finished', 1, 0),
        estMatch($t1, $t4, 3, 'finished', 1, 0),
        estMatch($t2, $t3, 3, 'finished', 0, 1),
        estMatch($t2, $t1, 4, 'scheduled', null, null),
        estMatch($t4, $t3, 4, 'scheduled', null, null),
        estMatch($t3, $t1, 5, 'scheduled', null, null),
        estMatch($t4, $t2, 5, 'scheduled', null, null),
        estMatch($t4, $t1, 6, 'scheduled', null, null),
        estMatch($t3, $t2, 6, 'scheduled', null, null),
    ]);

    $estimator = new ChampionshipProbabilityEstimator(
        new MatchSimulationEngine,
        new PremierLeagueStandingsCalculator,
        new TeamFormAnalyzer,
    );

    $pct = $estimator->estimate($teams, $matches, iterations: 4000, baseSeed: 99);
    $max = max($pct);

    expect($max)->toBeLessThan(95.0);
});

test('close race uses monte carlo so two teams may share positive title percent', function () {
    $t1 = estTeam(1, 'Alpha');
    $t2 = estTeam(2, 'Beta');
    $t3 = estTeam(3, 'Gamma');
    $t4 = estTeam(4, 'Delta');
    $teams = collect([$t1, $t2, $t3, $t4]);

    $matches = collect([
        estMatch($t1, $t2, 1, 'finished', 0, 0),
        estMatch($t3, $t4, 1, 'finished', 0, 0),
        estMatch($t1, $t3, 2, 'finished', 0, 0),
        estMatch($t2, $t4, 2, 'finished', 0, 0),
        estMatch($t1, $t4, 3, 'finished', 0, 0),
        estMatch($t2, $t3, 3, 'finished', 0, 0),
        estMatch($t2, $t1, 4, 'scheduled', null, null),
        estMatch($t4, $t3, 4, 'scheduled', null, null),
        estMatch($t3, $t1, 5, 'scheduled', null, null),
        estMatch($t4, $t2, 5, 'scheduled', null, null),
        estMatch($t4, $t1, 6, 'scheduled', null, null),
        estMatch($t3, $t2, 6, 'scheduled', null, null),
    ]);

    $estimator = new ChampionshipProbabilityEstimator(
        new MatchSimulationEngine,
        new PremierLeagueStandingsCalculator,
        new TeamFormAnalyzer,
    );

    $pct = $estimator->estimate($teams, $matches, iterations: 2000, baseSeed: 42);
    $positive = collect($pct)->filter(fn (float $v): bool => $v > 2.0)->count();

    expect($positive)->toBeGreaterThan(1);
});
