<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Championship Monte Carlo iterations
    |--------------------------------------------------------------------------
    |
    | Used when estimating title probabilities. Lower values are faster; higher
    | values are smoother. "Fast" is used during bulk "play all" between weeks,
    | then a final pass uses the default budget for the last snapshot.
    |
    */

    'prediction_monte_carlo_iterations' => (int) env('LEAGUE_PREDICTION_ITERATIONS', 1200),

    'prediction_monte_carlo_iterations_fast' => (int) env('LEAGUE_PREDICTION_ITERATIONS_FAST', 500),

    /*
    |--------------------------------------------------------------------------
    | Title timeline blend (Monte Carlo → uniform prior)
    |--------------------------------------------------------------------------
    |
    | Pure match simulation can assign ~100% to the current leader very early
    | because Poisson draws are correlated across thousands of remaining fixtures.
    | For the phase timeline we blend each club's MC share with a uniform prior.
    | blend = (completed_full_matchweeks / timeline_matchweeks) ^ exponent
    | When no matchweek is fully completed yet, only the uniform prior is used.
    |
    */

    'timeline_matchweeks' => 6,

    'title_probability_timeline_blend_exponent' => (float) env('LEAGUE_TITLE_BLEND_EXPONENT', 2),

];
