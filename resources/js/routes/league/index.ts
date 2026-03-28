import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
import simulation73c602 from './simulation'
import matches from './matches'
/**
* @see routes/web.php:9
* @route '/league'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/league',
} satisfies RouteDefinition<["get","head"]>

/**
* @see routes/web.php:9
* @route '/league'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see routes/web.php:9
* @route '/league'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see routes/web.php:9
* @route '/league'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see routes/web.php:9
* @route '/league'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see routes/web.php:9
* @route '/league'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see routes/web.php:9
* @route '/league'
*/
indexForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index.form = indexForm

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

const league = {
    index: Object.assign(index, index),
    tournamentTeams: Object.assign(tournamentTeams, tournamentTeams),
    fixtures: Object.assign(fixtures, fixtures),
    simulation: Object.assign(simulation, simulation73c602),
    reset: Object.assign(reset, reset),
    matches: Object.assign(matches, matches),
}

export default league