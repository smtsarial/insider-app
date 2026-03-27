<?php

namespace Database\Seeders;

use App\Models\LeagueState;
use App\Models\Team;
use Illuminate\Database\Seeder;

class LeagueSeeder extends Seeder
{
    public function run(): void
    {
        $teams = [
            ['name' => 'Arsenal', 'short_name' => 'ARS', 'power' => 84, 'sort_order' => 1],
            ['name' => 'Liverpool', 'short_name' => 'LIV', 'power' => 90, 'sort_order' => 2],
            ['name' => 'Manchester City', 'short_name' => 'MCI', 'power' => 89, 'sort_order' => 3],
            ['name' => 'Chelsea', 'short_name' => 'CHE', 'power' => 82, 'sort_order' => 4],
        ];

        foreach ($teams as $data) {
            Team::query()->updateOrCreate(
                ['name' => $data['name']],
                [
                    'short_name' => $data['short_name'],
                    'power' => $data['power'],
                    'sort_order' => $data['sort_order'],
                    'supporter_factor' => 10,
                    'goalkeeper_factor' => 10,
                ]
            );
        }

        LeagueState::query()->delete();
        LeagueState::query()->create([
            'step' => 'teams',
            'current_view_week' => 1,
        ]);
    }
}
