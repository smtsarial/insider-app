# Champions League Group Simulator

A Laravel + Vue app that simulates a four-team Champions League style group. It draws a double round-robin fixture list, plays the group week by week with a Poisson goal model, keeps a live table, and estimates each team's chance of winning the group after every matchweek.

Seeded teams: Real Madrid, Bayern Munich, Paris Saint-Germain and Inter Milan.

## Features

- **Fixture draw**: double round-robin for four teams, with an optional random slot draw
- **Match engine**: expected-goals style Poisson simulation driven by squad strength, home advantage, supporter boost (attack), goalkeeper quality (defence) and recent form; strong favourites are dampened so upsets stay possible
- **Play controls**: play the next week, play all remaining weeks, or jump back to view any week
- **Edit results**: change any played score and the table and predictions update
- **League table** with points, goal difference and goals scored tie-breakers
- **Title probabilities**: 100%/0% once a team has clinched or is eliminated, otherwise a Monte Carlo run over the remaining fixtures (1,200 iterations; 500 during "play all"), blended toward a uniform prior early in the group
- **Archive** of finished tournaments
- **Test scenarios page** that runs predefined edge cases against the engine

## Stack

- PHP 8.4, Laravel 13, Inertia.js v3, Laravel Wayfinder
- Vue 3 + TypeScript, Tailwind CSS, reka-ui components
- PostgreSQL 18
- Pest for tests, Pint and ESLint for formatting
- Docker: Laravel Sail for development, `compose.prod.yaml` with a production Dockerfile

## How the code is organised

Domain logic lives in `app/League`:

| Folder / class | Responsibility |
|---|---|
| `Fixtures/RoundRobinFixtureGenerator` | Builds the matchweek schedule |
| `Simulation/MatchSimulationEngine` | Simulates a single match |
| `Standings/PremierLeagueStandingsCalculator` | Table and tie-breakers |
| `Form/TeamFormAnalyzer` | Form adjustment from recent results |
| `Prediction/ChampionshipProbabilityEstimator` | Title probabilities |
| `LeagueMatchWeekSimulator`, `LeagueScheduleLoader`, `LeagueSimulationPresenter` | Orchestration and view data |
| `Archive/LeagueTournamentArchiveWriter` | Saves finished tournaments |

All simulation constants (goal scale, home advantage, goalkeeper weight, Monte Carlo iterations, …) are in `config/league.php` and can be overridden with `LEAGUE_*` environment variables.

## Run locally

```bash
cp .env.example .env
composer install
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
npm install
npm run dev
```

Open http://localhost and go to `/league`.

Run the tests:

```bash
./vendor/bin/sail artisan test
```

## Routes

| Route | Purpose |
|---|---|
| `GET /league/tournament-teams` | Team setup |
| `POST /league/fixtures`, `GET /league/fixtures` | Draw and view fixtures |
| `POST /league/simulation/start`, `GET /league/simulation` | Start and view the simulation |
| `POST /league/simulation/play-next`, `/play-all`, `/view-week` | Play controls |
| `PATCH /league/matches/{id}` | Edit a result |
| `POST /league/reset` | Start over |
| `GET /league/test-scenarios`, `POST /league/test-scenarios/run` | Test scenarios |
