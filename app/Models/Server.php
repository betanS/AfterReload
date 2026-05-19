<?php

namespace App\Models;

use App\Services\LocalCsgoserverRuntimeService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Server extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'ip',
        'port',
        'rcon_password',
        'type',
        'status',
        'max_players',
        'current_players',
    ];

    public function lobbies(): HasMany
    {
        return $this->hasMany(Lobby::class);
    }

    public function isOnline(int $timeoutMs = 500): bool
    {
        if ($this->status === 'offline') {
            return false;
        }

        $localStatus = app(LocalCsgoserverRuntimeService::class)->isRunning($this);
        if ($localStatus !== null) {
            return $localStatus;
        }

        $timeoutSeconds = max(0.1, $timeoutMs / 1000);
        $socket = @fsockopen($this->ip, $this->port, $errno, $errstr, $timeoutSeconds);

        if ($socket) {
            fclose($socket);

            return true;
        }

        return false;
    }

    public function runtimeStatus(): string
    {
        if ($this->status === 'offline') {
            return 'offline';
        }

        return $this->isOnline() ? 'online' : 'offline';
    }
}
