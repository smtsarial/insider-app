<?php

use App\Models\LeagueMatch;
use App\Models\LeagueState;
use App\Models\LeagueTournamentArchive;
use App\Models\Team;
use Database\Seeders\LeagueSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('tournament teams route redirects to home hub', function () {
    $this->seed(LeagueSeeder::class);

    $this->get(route('league.tournament-teams'))
        ->assertRedirect(route('home'));

    $this->get(route('home'))->assertOk();
});

test('generate fixtures creates twelve matches and advances step', function () {
    $this->seed(LeagueSeeder::class);

    $this->post(route('league.fixtures.store'), leagueTeamsFormPayload())->assertRedirect(route('league.fixtures'));

    expect(LeagueMatch::query()->count())->toBe(12)
        ->and(LeagueState::current()->step)->toBe('fixtures');
});

test('week one fixtures match canonical schedule', function () {
    $this->seed(LeagueSeeder::class);
    $this->post(route('league.fixtures.store'), leagueTeamsFormPayload());

    $w1 = LeagueMatch::query()->where('matchweek', 1)->with(['homeTeam', 'awayTeam'])->get();

    expect($w1)->toHaveCount(2);

    $pairings = $w1->map(fn (LeagueMatch $m): string => $m->homeTeam->name.'|'.$m->awayTeam->name)->sort()->values()->all();

    expect($pairings)->toBe([
        'Paris Saint-Germain|Inter Milan',
        'Real Madrid|Bayern Munich',
    ]);
});

test('storing fixtures from clubs step persists edited teams before generating matches', function () {
    $this->seed(LeagueSeeder::class);
    $base = leagueTeamsFormPayload();
    $base['teams'][0]['name'] = 'Club Atlético Demo';
    $base['teams'][0]['power'] = 88;

    $editedId = (int) $base['teams'][0]['id'];

    $this->post(route('league.fixtures.store'), $base)->assertRedirect(route('league.fixtures'));

    $team = Team::query()->findOrFail($editedId);
    expect($team->name)->toBe('Club Atlético Demo')
        ->and($team->power)->toBe(88)
        ->and(
            LeagueMatch::query()->where('matchweek', 1)->where(function ($q) use ($editedId) {
                $q->where('home_team_id', $editedId)->orWhere('away_team_id', $editedId);
            })->exists(),
        )->toBeTrue();
});

test('storing fixtures again from fixtures step rebuilds pairings for current club order', function () {
    $this->seed(LeagueSeeder::class);
    $this->post(route('league.fixtures.store'), leagueTeamsFormPayload());

    $t3 = Team::query()->where('sort_order', 3)->firstOrFail();
    $t4 = Team::query()->where('sort_order', 4)->firstOrFail();
    $t3->update(['sort_order' => 4]);
    $t4->update(['sort_order' => 3]);

    // Send clubs payload so the schedule follows the updated draw order (no random slot shuffle).
    $this->post(route('league.fixtures.store'), leagueTeamsFormPayload())->assertRedirect(route('league.fixtures'));

    $w1 = LeagueMatch::query()->where('matchweek', 1)->with(['homeTeam', 'awayTeam'])->get();
    $pairings = $w1->map(fn (LeagueMatch $m): string => $m->homeTeam->name.'|'.$m->awayTeam->name)->sort()->values()->all();

    expect($pairings)->toBe([
        'Inter Milan|Paris Saint-Germain',
        'Real Madrid|Bayern Munich',
    ])
        ->and(LeagueMatch::query()->count())->toBe(12);
});

test('storing fixtures is ignored during simulation step', function () {
    $this->seed(LeagueSeeder::class);
    $this->post(route('league.fixtures.store'), leagueTeamsFormPayload());
    $this->post(route('league.simulation.start'));

    $before = LeagueMatch::query()->orderBy('id')->pluck('home_team_id', 'id')->all();
    $this->post(route('league.fixtures.store'))->assertRedirect(route('league.simulation'));

    expect(LeagueMatch::query()->orderBy('id')->pluck('home_team_id', 'id')->all())->toBe($before);
});

test('start simulation redirects to simulation step', function () {
    $this->seed(LeagueSeeder::class);
    $this->post(route('league.fixtures.store'), leagueTeamsFormPayload());
    $this->post(route('league.simulation.start'))->assertRedirect(route('league.simulation'));

    expect(LeagueState::current()->step)->toBe('simulation');
});

