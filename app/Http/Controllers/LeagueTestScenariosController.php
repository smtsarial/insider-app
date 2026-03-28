<?php

namespace App\Http\Controllers;

use App\League\Fixtures\RoundRobinFixtureGenerator;
use App\League\Prediction\TitlePredictionSnapshotService;
use App\League\Simulation\MatchSimulationEngine;
use App\League\Standings\PremierLeagueStandingsCalculator;
use App\Models\LeagueMatch;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\Process\Process;

/**
 * Live view of the same in-memory setups as {@see tests/Unit/MatchSimulationEngineTest},
 * {@see tests/Unit/PremierLeagueStandingsCalculatorTest}, and
 * {@see tests/Unit/TitlePredictionSnapshotServiceTest}.
 */
class LeagueTestScenariosController extends Controller
{
    public function show(
        MatchSimulationEngine $matchEngine,
        PremierLeagueStandingsCalculator $standingsCalculator,
        RoundRobinFixtureGenerator $fixtureGenerator,
        TitlePredictionSnapshotService $titleSnapshots,
    ): Response {
        $canRunUnitTests = app()->environment(['local', 'testing']);

        return Inertia::render('League/TestScenarios', [
            'canRunUnitTests' => $canRunUnitTests,
            'matchSimulation' => $this->matchSimulationPayload($matchEngine),
            'standings' => $this->standingsPayload($standingsCalculator),
            'fixtures' => $this->fixturesPayload($fixtureGenerator),
            'titlePrediction' => $this->titlePredictionPayload($standingsCalculator, $titleSnapshots),
        ]);
    }

