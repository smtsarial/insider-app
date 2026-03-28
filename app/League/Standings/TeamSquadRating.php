<?php

namespace App\League\Standings;

use App\Models\Team;

/**
 * Intrinsic squad strength used for table tie-breaks and prediction weighting.
 */
final class TeamSquadRating
{
    public static function intrinsic(Team $team): float
    {
        return $team->power
            + $team->supporter_factor * 0.4
            + $team->goalkeeper_factor * 0.5;
    }
}