test('predictions are inactive before week 4 and active from week 4 onward', function () {
    $this->seed(LeagueSeeder::class);
    $this->post(route('league.fixtures.store'), leagueTeamsFormPayload());
    $this->post(route('league.simulation.start'));

    // No matchweeks played yet — predictions should be off (last 3 weeks rule)
    $this->get(route('league.simulation'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('predictionActive', false)
            ->where('lastCompletedMatchweek', 0)
            ->where('standings.0.title_percent', null));

    // Play 3 weeks to enter the last 3 (weeks 4–6)
    $this->post(route('league.simulation.play-next'));
    $this->post(route('league.simulation.play-next'));
    $this->post(route('league.simulation.play-next'));

    $this->get(route('league.simulation'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('predictionActive', true)
            ->where('lastCompletedMatchweek', 3)
            ->has('standings.0.title_percent'));
});

test('full season play all stays on simulation with predictions until reset archives', function () {
    $this->seed(LeagueSeeder::class);
    $this->post(route('league.fixtures.store'), leagueTeamsFormPayload());
    $this->post(route('league.simulation.start'));
    $this->post(route('league.simulation.play-all'))->assertRedirect(route('league.simulation'));

    expect(LeagueMatch::query()->where('status', 'finished')->count())->toBe(12)
        ->and(LeagueState::current()->step)->toBe('simulation')
        ->and(LeagueTournamentArchive::query()->count())->toBe(0);

    $this->get(route('league.simulation'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('seasonComplete', true)
            ->has('predictionHistoryGrid.after_matchweeks', 7)
            ->has('predictionTrackRecord')
            ->where('predictionTrackRecord.favorite_total', 6)
            ->where('currentViewWeek', 6)
            ->has('tournamentHistory'));

    $this->post(route('league.reset'))->assertRedirect(route('home'));

    $archive = LeagueTournamentArchive::query()->first();
    expect($archive)->not->toBeNull()
        ->and($archive->was_completed)->toBeTrue()
        ->and($archive->finished_matches_count)->toBe(12);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('tournamentHistory.0.prediction_snapshots'));
});

test('six play-next requests stay on simulation with no archive until reset', function () {
    $this->seed(LeagueSeeder::class);
    $this->post(route('league.fixtures.store'), leagueTeamsFormPayload());
    $this->post(route('league.simulation.start'));

    for ($i = 0; $i < 6; $i++) {
        $this->post(route('league.simulation.play-next'))->assertRedirect(route('league.simulation'));
    }

    expect(LeagueMatch::query()->where('status', 'finished')->count())->toBe(12)
        ->and(LeagueTournamentArchive::query()->count())->toBe(0);
});

test('play next week simulates one matchweek', function () {
    $this->seed(LeagueSeeder::class);
    $this->post(route('league.fixtures.store'), leagueTeamsFormPayload());
    $this->post(route('league.simulation.start'));

    $this->post(route('league.simulation.play-next'))->assertRedirect(route('league.simulation'));

    expect(LeagueMatch::query()->where('matchweek', 1)->where('status', 'finished')->count())->toBe(2)
        ->and(LeagueMatch::query()->where('status', 'scheduled')->count())->toBe(10)
        ->and(LeagueState::current()->current_view_week)->toBe(1);
});

test('simulation page week query updates current view week', function () {
    $this->seed(LeagueSeeder::class);
    $this->post(route('league.fixtures.store'), leagueTeamsFormPayload());
    $this->post(route('league.simulation.start'));

    $this->get(route('league.simulation', ['week' => 5]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('currentViewWeek', 5));

    expect(LeagueState::current()->current_view_week)->toBe(5);
});

test('title probability snapshot after matchweek one is spread across clubs not a single 100 percent', function () {
    $this->seed(LeagueSeeder::class);
    $this->post(route('league.fixtures.store'), leagueTeamsFormPayload());
    $this->post(route('league.simulation.start'));
    $this->post(route('league.simulation.play-next'));

    expect(LeagueMatch::query()->where('status', 'finished')->count())->toBe(2)
        ->and(LeagueMatch::query()->where('status', 'scheduled')->count())->toBe(10);

    /** @var array<int, mixed>|null $snaps */
    $snaps = LeagueState::current()->prediction_snapshots;
    expect($snaps)->toBeArray();

    $row = collect($snaps)->firstWhere('after_matchweek', 1);
    expect($row)->not->toBeNull();

    /** @var array<string, float|int|string> $pcts */
    $pcts = $row['percentages'];
    $vals = array_map(fn (float|int|string $v): float => (float) $v, $pcts);
    $max = max($vals);
    $positive = count(array_filter($vals, fn (float $v): bool => $v >= 1.0));

    expect($max)->toBeLessThan(95.0)
        ->and($positive)->toBeGreaterThan(1);
});

test('reset clears matches and returns to teams step', function () {
    $this->seed(LeagueSeeder::class);
    $this->post(route('league.fixtures.store'), leagueTeamsFormPayload());
    $this->post(route('league.reset'));

    expect(LeagueMatch::query()->count())->toBe(0)
        ->and(LeagueState::current()->step)->toBe('teams');
});

test('home inertia includes four clubs and tournament history', function () {
    $this->seed(LeagueSeeder::class);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('League/Index')
            ->where('surface', 'home')
            ->where('leagueProgressStep', 'teams')
            ->has('teams', 4)
            ->has('tournamentHistory'));
});
