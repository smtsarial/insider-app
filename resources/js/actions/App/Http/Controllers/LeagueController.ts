import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\LeagueController::home
* @see app/Http/Controllers/LeagueController.php:43
* @route '/'
*/
export const home = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: home.url(options),
    method: 'get',
})

home.definition = {
    methods: ["get","head"],
    url: '/',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\LeagueController::home
* @see app/Http/Controllers/LeagueController.php:43
* @route '/'
*/
home.url = (options?: RouteQueryOptions) => {
    return home.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\LeagueController::home
* @see app/Http/Controllers/LeagueController.php:43
* @route '/'
*/
home.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: home.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LeagueController::home
* @see app/Http/Controllers/LeagueController.php:43
* @route '/'
*/
home.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: home.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\LeagueController::home
* @see app/Http/Controllers/LeagueController.php:43
* @route '/'
*/
const homeForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: home.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LeagueController::home
* @see app/Http/Controllers/LeagueController.php:43
* @route '/'
*/
homeForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: home.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LeagueController::home
* @see app/Http/Controllers/LeagueController.php:43
* @route '/'
*/
homeForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: home.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

home.form = homeForm

/**
* @see \App\Http\Controllers\LeagueController::tournamentTeams
* @see app/Http/Controllers/LeagueController.php:56
* @route '/league/tournament-teams'
*/
export const tournamentTeams = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: tournamentTeams.url(options),
    method: 'get',
})

tournamentTeams.definition = {
    methods: ["get","head"],
    url: '/league/tournament-teams',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\LeagueController::tournamentTeams
* @see app/Http/Controllers/LeagueController.php:56
* @route '/league/tournament-teams'
*/
tournamentTeams.url = (options?: RouteQueryOptions) => {
    return tournamentTeams.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\LeagueController::tournamentTeams
* @see app/Http/Controllers/LeagueController.php:56
* @route '/league/tournament-teams'
*/
tournamentTeams.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: tournamentTeams.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LeagueController::tournamentTeams
* @see app/Http/Controllers/LeagueController.php:56
* @route '/league/tournament-teams'
*/
tournamentTeams.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: tournamentTeams.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\LeagueController::tournamentTeams
* @see app/Http/Controllers/LeagueController.php:56
* @route '/league/tournament-teams'
*/
const tournamentTeamsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: tournamentTeams.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LeagueController::tournamentTeams
* @see app/Http/Controllers/LeagueController.php:56
* @route '/league/tournament-teams'
*/
tournamentTeamsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: tournamentTeams.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LeagueController::tournamentTeams
* @see app/Http/Controllers/LeagueController.php:56
* @route '/league/tournament-teams'
*/
tournamentTeamsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: tournamentTeams.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

tournamentTeams.form = tournamentTeamsForm

/**
* @see \App\Http\Controllers\LeagueController::storeFixtures
* @see app/Http/Controllers/LeagueController.php:69
* @route '/league/fixtures'
*/
export const storeFixtures = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeFixtures.url(options),
    method: 'post',
})

storeFixtures.definition = {
    methods: ["post"],
    url: '/league/fixtures',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\LeagueController::storeFixtures
* @see app/Http/Controllers/LeagueController.php:69
* @route '/league/fixtures'
*/
storeFixtures.url = (options?: RouteQueryOptions) => {
    return storeFixtures.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\LeagueController::storeFixtures
* @see app/Http/Controllers/LeagueController.php:69
* @route '/league/fixtures'
*/
storeFixtures.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeFixtures.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LeagueController::storeFixtures
* @see app/Http/Controllers/LeagueController.php:69
* @route '/league/fixtures'
*/
const storeFixturesForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeFixtures.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LeagueController::storeFixtures
* @see app/Http/Controllers/LeagueController.php:69
* @route '/league/fixtures'
*/
storeFixturesForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeFixtures.url(options),
    method: 'post',
})

storeFixtures.form = storeFixturesForm

/**
* @see \App\Http\Controllers\LeagueController::fixtures
* @see app/Http/Controllers/LeagueController.php:117
* @route '/league/fixtures'
*/
export const fixtures = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: fixtures.url(options),
    method: 'get',
})

