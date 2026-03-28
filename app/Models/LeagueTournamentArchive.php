<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeagueTournamentArchive extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'archived_at',
        'was_completed',
        'champion_team_id',
        'finished_matches_count',
        'step_at_archive',
        'payload',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'archived_at' => 'datetime',
            'was_completed' => 'boolean',
            'finished_matches_count' => 'integer',
            'payload' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function champion(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'champion_team_id');
    }
}
