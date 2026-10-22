<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = ['login', 'password', 'role_id', 'score', 'is_banned'];
    protected $hidden = ['password', 'remember_token'];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function movies(): HasMany
    {
        return $this->hasMany(Movie::class, 'moderator_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}