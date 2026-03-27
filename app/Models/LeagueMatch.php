<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeagueMatch extends Model
{
    protected $table = 'league_matches';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'home_team_id',
        'away_team_id',
        'matchweek',
        'home_goals',
        'away_goals',
        'status',
        'is_manually_edited',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'matchweek' => 'integer',
            'home_goals' => 'integer',
            'away_goals' => 'integer',
            'is_manually_edited' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    public function isFinished(): bool
    {
        return $this->status === 'finished';
    }
}
