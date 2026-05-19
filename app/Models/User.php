<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'steam_id',
        'name',
        'steam_nickname',
        'steam_real_name',
        'email',
        'password',
        'avatar',
        'role',
        'banned_at',
        'points',
        'credits',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'banned_at' => 'datetime',
        ];
    }

    public function hasConfiguredAdminSteamId(): bool
    {
        $steamId = trim((string) $this->steam_id);

        if ($steamId === '') {
            return false;
        }

        $adminIds = config('services.steam.admin_ids', []);

        return in_array($steamId, $adminIds, true);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin' || $this->hasConfiguredAdminSteamId();
    }

    public function canAccessStore(): bool
    {
        return $this->isAdmin();
    }

    public function isBetaTester(): bool
    {
        return $this->role === 'betatester';
    }

    public function isBanned(): bool
    {
        return $this->banned_at !== null;
    }

    public function lobbies(): BelongsToMany
    {
        return $this->belongsToMany(Lobby::class)->withPivot('team', 'is_ready')->withTimestamps();
    }
}
