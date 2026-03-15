<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LobbyMatch extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'lobby_id',
        'server_id',
        'match_id',
        'config',
        'status',
        'created_at',
    ];

    protected $casts = [
        'config' => 'array',
        'created_at' => 'datetime',
    ];
}
