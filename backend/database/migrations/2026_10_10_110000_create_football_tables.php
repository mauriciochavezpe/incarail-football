<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Copia local de football-data.org.
 *
 * Las claves primarias son los IDs de football-data (no autoincrementales):
 * así importar la misma liga dos veces actualiza en lugar de duplicar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competitions', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('code', 10)->nullable()->unique(); // PL, PD, CL...
            $table->string('name');
            $table->string('type', 20)->nullable();           // LEAGUE, CUP
            $table->string('plan', 20)->nullable();           // TIER_ONE...
            $table->string('emblem')->nullable();
            $table->string('area_name')->nullable();
            $table->string('area_code', 10)->nullable();
            $table->date('current_season_start')->nullable();
            $table->date('current_season_end')->nullable();
            $table->unsignedSmallInteger('current_matchday')->nullable();
            $table->timestamp('synced_at')->nullable();       // última vez que vino en el catálogo
            $table->timestamp('teams_synced_at')->nullable(); // última importación de equipos y jugadores
            $table->timestamps();
        });

        Schema::create('teams', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('name');
            $table->string('short_name')->nullable();
            $table->string('tla', 10)->nullable();
            $table->string('crest')->nullable();
            $table->string('area_name')->nullable();
            $table->string('address')->nullable();
            $table->string('website')->nullable();
            $table->unsignedSmallInteger('founded')->nullable();
            $table->string('club_colors')->nullable();
            $table->string('venue')->nullable();
            $table->string('coach_name')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index('name');
        });

        // Un equipo juega varias competiciones (p. ej. Premier League y Champions).
        Schema::create('competition_team', function (Blueprint $table) {
            $table->unsignedBigInteger('competition_id');
            $table->unsignedBigInteger('team_id');
            $table->string('season', 9)->nullable();

            $table->primary(['competition_id', 'team_id']);
            $table->foreign('competition_id')->references('id')->on('competitions')->cascadeOnDelete();
            $table->foreign('team_id')->references('id')->on('teams')->cascadeOnDelete();
        });

        Schema::create('players', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->string('name');
            $table->string('position')->nullable();
            // football-data no siempre lo entrega en el plantel (ver README).
            $table->unsignedSmallInteger('shirt_number')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('nationality')->nullable();
            $table->timestamps();

            $table->foreign('team_id')->references('id')->on('teams')->nullOnDelete();
            $table->index('name');
            $table->index('position');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('players');
        Schema::dropIfExists('competition_team');
        Schema::dropIfExists('teams');
        Schema::dropIfExists('competitions');
    }
};
