<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'steam_id',
        'steam_nickname',
        'steam_real_name',
        'role',
        'banned_at',
        'avatar',
        'rank_points',
        'blue_credits',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'banned_at' => 'datetime',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function canAccessStore(): bool
    {
        return $this->isAdmin();
    }

    public function isBanned(): bool
    {
        return $this->banned_at !== null;
    }

    /**
     * @return BelongsToMany<Lobby, User>
     */
    public function lobbies(): BelongsToMany
    {
        return $this->belongsToMany(Lobby::class)->withPivot('team')->withTimestamps();
    }
}
