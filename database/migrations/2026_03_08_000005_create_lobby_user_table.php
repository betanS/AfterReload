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
        Schema::create('lobby_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lobby_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('team', 8)->nullable();
            $table->boolean('is_ready')->default(false);
            $table->timestamps();

            $table->unique(['lobby_id', 'user_id']);
            $table->index(['lobby_id', 'team']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lobby_user');
    }
};