fixtures.definition = {
    methods: ["get","head"],
    url: '/league/fixtures',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\LeagueController::fixtures
* @see app/Http/Controllers/LeagueController.php:117
* @route '/league/fixtures'
*/
fixtures.url = (options?: RouteQueryOptions) => {
    return fixtures.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\LeagueController::fixtures
* @see app/Http/Controllers/LeagueController.php:117
* @route '/league/fixtures'
*/
fixtures.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: fixtures.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LeagueController::fixtures
* @see app/Http/Controllers/LeagueController.php:117
* @route '/league/fixtures'
*/
fixtures.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: fixtures.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\LeagueController::fixtures
* @see app/Http/Controllers/LeagueController.php:117
* @route '/league/fixtures'
*/
const fixturesForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: fixtures.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LeagueController::fixtures
* @see app/Http/Controllers/LeagueController.php:117
* @route '/league/fixtures'
*/
fixturesForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: fixtures.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LeagueController::fixtures
* @see app/Http/Controllers/LeagueController.php:117
* @route '/league/fixtures'
*/
fixturesForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: fixtures.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

fixtures.form = fixturesForm

/**
* @see \App\Http\Controllers\LeagueController::startSimulation
* @see app/Http/Controllers/LeagueController.php:137
* @route '/league/simulation/start'
*/
export const startSimulation = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: startSimulation.url(options),
    method: 'post',
})

startSimulation.definition = {
    methods: ["post"],
    url: '/league/simulation/start',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\LeagueController::startSimulation
* @see app/Http/Controllers/LeagueController.php:137
* @route '/league/simulation/start'
*/
startSimulation.url = (options?: RouteQueryOptions) => {
    return startSimulation.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\LeagueController::startSimulation
* @see app/Http/Controllers/LeagueController.php:137
* @route '/league/simulation/start'
*/
startSimulation.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: startSimulation.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LeagueController::startSimulation
* @see app/Http/Controllers/LeagueController.php:137
* @route '/league/simulation/start'
*/
const startSimulationForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: startSimulation.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LeagueController::startSimulation
* @see app/Http/Controllers/LeagueController.php:137
* @route '/league/simulation/start'
*/
startSimulationForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: startSimulation.url(options),
    method: 'post',
})

startSimulation.form = startSimulationForm

/**
* @see \App\Http\Controllers\LeagueController::simulation
* @see app/Http/Controllers/LeagueController.php:158
* @route '/league/simulation'
*/
export const simulation = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: simulation.url(options),
    method: 'get',
})

simulation.definition = {
    methods: ["get","head"],
    url: '/league/simulation',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\LeagueController::simulation
* @see app/Http/Controllers/LeagueController.php:158
* @route '/league/simulation'
*/
simulation.url = (options?: RouteQueryOptions) => {
    return simulation.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\LeagueController::simulation
* @see app/Http/Controllers/LeagueController.php:158
* @route '/league/simulation'
*/
simulation.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: simulation.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LeagueController::simulation
* @see app/Http/Controllers/LeagueController.php:158
* @route '/league/simulation'
*/
simulation.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: simulation.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\LeagueController::simulation
* @see app/Http/Controllers/LeagueController.php:158
* @route '/league/simulation'
*/
const simulationForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: simulation.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LeagueController::simulation
* @see app/Http/Controllers/LeagueController.php:158
* @route '/league/simulation'
*/
simulationForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: simulation.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LeagueController::simulation
* @see app/Http/Controllers/LeagueController.php:158
* @route '/league/simulation'
*/
simulationForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: simulation.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

simulation.form = simulationForm

/**
* @see \App\Http\Controllers\LeagueController::setViewWeek
* @see app/Http/Controllers/LeagueController.php:182
* @route '/league/simulation/view-week'
*/
export const setViewWeek = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: setViewWeek.url(options),
    method: 'post',
})

setViewWeek.definition = {
    methods: ["post"],
    url: '/league/simulation/view-week',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\LeagueController::setViewWeek
* @see app/Http/Controllers/LeagueController.php:182
* @route '/league/simulation/view-week'
*/
setViewWeek.url = (options?: RouteQueryOptions) => {
    return setViewWeek.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\LeagueController::setViewWeek
* @see app/Http/Controllers/LeagueController.php:182
* @route '/league/simulation/view-week'
*/
setViewWeek.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: setViewWeek.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LeagueController::setViewWeek
* @see app/Http/Controllers/LeagueController.php:182
* @route '/league/simulation/view-week'
*/
const setViewWeekForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: setViewWeek.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LeagueController::setViewWeek
* @see app/Http/Controllers/LeagueController.php:182
* @route '/league/simulation/view-week'
*/
setViewWeekForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: setViewWeek.url(options),
    method: 'post',
})

setViewWeek.form = setViewWeekForm

/**
* @see \App\Http\Controllers\LeagueController::playNextWeek
* @see app/Http/Controllers/LeagueController.php:194
* @route '/league/simulation/play-next'
*/
export const playNextWeek = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: playNextWeek.url(options),
    method: 'post',
})

