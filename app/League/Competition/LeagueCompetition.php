<?php

namespace App\League\Competition;

use App\Models\LeagueMatch;
use App\Models\Team;
use Illuminate\Support\Collection;

/**
 * In-memory view of the current mini-league: ordered clubs and full fixture list.
 * Encapsulates schedule queries used by standings, simulation, and title prediction.
 */
final readonly class LeagueCompetition
{
    /**
     * @param  Collection<int, Team>  $teams
     * @param  Collection<int, LeagueMatch>  $matches
     */
    public function __construct(
        private Collection $teams,
        private Collection $matches,
    ) {}

    /**
     * @param  Collection<int, Team>  $teams
     * @param  Collection<int, LeagueMatch>  $matches
     */
    public static function fromCollections(Collection $teams, Collection $matches): self
    {
        return new self($teams, $matches->values());
    }

    /**
     * @return Collection<int, Team>
     */
    public function teams(): Collection
    {
        return $this->teams;
    }

    /**
     * @return Collection<int, LeagueMatch>
     */
    public function matches(): Collection
    {
        return $this->matches;
    }

    /**
     * @return Collection<int, LeagueMatch>
     */
    public function finishedMatches(): Collection
    {
        return $this->matches->filter(fn (LeagueMatch $m): bool => $m->isFinished())->values();
    }

    /**
     * @return Collection<int, LeagueMatch>
     */
    public function scheduledMatchesSorted(): Collection
    {
        return $this->matches
            ->filter(fn (LeagueMatch $m): bool => ! $m->isFinished())
            ->sortBy(fn (LeagueMatch $m): array => [$m->matchweek, $m->id])
            ->values();
    }

    public function lastFullyCompletedMatchweek(): int
    {
        $max = 0;
        foreach ($this->matches->groupBy('matchweek') as $week => $inWeek) {
            if ($inWeek->isEmpty()) {
                continue;
            }
            if ($inWeek->every(fn (LeagueMatch $m): bool => $m->isFinished())) {
                $max = max($max, (int) $week);
            }
        }

        return $max;
    }

    public function nextPlayableMatchweek(): ?int
    {
        $first = $this->matches
            ->filter(fn (LeagueMatch $m): bool => $m->status === 'scheduled')
            ->sortBy(fn (LeagueMatch $m): array => [$m->matchweek, $m->id])
            ->first();

        return $first ? (int) $first->matchweek : null;
    }

    public function seasonComplete(): bool
    {
        return $this->nextPlayableMatchweek() === null && $this->matches->isNotEmpty();
    }

    public function isTitlePredictionActive(): bool
    {
        return $this->lastFullyCompletedMatchweek() >= 3 || $this->seasonComplete();
    }

    public function hasMatches(): bool
    {
        return $this->matches->isNotEmpty();
    }

    /**
     * Finished fixtures on or before a matchweek (snapshot standings at a timeline point).
     *
     * @return Collection<int, LeagueMatch>
     */
    public function finishedThroughMatchweek(int $matchweek): Collection
    {
        return $this->matches
            ->filter(fn (LeagueMatch $m): bool => $m->isFinished() && (int) $m->matchweek <= $matchweek)
            ->values();
    }
}
