<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateLeagueFixturesRequest;
use App\Http\Requests\UpdateLeagueMatchRequest;
use App\Models\LeagueMatch;
use App\Models\LeagueState;
use App\Models\Team;
use App\Services\League\ChampionshipProbabilityEstimator;
use App\Services\League\MatchSimulationEngine;
use App\Services\League\PremierLeagueStandingsCalculator;
use App\Services\League\RoundRobinFixtureGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class LeagueController extends Controller
{
    public function __construct(
        private readonly RoundRobinFixtureGenerator $fixtureGenerator,
        private readonly MatchSimulationEngine $simulationEngine,
        private readonly PremierLeagueStandingsCalculator $standingsCalculator,
        private readonly ChampionshipProbabilityEstimator $probabilityEstimator,
    ) {}

    public function tournamentTeams(): Response|RedirectResponse
    {
        $state = LeagueState::current();
        if ($state->step === 'fixtures') {
            return redirect()->route('league.fixtures');
        }
        if ($state->step === 'simulation') {
            return redirect()->route('league.simulation');
        }

        $teams = Team::query()->orderBy('sort_order')->get();

        return Inertia::render('League/TournamentTeams', [
            'teams' => $teams->map(fn (Team $t): array => $this->teamPayload($t))->values()->all(),
        ]);
    }

    public function storeFixtures(GenerateLeagueFixturesRequest $request): RedirectResponse
    {
        $state = LeagueState::current();
        $teams = Team::query()->orderBy('sort_order')->get();

        DB::transaction(function () use ($teams, $state): void {
            LeagueMatch::query()->delete();

            $rows = $this->fixtureGenerator->generate($teams);
            foreach ($rows as $row) {
                LeagueMatch::query()->create([
                    'home_team_id' => $row['home_team_id'],
                    'away_team_id' => $row['away_team_id'],
                    'matchweek' => $row['matchweek'],
                    'status' => 'scheduled',
                ]);
            }

            $state->update([
                'step' => 'fixtures',
                'current_view_week' => 1,
            ]);
        });

        return redirect()->route('league.fixtures');
    }

    public function fixtures(): Response|RedirectResponse
    {
        $state = LeagueState::current();
        if ($state->step === 'teams') {
            return redirect()->route('league.tournament-teams');
        }
        if ($state->step === 'simulation') {
            return redirect()->route('league.simulation');
        }

        $matches = $this->matchesWithTeams();

        return Inertia::render('League/GeneratedFixtures', [
            'matchesByWeek' => $this->matchesByWeekPayload($matches),
        ]);
    }

    public function startSimulation(): RedirectResponse
    {
        $state = LeagueState::current();
        if ($state->step === 'simulation') {
            return redirect()->route('league.simulation');
        }
        if ($state->step !== 'fixtures') {
            return redirect()->route('league.tournament-teams');
        }

        $state->update([
            'step' => 'simulation',
            'current_view_week' => 1,
        ]);

        return redirect()->route('league.simulation');
    }

    public function simulation(Request $request): Response|RedirectResponse
    {
        $state = LeagueState::current();
        if ($state->step === 'fixtures') {
            return redirect()->route('league.fixtures');
        }
        if ($state->step !== 'simulation') {
            return redirect()->route('league.tournament-teams');
        }

        if ($request->has('week')) {
            $w = (int) $request->query('week');
            if ($w >= 1 && $w <= 6) {
                $state->update(['current_view_week' => $w]);
            }
        }

        return Inertia::render('League/Simulation', $this->simulationProps());
    }

    public function setViewWeek(Request $request): RedirectResponse
    {
        $request->validate([
            'week' => ['required', 'integer', 'min:1', 'max:6'],
        ]);

        $state = LeagueState::current();
        $state->update(['current_view_week' => (int) $request->input('week')]);

        return redirect()->route('league.simulation');
    }

    public function playNextWeek(): RedirectResponse
    {
        $state = LeagueState::current();
        if ($state->step !== 'simulation') {
            return redirect()->route('league.tournament-teams');
        }

        $week = $this->nextPlayableWeek();
        if ($week === null) {
            return redirect()->route('league.simulation');
        }

        $this->simulateWeek($week);

        return redirect()->route('league.simulation');
    }

    public function playAllWeeks(): RedirectResponse
    {
        $state = LeagueState::current();
        if ($state->step !== 'simulation') {
            return redirect()->route('league.tournament-teams');
        }

        while (($week = $this->nextPlayableWeek()) !== null) {
            $this->simulateWeek($week);
        }

        return redirect()->route('league.simulation');
    }

    public function reset(): RedirectResponse
    {
        DB::transaction(function (): void {
            LeagueMatch::query()->delete();
            LeagueState::current()->update([
                'step' => 'teams',
                'current_view_week' => 1,
            ]);
        });

        return redirect()->route('league.tournament-teams');
    }

    public function updateMatch(UpdateLeagueMatchRequest $request, LeagueMatch $leagueMatch): RedirectResponse
    {
        $state = LeagueState::current();
        if ($state->step !== 'simulation') {
            return redirect()->route('league.tournament-teams');
        }

        $validated = $request->validated();
        $leagueMatch->update([
            'home_goals' => $validated['home_goals'],
            'away_goals' => $validated['away_goals'],
            'status' => 'finished',
            'is_manually_edited' => true,
        ]);

        return redirect()->route('league.simulation');
    }

    /**
     * @return array<string, mixed>
     */
    private function simulationProps(): array
    {
        $state = LeagueState::current();
        $teams = Team::query()->orderBy('sort_order')->get();
        $matches = $this->matchesWithTeams();

        $finished = $matches->filter(fn (LeagueMatch $m): bool => $m->isFinished())->values();
        $standings = $this->standingsCalculator->calculate($teams, $finished);

        $lastCompleted = $this->lastCompletedMatchweek();
        $predictionActive = $lastCompleted >= 3;

        $predMap = $predictionActive
            ? $this->probabilityEstimator->estimate($teams, $matches->values())
            : $this->zeroPredictions($teams);

        return [
            'currentViewWeek' => $state->current_view_week,
            'lastCompletedMatchweek' => $lastCompleted,
            'nextPlayableWeek' => $this->nextPlayableWeek(),
            'seasonComplete' => $this->nextPlayableWeek() === null && $matches->isNotEmpty(),
            'predictionActive' => $predictionActive,
            'standings' => $standings->map(function (array $row): array {
                /** @var Team $team */
                $team = $row['team'];

                return [
                    'team' => $this->teamPayload($team),
                    'played' => $row['played'],
                    'won' => $row['won'],
                    'drawn' => $row['drawn'],
                    'lost' => $row['lost'],
                    'gf' => $row['gf'],
                    'ga' => $row['ga'],
                    'gd' => $row['gd'],
                    'points' => $row['points'],
                ];
            })->values()->all(),
            'matchesByWeek' => $this->matchesByWeekPayload($matches),
            'predictions' => $teams->map(fn (Team $t) => [
                'team_id' => $t->id,
                'name' => $t->name,
                'percent' => $predMap[$t->id] ?? 0.0,
            ])->values()->all(),
        ];
    }

    private function teamPayload(Team $t): array
    {
        return [
            'id' => $t->id,
            'name' => $t->name,
            'short_name' => $t->short_name,
            'power' => $t->power,
        ];
    }

    /**
     * @param  Collection<int, LeagueMatch>  $matches
     * @return array<int, list<array<string, mixed>>>
     */
    private function matchesByWeekPayload($matches): array
    {
        $grouped = $matches->groupBy('matchweek');
        $weeks = [];
        for ($w = 1; $w <= 6; $w++) {
            $list = $grouped->get($w, collect());
            $weeks[] = [
                'week' => $w,
                'matches' => $list->map(function (LeagueMatch $m): array {
                    return [
                        'id' => $m->id,
                        'matchweek' => $m->matchweek,
                        'home_team' => $this->teamPayload($m->homeTeam),
                        'away_team' => $this->teamPayload($m->awayTeam),
                        'home_goals' => $m->home_goals,
                        'away_goals' => $m->away_goals,
                        'status' => $m->status,
                        'is_manually_edited' => $m->is_manually_edited,
                    ];
                })->values()->all(),
            ];
        }

        return $weeks;
    }

    /**
     * @return Collection<int, LeagueMatch>
     */
    private function matchesWithTeams()
    {
        return LeagueMatch::query()
            ->with(['homeTeam', 'awayTeam'])
            ->orderBy('matchweek')
            ->orderBy('id')
            ->get();
    }

    private function lastCompletedMatchweek(): int
    {
        $weeks = LeagueMatch::query()
            ->distinct()
            ->orderBy('matchweek')
            ->pluck('matchweek');

        $max = 0;
        foreach ($weeks as $w) {
            $inWeek = LeagueMatch::query()->where('matchweek', $w)->get();
            if ($inWeek->isEmpty()) {
                continue;
            }
            if ($inWeek->every(fn (LeagueMatch $m): bool => $m->isFinished())) {
                $max = max($max, (int) $w);
            }
        }

        return $max;
    }

    private function nextPlayableWeek(): ?int
    {
        $m = LeagueMatch::query()
            ->where('status', 'scheduled')
            ->orderBy('matchweek')
            ->orderBy('id')
            ->first();

        return $m ? (int) $m->matchweek : null;
    }

    private function simulateWeek(int $week): void
    {
        $matches = LeagueMatch::query()
            ->with(['homeTeam', 'awayTeam'])
            ->where('matchweek', $week)
            ->where('status', 'scheduled')
            ->orderBy('id')
            ->get();

        foreach ($matches as $match) {
            $seed = (int) ($match->id * 10007 + $week * 131);
            [$hg, $ga] = $this->simulationEngine->simulate($match->homeTeam, $match->awayTeam, $seed);
            $match->update([
                'home_goals' => $hg,
                'away_goals' => $ga,
                'status' => 'finished',
                'is_manually_edited' => false,
            ]);
        }
    }

    /**
     * @param  Collection<int, Team>  $teams
     * @return array<int, float>
     */
    private function zeroPredictions($teams): array
    {
        $out = [];
        foreach ($teams as $t) {
            $out[$t->id] = 0.0;
        }

        return $out;
    }
}
