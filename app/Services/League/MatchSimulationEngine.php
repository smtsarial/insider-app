<?php

namespace App\Services\League;

use App\Models\Team;
use Random\Engine\Mt19937;
use Random\Engine\SecureEngine;
use Random\Randomizer;

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

        $homeEff = $home->power + 15 + $home->supporter_factor * 0.4 + $homeFormBoost;
        $awayEff = $away->power + $away->goalkeeper_factor * 0.5 + $awayFormBoost;

        $sum = $homeEff + $awayEff;
        $base = 2.35;

        $ratioH = $homeEff / $sum;
        $ratioA = $awayEff / $sum;
        $sharpH = $ratioH ** 1.42;
        $sharpA = $ratioA ** 1.42;
        $norm = $sharpH + $sharpA;

        $lambdaHome = max(0.22, $base * ($sharpH / $norm) * 1.16);
        $lambdaAway = max(0.22, $base * ($sharpA / $norm) * 0.86);

        $gh = min(self::MAX_GOALS, $this->poisson($randomizer, $lambdaHome));
        $ga = min(self::MAX_GOALS, $this->poisson($randomizer, $lambdaAway));

        return [$gh, $ga];
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
