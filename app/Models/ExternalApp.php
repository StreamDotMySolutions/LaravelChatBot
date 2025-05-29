<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ExternalApp extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'client_id',
        'api_token',
        'token_expires_at',
        'is_active',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];


    public static function generateToken()
    {
        return hash('sha256', Str::random(60));
    }
}
