import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
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

const testScenarios = {
    run: Object.assign(run, run),
}

export default testScenarios