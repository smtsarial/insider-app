import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\LeagueController::update
* @see app/Http/Controllers/LeagueController.php:185
* @route '/league/matches/{league_match}'
*/
export const update = (args: { league_match: string | number } | [league_match: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

update.definition = {
    methods: ["patch"],
    url: '/league/matches/{league_match}',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\LeagueController::update
* @see app/Http/Controllers/LeagueController.php:185
* @route '/league/matches/{league_match}'
*/
update.url = (args: { league_match: string | number } | [league_match: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return update.definition.url
            .replace('{league_match}', parsedArgs.league_match.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\LeagueController::update
* @see app/Http/Controllers/LeagueController.php:185
* @route '/league/matches/{league_match}'
*/
update.patch = (args: { league_match: string | number } | [league_match: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\LeagueController::update
* @see app/Http/Controllers/LeagueController.php:185
* @route '/league/matches/{league_match}'
*/
const updateForm = (args: { league_match: string | number } | [league_match: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LeagueController::update
* @see app/Http/Controllers/LeagueController.php:185
* @route '/league/matches/{league_match}'
*/
updateForm.patch = (args: { league_match: string | number } | [league_match: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

update.form = updateForm

const matches = {
    update: Object.assign(update, update),
}

export default matches