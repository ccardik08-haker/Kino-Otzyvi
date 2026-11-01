<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Director extends Model
{
    protected $fillable = [
        'name',
        'name_key',
        'photo_url',
        'file_key',
    ];
    public function movies(): BelongsToMany
    {
        return $this->belongsToMany(
            Movie::class,
            'director_movie',
            'director_id',
            'movie_id'
        );
    }
}