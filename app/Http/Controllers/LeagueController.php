<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateLeagueFixturesRequest;
use App\Http\Requests\UpdateLeagueMatchRequest;
use App\Models\LeagueMatch;
use App\Models\LeagueState;
use App\Models\LeagueTournamentArchive;
use App\Models\Team;
use App\Services\League\ChampionshipProbabilityEstimator;
use App\Services\League\LeagueTournamentArchiveWriter;
use App\Services\League\MatchSimulationEngine;
use App\Services\League\PremierLeagueStandingsCalculator;
use App\Services\League\RoundRobinFixtureGenerator;
use App\Services\League\TitlePredictionSnapshotService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class LeagueController extends Controller
{
    /**
     * During bulk "play all", snapshots use fewer Monte Carlo iterations; cleared after the loop.
     */
    private ?int $snapshotIterationsOverride = null;

    public function __construct(
        private readonly RoundRobinFixtureGenerator $fixtureGenerator,
        private readonly MatchSimulationEngine $simulationEngine,
        private readonly PremierLeagueStandingsCalculator $standingsCalculator,
        private readonly ChampionshipProbabilityEstimator $probabilityEstimator,
        private readonly TitlePredictionSnapshotService $titlePredictionSnapshots,
        private readonly LeagueTournamentArchiveWriter $tournamentArchiveWriter,
    ) {}

    /**
     * Landing page: archived tournaments and club setup (always reachable at /).
     */
    public function home(): Response
    {
        $state = LeagueState::current();
        $teams = Team::query()->orderBy('sort_order')->get();

        return Inertia::render('League/Index', [
            'surface' => 'home',
            'leagueProgressStep' => $state->step,
            'teams' => $teams->map(fn (Team $t): array => $this->teamPayload($t))->values()->all(),
            'tournamentHistory' => $this->tournamentHistoryPayload(),
        ]);
    }

    public function tournamentTeams(): RedirectResponse
    {
        $state = LeagueState::current();
        if ($state->step === 'fixtures') {
            return redirect()->route('league.fixtures');
        }
        if ($state->step === 'simulation') {
            return redirect()->route('league.simulation');
        }

        return redirect()->route('home');
    }

    public function storeFixtures(GenerateLeagueFixturesRequest $request): RedirectResponse
    {
        $state = LeagueState::current();

        if ($state->step === 'simulation') {
            return redirect()->route('league.simulation');
        }

        $payload = $request->validated();

        DB::transaction(function () use ($payload, $state): void {
            if (isset($payload['teams']) && is_array($payload['teams'])) {
                foreach ($payload['teams'] as $row) {
                    Team::query()->whereKey($row['id'])->update([
                        'name' => $row['name'],
                        'short_name' => $row['short_name'],
                        'power' => $row['power'],
                        'sort_order' => $row['sort_order'],
                    ]);
                }
            }

            $this->tournamentArchiveWriter->archiveIfCompetitionExists($state);

            LeagueMatch::query()->delete();

            $teams = Team::query()->orderBy('sort_order')->get();
            $shuffleSlots = ! isset($payload['teams']);
            $rows = $this->fixtureGenerator->generate($teams, $shuffleSlots);
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
                'prediction_snapshots' => null,
            ]);
        });

        return redirect()->route('league.fixtures');
    }

    public function fixtures(): Response|RedirectResponse
    {
        $state = LeagueState::current();
        if ($state->step === 'teams') {
            return redirect()->route('home');
        }
        if ($state->step === 'simulation') {
            return redirect()->route('league.simulation');
        }

        $matches = $this->matchesWithTeams();

        return Inertia::render('League/Index', [
            'surface' => 'wizard',
            'step' => 'fixtures',
            'matchesByWeek' => $this->matchesByWeekPayload($matches),
            'tournamentHistory' => $this->tournamentHistoryPayload(),
        ]);
    }

    public function startSimulation(): RedirectResponse
    {
        $state = LeagueState::current();
        if ($state->step === 'simulation') {
            return redirect()->route('league.simulation');
        }
        if ($state->step !== 'fixtures') {
            return redirect()->route('home');
        }

        $state->update([
            'step' => 'simulation',
            'current_view_week' => 1,
            'prediction_snapshots' => null,
        ]);

        $this->persistPredictionSnapshot();

        return redirect()->route('league.simulation');
    }

    public function simulation(Request $request): Response|RedirectResponse
    {
        $state = LeagueState::current();
        if ($state->step === 'fixtures') {
            return redirect()->route('league.fixtures');
        }
        if ($state->step !== 'simulation') {
            return redirect()->route('home');
        }

        if ($request->has('week')) {
            $w = (int) $request->query('week');
            if ($w >= 1 && $w <= 6) {
                $state->update(['current_view_week' => $w]);
            }
        }

        return Inertia::render('League/Index', array_merge([
            'surface' => 'wizard',
            'step' => 'simulation',
            'tournamentHistory' => $this->tournamentHistoryPayload(),
        ], $this->simulationProps()));
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
            return redirect()->route('home');
        }

        $week = $this->nextPlayableWeek();
        if ($week === null) {
            return redirect()->route('league.simulation');
        }

        $this->simulateWeek($week);
        $state->update(['current_view_week' => $week]);

        return redirect()->route('league.simulation');
    }

    public function playAllWeeks(): RedirectResponse
    {
        $state = LeagueState::current();
        if ($state->step !== 'simulation') {
            return redirect()->route('home');
        }

        $this->snapshotIterationsOverride = (int) config('league.prediction_monte_carlo_iterations_fast', 500);

        try {
            while (($week = $this->nextPlayableWeek()) !== null) {
                $this->simulateWeek($week);
            }
        } finally {
            $this->snapshotIterationsOverride = null;
        }

        $this->persistPredictionSnapshot();

        $state->update([
            'current_view_week' => (int) config('league.timeline_matchweeks', 6),
        ]);

        return redirect()->route('league.simulation');
    }

    public function reset(): RedirectResponse
    {
        DB::transaction(function (): void {
            $state = LeagueState::current();
            $this->tournamentArchiveWriter->archiveIfCompetitionExists($state);

            LeagueMatch::query()->delete();
            $state->update([
                'step' => 'teams',
                'current_view_week' => 1,
                'prediction_snapshots' => null,
            ]);
        });

        return redirect()->route('home');
    }

    public function updateMatch(UpdateLeagueMatchRequest $request, LeagueMatch $leagueMatch): RedirectResponse
    {
        $state = LeagueState::current();
        if ($state->step !== 'simulation') {
            return redirect()->route('home');
        }

        $validated = $request->validated();
        $leagueMatch->update([
            'home_goals' => $validated['home_goals'],
            'away_goals' => $validated['away_goals'],
            'status' => 'finished',
            'is_manually_edited' => true,
        ]);

        $this->persistPredictionSnapshot();

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
        $lastCompleted = $this->lastCompletedMatchweekFromCollection($matches);
        $nextPlayable = $this->nextPlayableWeekFromCollection($matches);
        $seasonComplete = $nextPlayable === null && $matches->isNotEmpty();

        // Show title-race model in the last 3 matchweeks (weeks 4–6) as per the brief.
        $predictionActive = $lastCompleted >= 3 || $seasonComplete;

        $iterations = $this->predictionMonteCarloIterations();

        $predMap = $predictionActive
            ? $this->probabilityEstimator->estimate($teams, $matches->values(), $iterations)
            : [];

        /** @var list<array<string, mixed>>|null $snapshots */
        $snapshots = is_array($state->prediction_snapshots) ? $state->prediction_snapshots : null;
        $historyGrid = $this->titlePredictionSnapshots->buildHistoryGrid(
            $teams,
            $snapshots,
            fn (Team $t): array => $this->teamPayload($t),
        );

        $trackRecord = null;
        if ($seasonComplete) {
            $championIds = $this->standingsCalculator->firstPlaceTeamIds($teams, $finished);
            $trackRecord = $this->titlePredictionSnapshots->favoriteVersusChampionTrackRecord($snapshots, $championIds);
            if (is_array($trackRecord)) {
                $trackRecord['champion_names'] = $teams
                    ->whereIn('id', $trackRecord['champion_team_ids'])
                    ->pluck('name')
                    ->values()
                    ->all();
            }
        }

        return [
            'currentViewWeek' => $state->current_view_week,
            'lastCompletedMatchweek' => $lastCompleted,
            'nextPlayableWeek' => $nextPlayable,
            'seasonComplete' => $seasonComplete,
            'predictionActive' => $predictionActive,
            'standings' => $standings->map(function (array $row) use ($predMap, $predictionActive): array {
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
                    'title_percent' => $predictionActive ? ($predMap[$team->id] ?? 0.0) : null,
                ];
            })->values()->all(),
            'matchesByWeek' => $this->matchesByWeekPayload($matches),
            'predictionHistoryGrid' => $historyGrid,
            'predictionTrackRecord' => $trackRecord,
        ];
    }

    private function teamPayload(Team $t): array
    {
        return [
            'id' => $t->id,
            'name' => $t->name,
            'short_name' => $t->short_name,
            'power' => $t->power,
            'sort_order' => $t->sort_order,
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

    private function predictionMonteCarloIterations(): int
    {
        return (int) config('league.prediction_monte_carlo_iterations', 1200);
    }

    /**
     * @param  Collection<int, LeagueMatch>  $matches
     */
    private function lastCompletedMatchweekFromCollection(Collection $matches): int
    {
        $max = 0;
        foreach ($matches->groupBy('matchweek') as $w => $inWeek) {
            if ($inWeek->isEmpty()) {
                continue;
            }
            if ($inWeek->every(fn (LeagueMatch $m): bool => $m->isFinished())) {
                $max = max($max, (int) $w);
            }
        }

        return $max;
    }

    /**
     * @param  Collection<int, LeagueMatch>  $matches
     */
    private function nextPlayableWeekFromCollection(Collection $matches): ?int
    {
        $m = $matches
            ->filter(fn (LeagueMatch $m): bool => $m->status === 'scheduled')
            ->sortBy(fn (LeagueMatch $m): array => [$m->matchweek, $m->id])
            ->first();

        return $m ? (int) $m->matchweek : null;
    }

    private function lastCompletedMatchweek(): int
    {
        return $this->lastCompletedMatchweekFromCollection($this->matchesWithTeams());
    }

    private function nextPlayableWeek(): ?int
    {
        return $this->nextPlayableWeekFromCollection($this->matchesWithTeams());
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function tournamentHistoryPayload(): array
    {
        return LeagueTournamentArchive::query()
            ->with(['champion:id,name'])
            ->orderByDesc('archived_at')
            ->limit(30)
            ->get()
            ->map(function (LeagueTournamentArchive $row): array {
                /** @var list<array<string, mixed>>|null $standings */
                $standings = $row->payload['standings'] ?? null;
                $slice = is_array($standings)
                    ? array_values(array_slice($standings, 0, 4))
                    : [];

                $standingsDetail = [];
                foreach ($slice as $r) {
                    $standingsDetail[] = [
                        'team_id' => (int) ($r['team_id'] ?? 0),
                        'team_name' => (string) ($r['team_name'] ?? ''),
                        'played' => (int) ($r['played'] ?? 0),
                        'won' => (int) ($r['won'] ?? 0),
                        'drawn' => (int) ($r['drawn'] ?? 0),
                        'lost' => (int) ($r['lost'] ?? 0),
                        'gf' => (int) ($r['gf'] ?? 0),
                        'ga' => (int) ($r['ga'] ?? 0),
                        'gd' => (int) ($r['gd'] ?? 0),
                        'points' => (int) ($r['points'] ?? 0),
                    ];
                }

                $table = array_map(fn (array $r): array => [
                    'team_name' => $r['team_name'],
                    'points' => $r['points'],
                    'played' => $r['played'],
                    'gd' => $r['gd'],
                ], $standingsDetail);

                /** @var list<array<string, mixed>>|null $snapshots */
                $snapshots = $row->payload['prediction_snapshots'] ?? null;

                return [
                    'id' => $row->id,
                    'archived_at' => $row->archived_at->toIso8601String(),
                    'was_completed' => $row->was_completed,
                    'champion_name' => $row->champion?->name,
                    'finished_matches' => $row->finished_matches_count,
                    'step_at_archive' => $row->step_at_archive,
                    'table' => $table,
                    'standings_detail' => $standingsDetail,
                    'prediction_snapshots' => is_array($snapshots) ? $snapshots : [],
                ];
            })
            ->all();
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

        $this->persistPredictionSnapshot();
    }

    /**
     * Stores title-% estimates for the current results (after_matchweek = last fully completed matchweek).
     */
    private function persistPredictionSnapshot(): void
    {
        $state = LeagueState::current()->fresh();
        if ($state->step !== 'simulation') {
            return;
        }

        $teams = Team::query()->orderBy('sort_order')->get();
        $matches = $this->matchesWithTeams();
        if ($matches->isEmpty()) {
            return;
        }

        $afterMw = $this->lastCompletedMatchweekFromCollection($matches);

        // Only run the estimator when points matter: at least one matchweek complete
        // so the history grid always shows week-by-week progression.
        $iterations = $this->snapshotIterationsOverride ?? $this->predictionMonteCarloIterations();

        $pred = $this->probabilityEstimator->estimate($teams, $matches->values(), $iterations);

        $finishedThrough = $matches->filter(function (LeagueMatch $m) use ($afterMw): bool {
            return $m->isFinished() && (int) $m->matchweek <= $afterMw;
        })->values();

        $tableAtSnapshot = $this->standingsCalculator->calculate($teams, $finishedThrough);
        $standingsRows = $tableAtSnapshot->map(fn (array $r): array => [
            'team_id' => $r['team']->id,
            'played' => $r['played'],
            'won' => $r['won'],
            'drawn' => $r['drawn'],
            'lost' => $r['lost'],
            'gf' => $r['gf'],
            'ga' => $r['ga'],
            'gd' => $r['gd'],
            'points' => $r['points'],
        ])->values()->all();

        $merged = $this->titlePredictionSnapshots->upsert(
            is_array($state->prediction_snapshots) ? $state->prediction_snapshots : null,
            $afterMw,
            $pred,
            $standingsRows,
        );
        $state->update(['prediction_snapshots' => $merged]);
    }
}
