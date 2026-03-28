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
            ['name' => 'Real Madrid', 'short_name' => 'RMA', 'power' => 93, 'sort_order' => 1],
            ['name' => 'Bayern Munich', 'short_name' => 'BAY', 'power' => 89, 'sort_order' => 2],
            ['name' => 'Paris Saint-Germain', 'short_name' => 'PSG', 'power' => 87, 'sort_order' => 3],
            ['name' => 'Inter Milan', 'short_name' => 'INT', 'power' => 84, 'sort_order' => 4],
        ];

        foreach ($teams as $data) {
            Team::query()->updateOrCreate(
                ['sort_order' => $data['sort_order']],
                [
                    'name' => $data['name'],
                    'short_name' => $data['short_name'],
                    'power' => $data['power'],
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
