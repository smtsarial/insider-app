<?php

use App\Models\LeagueMatch;
use App\Models\LeagueState;
use App\Models\Team;
use Database\Seeders\LeagueSeeder;

test('tournament teams page loads', function () {
    $this->seed(LeagueSeeder::class);

    $this->get(route('league.tournament-teams'))->assertOk();
});

test('generate fixtures creates twelve matches and advances step', function () {
    $this->seed(LeagueSeeder::class);

    $this->post(route('league.fixtures.store'))->assertRedirect(route('league.fixtures'));

    expect(LeagueMatch::query()->count())->toBe(12)
        ->and(LeagueState::current()->step)->toBe('fixtures');
});

test('week one fixtures match canonical schedule', function () {
    $this->seed(LeagueSeeder::class);
    $this->post(route('league.fixtures.store'));

    $w1 = LeagueMatch::query()->where('matchweek', 1)->with(['homeTeam', 'awayTeam'])->get();

    expect($w1)->toHaveCount(2);

    $pairings = $w1->map(fn (LeagueMatch $m): string => $m->homeTeam->name.'|'.$m->awayTeam->name)->sort()->values()->all();

    expect($pairings)->toBe([
        'Arsenal|Liverpool',
        'Manchester City|Chelsea',
    ]);
});

test('start simulation redirects to simulation step', function () {
    $this->seed(LeagueSeeder::class);
    $this->post(route('league.fixtures.store'));
    $this->post(route('league.simulation.start'))->assertRedirect(route('league.simulation'));

    expect(LeagueState::current()->step)->toBe('simulation');
});

test('play next week simulates one matchweek', function () {
    $this->seed(LeagueSeeder::class);
    $this->post(route('league.fixtures.store'));
    $this->post(route('league.simulation.start'));

    $this->post(route('league.simulation.play-next'))->assertRedirect(route('league.simulation'));

    expect(LeagueMatch::query()->where('matchweek', 1)->where('status', 'finished')->count())->toBe(2)
        ->and(LeagueMatch::query()->where('status', 'scheduled')->count())->toBe(10);
});

test('reset clears matches and returns to teams step', function () {
    $this->seed(LeagueSeeder::class);
    $this->post(route('league.fixtures.store'));
    $this->post(route('league.reset'));

    expect(LeagueMatch::query()->count())->toBe(0)
        ->and(LeagueState::current()->step)->toBe('teams');
});
