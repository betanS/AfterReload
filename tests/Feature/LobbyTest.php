<?php

namespace Tests\Feature;

use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LobbyTest extends TestCase
{
    use RefreshDatabase;

    public function test_lobby_view_has_all_required_variables()
    {
        $user = User::factory()->create(['steam_id' => '76561198000000000', 'role' => 'admin']);
        $server = Server::create([
            'name' => 'Test Server',
            'ip' => '127.0.0.1',
            'port' => 27015,
            'type' => 'public',
            'max_players' => 10,
        ]);

        $this->actingAs($user);
        
        $response = $this->get(route('lobby.show', $server));

        $response->assertStatus(200);
        $response->assertViewHas('isUnlimitedLobby');
        $response->assertViewHas('displayLobbyId');
        $response->assertViewHas('ctCount');
        $response->assertViewHas('tCount');
        $response->assertViewHas('teamSize');
        $response->assertViewHas('isReady');
        $response->assertViewHas('missingPlayers');
    }

    public function test_opening_lobby_does_not_auto_join_user()
    {
        $user = User::factory()->create(['steam_id' => '76561198000000001', 'role' => 'admin']);
        $server = Server::create([
            'name' => 'Matchmaking Server',
            'ip' => '127.0.0.1',
            'port' => 27016,
            'type' => 'mm',
            'max_players' => 10,
        ]);

        $this->actingAs($user);

        $this->get(route('lobby.show', $server))->assertStatus(200);

        $lobby = $server->lobbies()->firstOrFail();

        $this->assertFalse($lobby->users()->where('users.id', $user->id)->exists());
    }

    public function test_joining_team_confirms_lobby_attendance()
    {
        $user = User::factory()->create(['steam_id' => '76561198000000002', 'role' => 'admin']);
        $server = Server::create([
            'name' => 'Matchmaking Server',
            'ip' => '127.0.0.1',
            'port' => 27017,
            'type' => 'mm',
            'max_players' => 10,
        ]);

        $this->actingAs($user);

        $response = $this->postJson(route('lobby.team', $server), ['team' => 'ct']);

        $response->assertOk()
            ->assertJsonPath('lobby.users_count', 1)
            ->assertJsonPath('users.0.id', $user->id)
            ->assertJsonPath('users.0.team', 'ct')
            ->assertJsonPath('users.0.is_ready', false);

        $lobby = $server->lobbies()->firstOrFail();

        $this->assertTrue($lobby->users()->where('users.id', $user->id)->wherePivot('team', 'ct')->exists());
    }
}
