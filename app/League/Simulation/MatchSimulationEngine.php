<?php

namespace App\League\Simulation;

use App\Models\Team;
use Random\Engine\Mt19937;
use Random\Engine\SecureEngine;
use Random\Randomizer;

/**
 * Week-by-week match outcomes from fixture list: Poisson goals driven by squad
 * strength (power), home advantage, supporters (attack), goalkeepers (defense),
 * and optional form boosts. Large rating gaps strongly favour the better side
 * while a small floor on the weaker attack keeps rare upsets possible.
 */
class MatchSimulationEngine
{
    private const int MAX_GOALS = 8;

    /**
     * @return array{0: int, 1: int} [home_goals, away_goals]
     */
    public function simulate(Team $home, Team $away, ?int $seed = null, float $homeFormBoost = 0.0, float $awayFormBoost = 0.0): array
    {
        $engine = $seed !== null
            ? new Mt19937($seed)
            : new SecureEngine;

        $randomizer = new Randomizer($engine);

        $cfg = config('league.simulation', []);

        $homeAttack = $this->attackRating($home, $homeFormBoost, $cfg);
        $awayAttack = $this->attackRating($away, $awayFormBoost, $cfg);
        $homeDefense = $this->defenseRating($home, $cfg);
        $awayDefense = $this->defenseRating($away, $cfg);

        $base = (float) ($cfg['base_goals_scale'] ?? 1.38);
        $exp = (float) ($cfg['lambda_exponent'] ?? 0.72);
        $homeAtkMul = (float) ($cfg['home_attack_multiplier'] ?? 1.09);
        $awayAtkMul = (float) ($cfg['away_attack_multiplier'] ?? 0.93);

        $lambdaHome = $base * ($homeAttack / $awayDefense) ** $exp * $homeAtkMul;
        $lambdaAway = $base * ($awayAttack / $homeDefense) ** $exp * $awayAtkMul;

        $this->applyMismatchDampening($lambdaHome, $lambdaAway, $homeAttack, $awayAttack, $cfg);

        $lambdaCap = (float) ($cfg['lambda_cap'] ?? 5.4);
        $lambdaFloor = (float) ($cfg['lambda_floor'] ?? 0.055);
        $lambdaHome = max($lambdaFloor, min($lambdaCap, $lambdaHome));
        $lambdaAway = max($lambdaFloor, min($lambdaCap, $lambdaAway));

        $gh = min(self::MAX_GOALS, $this->poisson($randomizer, $lambdaHome));
        $ga = min(self::MAX_GOALS, $this->poisson($randomizer, $lambdaAway));

        return [$gh, $ga];
    }

    /**
     * @param  array<string, mixed>  $cfg
     */
    private function attackRating(Team $team, float $formBoost, array $cfg): float
    {
        $wS = (float) ($cfg['supporter_attack_weight'] ?? 0.35);

        return max(1.0, (float) $team->power + $formBoost + $wS * (float) $team->supporter_factor);
    }

    /**
     * @param  array<string, mixed>  $cfg
     */
    private function defenseRating(Team $team, array $cfg): float
    {
        $wP = (float) ($cfg['defense_from_power_share'] ?? 0.30);
        $wG = (float) ($cfg['goalkeeper_defense_weight'] ?? 0.62);
        $floor = (float) ($cfg['defense_floor'] ?? 9.0);

        return max($floor, $wP * (float) $team->power + $wG * (float) $team->goalkeeper_factor);
    }

    /**
     * When one attack is far stronger, shrink the underdog’s expected goals so
     * favourites win most matches, but keep {@see min_underdog_lambda} so an upset
     * is never impossible (see config league.simulation.min_underdog_lambda).
     *
     * @param  array<string, mixed>  $cfg
     */
    private function applyMismatchDampening(
        float &$lambdaHome,
        float &$lambdaAway,
        float $homeAttack,
        float $awayAttack,
        array $cfg,
    ): void {
        $total = $homeAttack + $awayAttack;
        if ($total <= 0.0) {
            return;
        }

        $shareHome = $homeAttack / $total;
        $thresh = (float) ($cfg['favorite_share_threshold'] ?? 0.72);
        $decay = (float) ($cfg['underdog_lambda_decay'] ?? 4.2);
        $minUd = (float) ($cfg['min_underdog_lambda'] ?? 0.065);

        if ($shareHome >= $thresh) {
            $excess = $shareHome - 0.5;
            $lambdaAway *= exp(-$excess * $decay);
            $lambdaAway = max($minUd, $lambdaAway);
        } elseif ($shareHome <= (1.0 - $thresh)) {
            $excess = 0.5 - $shareHome;
            $lambdaHome *= exp(-$excess * $decay);
            $lambdaHome = max($minUd, $lambdaHome);
        }
    }

    private function poisson(Randomizer $randomizer, float $lambda): int
    {
        if ($lambda <= 0) {
            return 0;
        }

        $l = exp(-$lambda);
        $k = 0;
        $p = 1.0;

        do {
            $k++;
            $p *= $randomizer->getFloat(0.0, 1.0);
        } while ($p > $l && $k < 30);

        return min(self::MAX_GOALS, $k - 1);
    }
}
