<?php

namespace App\Models;

use App\Services\PterodactylService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Server extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'ip',
        'port',
        'rcon_password',
        'type',
        'max_players',
        'current_players',
        'pterodactyl_identifier',
        'pterodactyl_uuid',
        'pterodactyl_status',
        'pterodactyl_last_synced_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'pterodactyl_last_synced_at' => 'datetime',
    ];

    /**
     * @return HasMany<Lobby>
     */
    public function lobbies(): HasMany
    {
        return $this->hasMany(Lobby::class);
    }

    public function isOnline(int $timeoutMs = 500): bool
    {
        if ($this->hasPterodactylIntegration()) {
            $state = app(PterodactylService::class)->resolveCurrentState($this);

            if ($state !== null) {
                return $state === 'running';
            }
        }

        $timeoutSeconds = max(0.1, $timeoutMs / 1000);
        $socket = @fsockopen($this->ip, $this->port, $errno, $errstr, $timeoutSeconds);

        if ($socket) {
            fclose($socket);
            return true;
        }

        return false;
    }

    public function hasPterodactylIntegration(): bool
    {
        return filled($this->pterodactyl_identifier);
    }

    public function runtimeStatus(): string
    {
        if ($this->hasPterodactylIntegration() && filled($this->pterodactyl_status)) {
            return (string) $this->pterodactyl_status;
        }

        return $this->isOnline() ? 'online' : 'offline';
    }
}