    /**
     * Runs the three league-related Pest files in a subprocess (see middleware: local/testing only).
     */
    public function run(): JsonResponse
    {
        $files = [
            base_path('tests/Unit/MatchSimulationEngineTest.php'),
            base_path('tests/Unit/PremierLeagueStandingsCalculatorTest.php'),
            base_path('tests/Unit/TitlePredictionSnapshotServiceTest.php'),
        ];

        $command = array_merge(
            [PHP_BINARY, base_path('artisan'), 'test', '--compact'],
            $files,
        );

        $process = new Process($command, base_path(), null, null, 120.0);
        $process->run();

        $stdout = $process->getOutput();
        $stderr = $process->getErrorOutput();
        $merged = $stdout;
        if ($stderr !== '') {
            $merged .= ($merged !== '' ? "\n" : '').$stderr;
        }

        $exitCode = $process->getExitCode() ?? 1;

        return response()->json([
            'success' => $exitCode === 0,
            'exit_code' => $exitCode,
            'output' => $merged !== '' ? $merged : '(no output)',
            'command' => [
                'binary' => PHP_BINARY,
                'args' => array_slice($command, 1),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function matchSimulationPayload(MatchSimulationEngine $engine): array
    {
        $home80 = $this->simulationTeam(1, 80);
        $away80 = $this->simulationTeam(2, 80);
        [$dHome, $dAway] = $engine->simulate($home80, $away80, 42_001);

        $homeFav = $this->simulationTeam(1, 100);
        $awayWeak = $this->simulationTeam(2, 10);
        $homeWins = 0;
        $draws = 0;
        $awayWinsFav = 0;
        for ($s = 0; $s < 4000; $s++) {
            [$h, $a] = $engine->simulate($homeFav, $awayWeak, $s);
            if ($a > $h) {
                $awayWinsFav++;
            }
            if ($h === $a) {
                $draws++;
            }
            if ($h > $a) {
                $homeWins++;
            }
        }

        $homeWeak = $this->simulationTeam(1, 12);
        $awayStrong = $this->simulationTeam(2, 95);
        $awayWinsStrong = 0;
        for ($s = 0; $s < 3000; $s++) {
            [$h, $a] = $engine->simulate($homeWeak, $awayStrong, 10_000 + $s);
            if ($a > $h) {
                $awayWinsStrong++;
            }
        }

        return [
            'deterministic' => [
                'test' => 'match simulation engine is deterministic with seed',
                'seed' => 42_001,
                'home' => ['id' => 1, 'power' => 80],
                'away' => ['id' => 2, 'power' => 80],
                'score' => ['home' => $dHome, 'away' => $dAway],
            ],
            'home_favourite_monte_carlo' => [
                'test' => 'heavy home favourite wins most matches but away upsets stay possible',
                'iterations' => 4000,
                'seeds' => '0 … 3999',
                'home' => ['id' => 1, 'power' => 100],
                'away' => ['id' => 2, 'power' => 10],
                'home_wins' => $homeWins,
                'draws' => $draws,
                'away_wins' => $awayWinsFav,
                'assertions' => 'away_wins > 0, away_wins < 200, draws < 700',
            ],
            'strong_away_monte_carlo' => [
                'test' => 'strong away side wins majority at weak home ground',
                'iterations' => 3000,
                'seeds' => '10000 … 10999',
                'home' => ['id' => 1, 'power' => 12],
                'away' => ['id' => 2, 'power' => 95],
                'away_wins' => $awayWinsStrong,
                'assertions' => 'away_wins > 1800',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function standingsPayload(PremierLeagueStandingsCalculator $calc): array
    {
        $tA = $this->makeStandingsTeam(1, 'Alpha');
        $tA->power = 92;
        $tB = $this->makeStandingsTeam(2, 'Beta');
        $tB->power = 71;
        $teamsH2h = collect([$tA, $tB]);

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
        $tableH2h = $calc->calculate($teamsH2h, collect([$m1, $m2]));

        $tA2 = $this->makeStandingsTeam(1, 'Alpha');
        $tB2 = $this->makeStandingsTeam(2, 'Beta');
        $teamsGd = collect([$tA2, $tB2]);
        $mGd = LeagueMatch::make([
            'home_team_id' => 1,
            'away_team_id' => 2,
            'home_goals' => 3,
            'away_goals' => 0,
            'status' => 'finished',
        ]);
        $mGd->setRelation('homeTeam', $tA2);
        $mGd->setRelation('awayTeam', $tB2);
        $tableGd = $calc->calculate($teamsGd, collect([$mGd]));

        return [
            'head_to_head_then_squad' => [
                'test' => 'breaks full tie on head to head then squad rating',
                'matches' => [
                    ['home' => 'Alpha', 'away' => 'Beta', 'score' => '1–1'],
                    ['home' => 'Beta', 'away' => 'Alpha', 'score' => '1–1'],
                ],
                'note' => 'Mini-league tied; squad rating (power) breaks tie — Alpha 92 vs Beta 71.',
                'table' => $this->serializeStandingsTable($tableH2h),
                'expect_top_team_id' => 1,
            ],
            'points_then_gd' => [
                'test' => 'orders teams by points then goal difference',
                'matches' => [
                    ['home' => 'Alpha', 'away' => 'Beta', 'score' => '3–0'],
                ],
                'table' => $this->serializeStandingsTable($tableGd),
                'expect_top_team_id' => 1,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fixturesPayload(RoundRobinFixtureGenerator $gen): array
    {
        $teams = collect([
            $this->makeStandingsTeam(10, 'Arsenal', 1),
            $this->makeStandingsTeam(20, 'Liverpool', 2),
            $this->makeStandingsTeam(30, 'Manchester City', 3),
            $this->makeStandingsTeam(40, 'Chelsea', 4),
        ])->sortBy('sort_order');

        $ordered = $gen->generate($teams, shuffleSlots: false);
        $shuffled = $gen->generate($teams, shuffleSlots: true, shuffleSeed: 12_345);

        $pairKey = static function (array $r): string {
            $a = (int) $r['home_team_id'];
            $b = (int) $r['away_team_id'];

            return min($a, $b).'-'.max($a, $b);
        };

        $weekOneOrdered = collect($ordered)
            ->where('matchweek', 1)
            ->map($pairKey)
            ->sort()
            ->values()
            ->all();
        $weekOneShuffled = collect($shuffled)
            ->where('matchweek', 1)
            ->map($pairKey)
            ->sort()
            ->values()
            ->all();

        return [
            'twelve_fixtures' => [
                'test' => 'round robin generator yields twelve fixtures',
                'fixture_count' => count($ordered),
                'matchweeks' => collect($ordered)->pluck('matchweek')->unique()->sort()->values()->all(),
            ],
            'shuffle_week_one' => [
                'test' => 'round robin generator shuffle slots changes week one pairings versus sort order',
                'week_one_ordered_pairings' => $weekOneOrdered,
                'week_one_shuffled_pairings_seed_12345' => $weekOneShuffled,
                'shuffled_differs_from_ordered' => $weekOneShuffled !== $weekOneOrdered,
            ],
            'ordered_preview' => collect($ordered)->take(8)->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function titlePredictionPayload(
        PremierLeagueStandingsCalculator $calc,
        TitlePredictionSnapshotService $svc,
    ): array {
        $standings = [
            ['team_id' => 1, 'played' => 0, 'won' => 0, 'drawn' => 0, 'lost' => 0, 'gf' => 0, 'ga' => 0, 'gd' => 0, 'points' => 0],
            ['team_id' => 2, 'played' => 0, 'won' => 0, 'drawn' => 0, 'lost' => 0, 'gf' => 0, 'ga' => 0, 'gd' => 0, 'points' => 0],
        ];
        $a = $svc->upsert(null, 0, [1 => 25.0, 2 => 25.0], $standings);
        $b = $svc->upsert($a, 0, [1 => 40.0, 2 => 20.0], $standings);

        $snapshots = [
            ['after_matchweek' => 0, 'percentages' => ['10' => 50.0, '20' => 30.0]],
            ['after_matchweek' => 1, 'percentages' => ['10' => 10.0, '20' => 60.0]],
            ['after_matchweek' => 6, 'percentages' => ['10' => 100.0, '20' => 0.0]],
        ];
        $track = $svc->favoriteVersusChampionTrackRecord($snapshots, [20]);

        $tA = $this->simulationTeam(1, 80);
        $tA->name = 'A';
        $tA->short_name = 'A';
        $tB = $this->simulationTeam(2, 80);
        $tB->name = 'B';
        $tB->short_name = 'B';
        $m = LeagueMatch::make([
            'home_team_id' => 1,
            'away_team_id' => 2,
            'home_goals' => 1,
            'away_goals' => 0,
            'status' => 'finished',
        ]);
        $m->setRelation('homeTeam', $tA);
        $m->setRelation('awayTeam', $tB);
        $firstPlace = $calc->firstPlaceTeamIds(collect([$tA, $tB]), collect([$m]));

        return [
            'upsert_same_matchweek' => [
                'test' => 'title prediction snapshot upsert replaces same matchweek',
                'final_snapshots' => $b,
                'expect' => 'one row for MW 0, team 1 at 40%, two standings lines',
            ],
            'favorite_vs_champion' => [
                'test' => 'favorite versus champion track record counts snapshots after matchweek zero through five',
                'input_snapshots' => $snapshots,
                'champion_team_ids' => [20],
                'result' => $track,
                'expect' => 'favorite_total 2, favorite_hits 1, rate_percent 50',
            ],
            'first_place_team_ids' => [
                'test' => 'first place team ids resolves single winner',
                'match' => 'A 1–0 B',
                'first_place_team_ids' => $firstPlace,
                'expect' => '[1]',
            ],
        ];
    }

    private function simulationTeam(int $id, int $power, int $supporter = 10, int $goalkeeper = 10): Team
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

    private function makeStandingsTeam(int $id, string $name, int $sortOrder = 0): Team
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

    /**
     * @param  Collection<int, array<string, mixed>>  $table
     * @return list<array<string, int|string>>
     */
    private function serializeStandingsTable(Collection $table): array
    {
        return $table->map(function (array $row): array {
            /** @var Team $team */
            $team = $row['team'];

            return [
                'team' => $team->name,
                'played' => $row['played'],
                'won' => $row['won'],
                'drawn' => $row['drawn'],
                'lost' => $row['lost'],
                'gf' => $row['gf'],
                'ga' => $row['ga'],
                'gd' => $row['gd'],
                'points' => $row['points'],
            ];
        })->values()->all();
    }
}
