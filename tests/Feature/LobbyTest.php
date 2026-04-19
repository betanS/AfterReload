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
}
