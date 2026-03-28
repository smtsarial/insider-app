import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\LeagueController::start
* @see app/Http/Controllers/LeagueController.php:137
* @route '/league/simulation/start'
*/
export const start = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: start.url(options),
    method: 'post',
})

start.definition = {
    methods: ["post"],
    url: '/league/simulation/start',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\LeagueController::start
* @see app/Http/Controllers/LeagueController.php:137
* @route '/league/simulation/start'
*/
start.url = (options?: RouteQueryOptions) => {
    return start.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\LeagueController::start
* @see app/Http/Controllers/LeagueController.php:137
* @route '/league/simulation/start'
*/
start.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: start.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LeagueController::start
* @see app/Http/Controllers/LeagueController.php:137
* @route '/league/simulation/start'
*/
const startForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: start.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LeagueController::start
* @see app/Http/Controllers/LeagueController.php:137
* @route '/league/simulation/start'
*/
startForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: start.url(options),
    method: 'post',
})

start.form = startForm

/**
* @see \App\Http\Controllers\LeagueController::viewWeek
* @see app/Http/Controllers/LeagueController.php:182
* @route '/league/simulation/view-week'
*/
export const viewWeek = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: viewWeek.url(options),
    method: 'post',
})

viewWeek.definition = {
    methods: ["post"],
    url: '/league/simulation/view-week',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\LeagueController::viewWeek
* @see app/Http/Controllers/LeagueController.php:182
* @route '/league/simulation/view-week'
*/
viewWeek.url = (options?: RouteQueryOptions) => {
    return viewWeek.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\LeagueController::viewWeek
* @see app/Http/Controllers/LeagueController.php:182
* @route '/league/simulation/view-week'
*/
viewWeek.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: viewWeek.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LeagueController::viewWeek
* @see app/Http/Controllers/LeagueController.php:182
* @route '/league/simulation/view-week'
*/
const viewWeekForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: viewWeek.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LeagueController::viewWeek
* @see app/Http/Controllers/LeagueController.php:182
* @route '/league/simulation/view-week'
*/
viewWeekForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: viewWeek.url(options),
    method: 'post',
})

viewWeek.form = viewWeekForm

/**
* @see \App\Http\Controllers\LeagueController::playNext
* @see app/Http/Controllers/LeagueController.php:194
* @route '/league/simulation/play-next'
*/
export const playNext = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: playNext.url(options),
    method: 'post',
})

playNext.definition = {
    methods: ["post"],
    url: '/league/simulation/play-next',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\LeagueController::playNext
* @see app/Http/Controllers/LeagueController.php:194
* @route '/league/simulation/play-next'
*/
playNext.url = (options?: RouteQueryOptions) => {
    return playNext.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\LeagueController::playNext
* @see app/Http/Controllers/LeagueController.php:194
* @route '/league/simulation/play-next'
*/
playNext.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: playNext.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LeagueController::playNext
* @see app/Http/Controllers/LeagueController.php:194
* @route '/league/simulation/play-next'
*/
const playNextForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: playNext.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LeagueController::playNext
* @see app/Http/Controllers/LeagueController.php:194
* @route '/league/simulation/play-next'
*/
playNextForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: playNext.url(options),
    method: 'post',
})

playNext.form = playNextForm

/**
* @see \App\Http\Controllers\LeagueController::playAll
* @see app/Http/Controllers/LeagueController.php:212
* @route '/league/simulation/play-all'
*/
export const playAll = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: playAll.url(options),
    method: 'post',
})

playAll.definition = {
    methods: ["post"],
    url: '/league/simulation/play-all',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\LeagueController::playAll
* @see app/Http/Controllers/LeagueController.php:212
* @route '/league/simulation/play-all'
*/
playAll.url = (options?: RouteQueryOptions) => {
    return playAll.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\LeagueController::playAll
* @see app/Http/Controllers/LeagueController.php:212
* @route '/league/simulation/play-all'
*/
playAll.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: playAll.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LeagueController::playAll
* @see app/Http/Controllers/LeagueController.php:212
* @route '/league/simulation/play-all'
*/
const playAllForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: playAll.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LeagueController::playAll
* @see app/Http/Controllers/LeagueController.php:212
* @route '/league/simulation/play-all'
*/
playAllForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: playAll.url(options),
    method: 'post',
})

playAll.form = playAllForm

const simulation = {
    viewWeek: Object.assign(viewWeek, viewWeek),
    playNext: Object.assign(playNext, playNext),
    playAll: Object.assign(playAll, playAll),
}

export default simulation