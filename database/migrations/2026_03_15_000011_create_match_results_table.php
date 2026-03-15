<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('match_results', function (Blueprint $table) {
            $table->id();
            $table->string('event_hash', 64)->unique();
            $table->string('event_name')->nullable();
            $table->string('match_id')->nullable();
            $table->unsignedInteger('map_number')->nullable();
            $table->unsignedBigInteger('server_id')->nullable();
            $table->string('winner_team')->nullable();
            $table->unsignedInteger('team1_score')->nullable();
            $table->unsignedInteger('team2_score')->nullable();
            $table->json('payload');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('match_results');
    }
};
