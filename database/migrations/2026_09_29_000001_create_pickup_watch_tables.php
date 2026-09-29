<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fantrax_leagues', function (Blueprint $table) {
            $table->id();
            $table->string('league_id', 64)->unique();
            $table->timestamp('last_refresh_at')->nullable();
            $table->timestamps();
        });

        Schema::create('active_daily_players', function (Blueprint $table) {
            $table->id();
            $table->string('league_id', 64);
            $table->date('game_date');
            $table->string('player_name', 160);
            $table->string('team', 3);
            $table->string('position', 4)->nullable();
            $table->string('opponent', 8)->nullable();
            $table->string('home_away', 8)->nullable();
            $table->string('availability', 8);
            $table->string('waiver_day', 20)->nullable();
            $table->string('injury_status', 255)->nullable();
            $table->decimal('projected_fpts', 10, 2)->nullable();
            $table->unsignedInteger('source_rank')->nullable();
            $table->text('fantrax_url')->nullable();
            $table->timestamp('last_update')->nullable();
            $table->timestamps();
            $table->unique(['league_id','game_date','team','player_name'], 'daily_player_league_date_unique');
            $table->index(['league_id','game_date','position']);
        });

        Schema::create('active_starting_goalies', function (Blueprint $table) {
            $table->id();
            $table->date('game_date');
            $table->string('player_name', 160);
            $table->string('team', 3);
            $table->string('opponent', 3);
            $table->string('home_away', 8);
            $table->string('starting_status', 20)->nullable();
            $table->timestamp('source_updated_at')->nullable();
            $table->text('source_url')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();
            $table->unique(['game_date','team','player_name']);
        });

        Schema::create('active_pp_lines', function (Blueprint $table) {
            $table->id();
            $table->string('team', 3);
            $table->string('player_name', 160);
            $table->unsignedTinyInteger('pp_unit');
            $table->unsignedTinyInteger('unit_position')->nullable();
            $table->text('source_url')->nullable();
            $table->timestamp('last_update')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();
            $table->unique(['team','player_name']);
        });

        Schema::create('active_line_combinations', function (Blueprint $table) {
            $table->id();
            $table->string('team', 3);
            $table->string('player_name', 160);
            $table->string('position_group', 1);
            $table->unsignedTinyInteger('line_number');
            $table->unsignedTinyInteger('unit_position')->nullable();
            $table->text('source_url')->nullable();
            $table->timestamp('last_update')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();
            $table->unique(['team','player_name','position_group'], 'line_player_unique');
            $table->index(['position_group','line_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('active_line_combinations');
        Schema::dropIfExists('active_pp_lines');
        Schema::dropIfExists('active_starting_goalies');
        Schema::dropIfExists('active_daily_players');
        Schema::dropIfExists('fantrax_leagues');
    }
};
