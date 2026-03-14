<?php

namespace App\Models;

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
        'max_players',
        'current_players',
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
        $timeoutSeconds = max(0.1, $timeoutMs / 1000);
        $socket = @fsockopen($this->ip, $this->port, $errno, $errstr, $timeoutSeconds);

        if ($socket) {
            fclose($socket);
            return true;
        }

        return false;
    }
}