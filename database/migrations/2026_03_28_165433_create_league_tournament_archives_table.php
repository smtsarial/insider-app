<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('league_tournament_archives', function (Blueprint $table) {
            $table->id();
            $table->timestamp('archived_at');
            $table->boolean('was_completed')->default(false);
            $table->foreignId('champion_team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->unsignedSmallInteger('finished_matches_count')->default(0);
            $table->string('step_at_archive', 32)->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index('archived_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('league_tournament_archives');
    }
};
