import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\LeagueTestScenariosController::show
* @see app/Http/Controllers/LeagueTestScenariosController.php:24
* @route '/league/test-scenarios'
*/
export const show = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/league/test-scenarios',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\LeagueTestScenariosController::show
* @see app/Http/Controllers/LeagueTestScenariosController.php:24
* @route '/league/test-scenarios'
*/
show.url = (options?: RouteQueryOptions) => {
    return show.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\LeagueTestScenariosController::show
* @see app/Http/Controllers/LeagueTestScenariosController.php:24
* @route '/league/test-scenarios'
*/
show.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LeagueTestScenariosController::show
* @see app/Http/Controllers/LeagueTestScenariosController.php:24
* @route '/league/test-scenarios'
*/
show.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\LeagueTestScenariosController::show
* @see app/Http/Controllers/LeagueTestScenariosController.php:24
* @route '/league/test-scenarios'
*/
const showForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LeagueTestScenariosController::show
* @see app/Http/Controllers/LeagueTestScenariosController.php:24
* @route '/league/test-scenarios'
*/
showForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LeagueTestScenariosController::show
* @see app/Http/Controllers/LeagueTestScenariosController.php:24
* @route '/league/test-scenarios'
*/
showForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

/**
* @see \App\Http\Controllers\LeagueTestScenariosController::run
* @see app/Http/Controllers/LeagueTestScenariosController.php:44
* @route '/league/test-scenarios/run'
*/
export const run = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: run.url(options),
    method: 'post',
})

run.definition = {
    methods: ["post"],
    url: '/league/test-scenarios/run',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\LeagueTestScenariosController::run
* @see app/Http/Controllers/LeagueTestScenariosController.php:44
* @route '/league/test-scenarios/run'
*/
run.url = (options?: RouteQueryOptions) => {
    return run.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\LeagueTestScenariosController::run
* @see app/Http/Controllers/LeagueTestScenariosController.php:44
* @route '/league/test-scenarios/run'
*/
run.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: run.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LeagueTestScenariosController::run
* @see app/Http/Controllers/LeagueTestScenariosController.php:44
* @route '/league/test-scenarios/run'
*/
const runForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: run.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LeagueTestScenariosController::run
* @see app/Http/Controllers/LeagueTestScenariosController.php:44
* @route '/league/test-scenarios/run'
*/
runForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: run.url(options),
    method: 'post',
})

run.form = runForm

const LeagueTestScenariosController = { show, run }

export default LeagueTestScenariosController