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
        Schema::table('servers', function (Blueprint $table) {
            $table->string('pterodactyl_identifier')->nullable()->after('current_players');
            $table->string('pterodactyl_uuid')->nullable()->after('pterodactyl_identifier');
            $table->string('pterodactyl_status', 32)->nullable()->after('pterodactyl_uuid');
            $table->timestamp('pterodactyl_last_synced_at')->nullable()->after('pterodactyl_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn([
                'pterodactyl_identifier',
                'pterodactyl_uuid',
                'pterodactyl_status',
                'pterodactyl_last_synced_at',
            ]);
        });
    }
};
