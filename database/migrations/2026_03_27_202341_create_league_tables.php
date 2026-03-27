<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('short_name', 8);
            $table->unsignedSmallInteger('power');
            $table->unsignedTinyInteger('supporter_factor')->default(10);
            $table->unsignedTinyInteger('goalkeeper_factor')->default(10);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('league_state', function (Blueprint $table) {
            $table->id();
            $table->string('step')->default('teams');
            $table->unsignedTinyInteger('current_view_week')->default(1);
            $table->timestamps();
        });

        Schema::create('league_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('home_team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('away_team_id')->constrained('teams')->cascadeOnDelete();
            $table->unsignedTinyInteger('matchweek');
            $table->unsignedTinyInteger('home_goals')->nullable();
            $table->unsignedTinyInteger('away_goals')->nullable();
            $table->string('status')->default('scheduled');
            $table->boolean('is_manually_edited')->default(false);
            $table->timestamps();

            $table->index(['matchweek', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('league_matches');
        Schema::dropIfExists('league_state');
        Schema::dropIfExists('teams');
    }
};
