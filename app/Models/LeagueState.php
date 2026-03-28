<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeagueState extends Model
{
    protected $table = 'league_state';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'step',
        'current_view_week',
        'prediction_snapshots',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'current_view_week' => 'integer',
            'prediction_snapshots' => 'array',
        ];
    }

    public static function current(): self
    {
        $row = static::query()->first();
        if ($row === null) {
            $row = static::query()->create([
                'step' => 'teams',
                'current_view_week' => 1,
            ]);
        }

        return $row;
    }
}
