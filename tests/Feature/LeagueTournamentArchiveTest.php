<?php

use App\Models\LeagueTournamentArchive;
use Database\Seeders\LeagueSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('reset archives tournament when at least one match was played', function () {
    $this->seed(LeagueSeeder::class);
    $this->post(route('league.fixtures.store'), leagueTeamsFormPayload());
    $this->post(route('league.simulation.start'));
    $this->post(route('league.simulation.play-next'));

    expect(LeagueTournamentArchive::query()->count())->toBe(0);

    $this->post(route('league.reset'))->assertRedirect(route('home'));

    expect(LeagueTournamentArchive::query()->count())->toBe(1);
    $row = LeagueTournamentArchive::query()->first();
    expect($row)->not->toBeNull()
        ->and($row->was_completed)->toBeFalse()
        ->and($row->finished_matches_count)->toBe(2)
        ->and($row->champion_team_id)->toBeNull();
});

test('reset after full season archives completed tournament with champion', function () {
    $this->seed(LeagueSeeder::class);
    $this->post(route('league.fixtures.store'), leagueTeamsFormPayload());
    $this->post(route('league.simulation.start'));
    $this->post(route('league.simulation.play-all'));
    $this->post(route('league.reset'));

    $row = LeagueTournamentArchive::query()->first();
    expect($row)->not->toBeNull()
        ->and($row->was_completed)->toBeTrue()
        ->and($row->finished_matches_count)->toBe(12)
        ->and($row->champion_team_id)->not->toBeNull();

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('tournamentHistory.0.standings_detail', 4)
            ->where('tournamentHistory.0.standings_detail.0.team_name', fn (string $n): bool => $n !== '')
            ->has('tournamentHistory.0.prediction_snapshots'));
});

test('league pages expose tournament history list', function () {
    $this->seed(LeagueSeeder::class);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('tournamentHistory'));
});
