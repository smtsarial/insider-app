<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Match simulation (expected-goals style)
    |--------------------------------------------------------------------------
    |
    | Attack ratings use squad power plus supporter lift; defense blends power
    | and goalkeeper quality. Lambdas are derived from attack vs opponent
    | defense, with home advantage and extra dampening when one side is vastly
    | stronger (upsets remain possible via min_underdog_lambda).
    |
    */

    'simulation' => [
        'base_goals_scale' => (float) env('LEAGUE_SIM_BASE_SCALE', 1.38),
        'lambda_exponent' => (float) env('LEAGUE_SIM_LAMBDA_EXP', 0.72),
        'home_attack_multiplier' => (float) env('LEAGUE_SIM_HOME_ATK', 1.09),
        'away_attack_multiplier' => (float) env('LEAGUE_SIM_AWAY_ATK', 0.93),
        'supporter_attack_weight' => (float) env('LEAGUE_SIM_SUPP_W', 0.35),
        'defense_from_power_share' => (float) env('LEAGUE_SIM_DEF_PWR', 0.30),
        'goalkeeper_defense_weight' => (float) env('LEAGUE_SIM_GK_W', 0.62),
        'defense_floor' => (float) env('LEAGUE_SIM_DEF_FLOOR', 9.0),
        'favorite_share_threshold' => (float) env('LEAGUE_SIM_FAV_THRESH', 0.72),
        'underdog_lambda_decay' => (float) env('LEAGUE_SIM_UD_DECAY', 4.2),
        'min_underdog_lambda' => (float) env('LEAGUE_SIM_MIN_UD', 0.065),
        'lambda_cap' => (float) env('LEAGUE_SIM_LAMBDA_CAP', 5.4),
        'lambda_floor' => (float) env('LEAGUE_SIM_LAMBDA_FLOOR', 0.055),
    ],

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

    /*
    |--------------------------------------------------------------------------
    | Championship tie-break inside Monte Carlo
    |--------------------------------------------------------------------------
    |
    | When a simulated final table still ties on points, goal difference, and
    | goals for, title share for that iteration is split by power + form. This
    | floor keeps a tiny-weight team from collapsing to zero when form is poor.
    |
    */

    'prediction_tie_weight_floor' => (float) env('LEAGUE_PRED_TIE_WEIGHT_FLOOR', 0.25),

];
