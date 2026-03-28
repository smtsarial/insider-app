import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\LeagueController::store
* @see app/Http/Controllers/LeagueController.php:68
* @route '/league/fixtures'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/league/fixtures',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\LeagueController::store
* @see app/Http/Controllers/LeagueController.php:68
* @route '/league/fixtures'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\LeagueController::store
* @see app/Http/Controllers/LeagueController.php:68
* @route '/league/fixtures'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LeagueController::store
* @see app/Http/Controllers/LeagueController.php:68
* @route '/league/fixtures'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LeagueController::store
* @see app/Http/Controllers/LeagueController.php:68
* @route '/league/fixtures'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm
