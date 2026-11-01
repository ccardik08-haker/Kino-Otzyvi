<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Movie extends Model
{
    protected $fillable = [
        'title',
        'year',
        'description',
        'poster_url',
        'trailer_url',
        'category_id',
        'moderator_id',
        'actors',
        'directors',
        'people_file_key',
    ];
    protected function casts(): array
    {
        return [
            'actors' => 'array',
            'directors' => 'array',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function getAverageRatingAttribute(): float
    {
        return round($this->reviews()->avg('rating') ?? 0, 1);
    }

    /**
     * @return array{type: string, url: string}|null
     */
    public function getTrailerMediaAttribute(): ?array
    {
        return self::resolveTrailer($this->trailer_url);
    }

    /**
     * @return array{type: string, url: string}|null
     */
    public static function resolveTrailer(?string $url): ?array
    {
        $url = trim($url ?? '');

        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        $parts = parse_url($url);

        if (
            !in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
            || isset($parts['user'])
            || isset($parts['pass'])
        ) {
            return null;
        }

        $host = strtolower($parts['host'] ?? '');
        $path = $parts['path'] ?? '';

        parse_str($parts['query'] ?? '', $query);

        // Прямая ссылка на видеофайл.
        if (preg_match('~\.(mp4|webm|ogv)$~i', $path)) {
            return [
                'type' => 'video',
                'url' => $url,
            ];
        }

        // YouTube.
        $youtubeId = null;

        if (in_array($host, ['youtu.be', 'www.youtu.be'], true)) {
            $youtubeId = trim($path, '/');
        } elseif (
            in_array($host, [
                'youtube.com',
                'www.youtube.com',
                'm.youtube.com',
                'youtube-nocookie.com',
                'www.youtube-nocookie.com',
            ], true)
        ) {
            if ($path === '/watch') {
                $youtubeId = $query['v'] ?? null;
            } elseif (
                preg_match(
                    '~^/(?:embed|shorts|live)/([a-zA-Z0-9_-]{11})/?$~',
                    $path,
                    $matches
                )
            ) {
                $youtubeId = $matches[1];
            }
        }

        if (
            is_string($youtubeId)
            && preg_match('~^[a-zA-Z0-9_-]{11}$~', $youtubeId)
        ) {
            return [
                'type' => 'embed',
                'url' => 'https://www.youtube-nocookie.com/embed/' . $youtubeId,
            ];
        }

        // Rutube.
        if (
            in_array($host, ['rutube.ru', 'www.rutube.ru'], true)
            && preg_match(
                '~^/(?:video|play/embed)/([a-f0-9]{32})/?$~i',
                $path,
                $matches
            )
        ) {
            return [
                'type' => 'embed',
                'url' => 'https://rutube.ru/play/embed/' . $matches[1],
            ];
        }

        // VK Видео.
        if (
            in_array($host, [
                'vk.com',
                'www.vk.com',
                'vkvideo.ru',
                'www.vkvideo.ru',
            ], true)
        ) {
            $ownerId = $query['oid'] ?? null;
            $videoId = $query['id'] ?? null;

            if (preg_match('~^/video(-?\d+)_(\d+)/?$~', $path, $matches)) {
                $ownerId = $matches[1];
                $videoId = $matches[2];
            } elseif ($path !== '/video_ext.php') {
                return null;
            }

            if (
                is_string($ownerId)
                && preg_match('~^-?\d+$~', $ownerId)
                && is_string($videoId)
                && preg_match('~^\d+$~', $videoId)
            ) {
                $parameters = [
                    'oid' => $ownerId,
                    'id' => $videoId,
                ];

                if (
                    isset($query['hash'])
                    && is_string($query['hash'])
                    && preg_match('~^[a-zA-Z0-9_-]+$~', $query['hash'])
                ) {
                    $parameters['hash'] = $query['hash'];
                }

                $embedHost = str_contains($host, 'vkvideo.ru')
                    ? 'vkvideo.ru'
                    : 'vk.com';

                return [
                    'type' => 'embed',
                    'url' => 'https://' . $embedHost
                        . '/video_ext.php?'
                        . http_build_query($parameters),
                ];
            }
        }

        return null;
    }
    public function castMembers(): BelongsToMany
    {
        return $this->belongsToMany(
            Actor::class,
            'actor_movie'
        )
            ->withPivot('position')
            ->orderByPivot('position');
    }
    public function directorMembers(): BelongsToMany
    {
        return $this->belongsToMany(
            Director::class,
            'director_movie'
        )
            ->withPivot('position')
            ->orderByPivot('position');
    }
}