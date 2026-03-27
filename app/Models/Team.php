<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    /** @use HasFactory<\Database\Factories\TeamFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'short_name',
        'power',
        'supporter_factor',
        'goalkeeper_factor',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'power' => 'integer',
            'supporter_factor' => 'integer',
            'goalkeeper_factor' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return HasMany<LeagueMatch, $this>
     */
    public function homeMatches(): HasMany
    {
        return $this->hasMany(LeagueMatch::class, 'home_team_id');
    }

    /**
     * @return HasMany<LeagueMatch, $this>
     */
    public function awayMatches(): HasMany
    {
        return $this->hasMany(LeagueMatch::class, 'away_team_id');
    }
}
