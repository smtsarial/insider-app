<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateLeagueFixturesRequest;
use App\Http\Requests\UpdateLeagueMatchRequest;
use App\League\Archive\LeagueTournamentArchiveWriter;
use App\League\Fixtures\RoundRobinFixtureGenerator;
use App\League\LeagueMatchWeekSimulator;
use App\League\LeaguePredictionSnapshotPersister;
use App\League\LeagueScheduleLoader;
use App\League\LeagueSimulationPresenter;
use App\Models\LeagueMatch;
use App\Models\LeagueState;
use App\Models\LeagueTournamentArchive;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        private readonly LeagueScheduleLoader $scheduleLoader,
        private readonly LeagueMatchWeekSimulator $weekSimulator,
        private readonly LeaguePredictionSnapshotPersister $predictionSnapshotPersister,
        private readonly LeagueSimulationPresenter $simulationPresenter,
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

        $competition = $this->scheduleLoader->load();

        return Inertia::render('League/Index', [
            'surface' => 'wizard',
            'step' => 'fixtures',
            'matchesByWeek' => $this->simulationPresenter->matchesByWeekPayload(
                $competition,
                fn (Team $t): array => $this->teamPayload($t),
            ),
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

        $competition = $this->scheduleLoader->load();

        return Inertia::render('League/Index', array_merge([
            'surface' => 'wizard',
            'step' => 'simulation',
            'tournamentHistory' => $this->tournamentHistoryPayload(),
        ], $this->simulationPresenter->simulationProps(
            LeagueState::current()->fresh(),
            $competition,
            fn (Team $t): array => $this->teamPayload($t),
        )));
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

        $week = $this->scheduleLoader->load()->nextPlayableMatchweek();
        if ($week === null) {
            return redirect()->route('league.simulation');
        }

        $this->weekSimulator->simulateScheduledWeek($week);
        $state->update(['current_view_week' => $week]);

        $this->predictionSnapshotPersister->persist(
            LeagueState::current()->fresh(),
            $this->scheduleLoader->load(),
            $this->snapshotIterationsOverride,
        );

        return redirect()->route('league.simulation');
    }

    public function playAllWeeks(): RedirectResponse
    {
        $state = LeagueState::current();
        if ($state->step !== 'simulation') {
            return redirect()->route('home');
        }

        $this->snapshotIterationsOverride = (int) config('league.prediction_monte_carlo_iterations_fast', 500);

        $simulatedAnyWeek = false;
        try {
            while (($week = $this->scheduleLoader->load()->nextPlayableMatchweek()) !== null) {
                $this->weekSimulator->simulateScheduledWeek($week);
                $this->predictionSnapshotPersister->persist(
                    LeagueState::current()->fresh(),
                    $this->scheduleLoader->load(),
                    $this->snapshotIterationsOverride,
                );
                $simulatedAnyWeek = true;
            }
        } finally {
            $this->snapshotIterationsOverride = null;
        }

        if (! $simulatedAnyWeek) {
            return redirect()->route('league.simulation');
        }

        $this->predictionSnapshotPersister->persist(
            LeagueState::current()->fresh(),
            $this->scheduleLoader->load(),
            null,
        );

        $state->update([
            'current_view_week' => (int) config('league.timeline_matchweeks', 6),
        ]);

        return redirect()->route('league.simulation');
    }

    public function reset(): RedirectResponse
    {
        $this->performLeagueReset();

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

    private function performLeagueReset(): void
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

    private function persistPredictionSnapshot(): void
    {
        $this->predictionSnapshotPersister->persist(
            LeagueState::current()->fresh(),
            $this->scheduleLoader->load(),
            $this->snapshotIterationsOverride,
        );
    }
}