playNextWeek.definition = {
    methods: ["post"],
    url: '/league/simulation/play-next',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\LeagueController::playNextWeek
* @see app/Http/Controllers/LeagueController.php:194
* @route '/league/simulation/play-next'
*/
playNextWeek.url = (options?: RouteQueryOptions) => {
    return playNextWeek.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\LeagueController::playNextWeek
* @see app/Http/Controllers/LeagueController.php:194
* @route '/league/simulation/play-next'
*/
playNextWeek.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: playNextWeek.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LeagueController::playNextWeek
* @see app/Http/Controllers/LeagueController.php:194
* @route '/league/simulation/play-next'
*/
const playNextWeekForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: playNextWeek.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LeagueController::playNextWeek
* @see app/Http/Controllers/LeagueController.php:194
* @route '/league/simulation/play-next'
*/
playNextWeekForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: playNextWeek.url(options),
    method: 'post',
})

playNextWeek.form = playNextWeekForm

/**
* @see \App\Http\Controllers\LeagueController::playAllWeeks
* @see app/Http/Controllers/LeagueController.php:212
* @route '/league/simulation/play-all'
*/
export const playAllWeeks = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: playAllWeeks.url(options),
    method: 'post',
})

playAllWeeks.definition = {
    methods: ["post"],
    url: '/league/simulation/play-all',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\LeagueController::playAllWeeks
* @see app/Http/Controllers/LeagueController.php:212
* @route '/league/simulation/play-all'
*/
playAllWeeks.url = (options?: RouteQueryOptions) => {
    return playAllWeeks.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\LeagueController::playAllWeeks
* @see app/Http/Controllers/LeagueController.php:212
* @route '/league/simulation/play-all'
*/
playAllWeeks.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: playAllWeeks.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LeagueController::playAllWeeks
* @see app/Http/Controllers/LeagueController.php:212
* @route '/league/simulation/play-all'
*/
const playAllWeeksForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: playAllWeeks.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LeagueController::playAllWeeks
* @see app/Http/Controllers/LeagueController.php:212
* @route '/league/simulation/play-all'
*/
playAllWeeksForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: playAllWeeks.url(options),
    method: 'post',
})

playAllWeeks.form = playAllWeeksForm

/**
* @see \App\Http\Controllers\LeagueController::reset
* @see app/Http/Controllers/LeagueController.php:238
* @route '/league/reset'
*/
export const reset = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reset.url(options),
    method: 'post',
})

reset.definition = {
    methods: ["post"],
    url: '/league/reset',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\LeagueController::reset
* @see app/Http/Controllers/LeagueController.php:238
* @route '/league/reset'
*/
reset.url = (options?: RouteQueryOptions) => {
    return reset.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\LeagueController::reset
* @see app/Http/Controllers/LeagueController.php:238
* @route '/league/reset'
*/
reset.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reset.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LeagueController::reset
* @see app/Http/Controllers/LeagueController.php:238
* @route '/league/reset'
*/
const resetForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reset.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LeagueController::reset
* @see app/Http/Controllers/LeagueController.php:238
* @route '/league/reset'
*/
resetForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reset.url(options),
    method: 'post',
})

reset.form = resetForm

/**
* @see \App\Http\Controllers\LeagueController::updateMatch
* @see app/Http/Controllers/LeagueController.php:255
* @route '/league/matches/{league_match}'
*/
export const updateMatch = (args: { league_match: string | number } | [league_match: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: updateMatch.url(args, options),
    method: 'patch',
})

updateMatch.definition = {
    methods: ["patch"],
    url: '/league/matches/{league_match}',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\LeagueController::updateMatch
* @see app/Http/Controllers/LeagueController.php:255
* @route '/league/matches/{league_match}'
*/
updateMatch.url = (args: { league_match: string | number } | [league_match: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { league_match: args }
    }

    if (Array.isArray(args)) {
        args = {
            league_match: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        league_match: args.league_match,
    }

    return updateMatch.definition.url
            .replace('{league_match}', parsedArgs.league_match.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\LeagueController::updateMatch
* @see app/Http/Controllers/LeagueController.php:255
* @route '/league/matches/{league_match}'
*/
updateMatch.patch = (args: { league_match: string | number } | [league_match: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: updateMatch.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\LeagueController::updateMatch
* @see app/Http/Controllers/LeagueController.php:255
* @route '/league/matches/{league_match}'
*/
const updateMatchForm = (args: { league_match: string | number } | [league_match: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateMatch.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LeagueController::updateMatch
* @see app/Http/Controllers/LeagueController.php:255
* @route '/league/matches/{league_match}'
*/
updateMatchForm.patch = (args: { league_match: string | number } | [league_match: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateMatch.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

updateMatch.form = updateMatchForm

const LeagueController = { home, tournamentTeams, storeFixtures, fixtures, startSimulation, simulation, setViewWeek, playNextWeek, playAllWeeks, reset, updateMatch }

export default LeagueController