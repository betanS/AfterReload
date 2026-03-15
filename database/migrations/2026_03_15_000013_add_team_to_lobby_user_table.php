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
        Schema::table('lobby_user', function (Blueprint $table) {
            $table->string('team', 8)->nullable()->after('user_id');
            $table->index(['lobby_id', 'team']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lobby_user', function (Blueprint $table) {
            $table->dropIndex(['lobby_id', 'team']);
            $table->dropColumn('team');
        });
    }
};
