<?php

use App\League\Simulation\MatchSimulationEngine;
use App\Models\Team;
use Tests\TestCase;

uses(TestCase::class);

function simulationTeam(int $id, int $power, int $supporter = 10, int $goalkeeper = 10): Team
{
    $t = new Team;
    $t->forceFill([
        'id' => $id,
        'name' => 'T'.$id,
        'short_name' => 'T'.$id,
        'power' => $power,
        'sort_order' => $id,
        'supporter_factor' => $supporter,
        'goalkeeper_factor' => $goalkeeper,
    ]);
    $t->syncOriginal();

    return $t;
}

test('match simulation engine is deterministic with seed', function () {
    $engine = new MatchSimulationEngine;
    $home = simulationTeam(1, 80);
    $away = simulationTeam(2, 80);

    $a = $engine->simulate($home, $away, 42_001);
    $b = $engine->simulate($home, $away, 42_001);

    expect($a)->toBe($b);
});

test('heavy home favourite wins most matches but away upsets stay possible', function () {
    $engine = new MatchSimulationEngine;
    $home = simulationTeam(1, 100);
    $away = simulationTeam(2, 10);

    $awayWins = 0;
    $draws = 0;
    for ($s = 0; $s < 4000; $s++) {
        [$h, $a] = $engine->simulate($home, $away, $s);
        if ($a > $h) {
            $awayWins++;
        }
        if ($h === $a) {
            $draws++;
        }
    }

    expect($awayWins)->toBeGreaterThan(0)
        ->and($awayWins)->toBeLessThan(200)
        ->and($draws)->toBeLessThan(700);
});

test('strong away side wins majority at weak home ground', function () {
    $engine = new MatchSimulationEngine;
    $home = simulationTeam(1, 12);
    $away = simulationTeam(2, 95);

    $awayWins = 0;
    for ($s = 0; $s < 3000; $s++) {
        [$h, $a] = $engine->simulate($home, $away, 10_000 + $s);
        if ($a > $h) {
            $awayWins++;
        }
    }

    expect($awayWins)->toBeGreaterThan(1800);
});
