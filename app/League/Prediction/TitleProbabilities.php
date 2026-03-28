<?php

namespace App\League\Prediction;

use App\Models\Team;
use Illuminate\Support\Collection;

/**
 * Title chance estimates (0–100%) keyed by team id, produced by {@see ChampionshipProbabilityEstimator}.
 */
final readonly class TitleProbabilities
{
    /**
     * @param  array<int, float>  $percentByTeamId
     */
    public function __construct(private array $percentByTeamId) {}

    /**
     * @param  Collection<int, Team>  $teams
     */
    public static function allZeros(Collection $teams): self
    {
        $z = [];
        foreach ($teams as $t) {
            $z[$t->id] = 0.0;
        }

        return new self($z);
    }

    /**
     * @param  array<int, float>  $percentByTeamId
     */
    public static function fromPercentByTeamId(array $percentByTeamId): self
    {
        return new self($percentByTeamId);
    }

    /**
     * @return array<int, float>
     */
    public function toArray(): array
    {
        return $this->percentByTeamId;
    }

    public function forTeam(int $teamId): float
    {
        return $this->percentByTeamId[$teamId] ?? 0.0;
    }
}
