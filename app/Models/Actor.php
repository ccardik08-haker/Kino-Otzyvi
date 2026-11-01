<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Actor extends Model
{
    protected $fillable = [
        'name',
        'name_key',
        'photo_url',
        'file_key',
    ];

    public static function register(
        string $name,
        ?string $photoUrl = null
    ): self {
        $name = preg_replace('/\s+/u', ' ', trim($name)) ?? trim($name);
        $photoUrl = trim($photoUrl ?? '');
        $photoUrl = $photoUrl !== '' ? $photoUrl : null;

        Validator::make(
            [
                'name' => $name,
                'photo_url' => $photoUrl,
            ],
            [
                'name' => 'required|string|max:120',
                'photo_url' => 'nullable|url:http,https|max:2048',
            ],
            [
                'name.required' => 'Укажите имя актёра.',
                'name.max' => 'Имя актёра должно быть не длиннее 120 символов.',
                'photo_url.url' => 'Укажите корректный URL фотографии актёра.',
            ]
        )->validate();

        $nameKey = mb_strtolower($name, 'UTF-8');

        $existing = self::where('name_key', $nameKey)->first();

        // Уже существующего актёра повторно не создаём.
        if (
            $existing
            && ($existing->photo_url || $photoUrl === null)
        ) {
            return $existing;
        }

        $directory = base_path('actors');

        if (!is_dir($directory) || !is_writable($directory)) {
            throw ValidationException::withMessages([
                'actors_input' =>
                    'Папка actors должна существовать в корне проекта '
                    . 'и быть доступна приложению для записи.',
            ]);
        }

        $fileKey = (string) Str::uuid();

        $path = $directory . '/actor_' . $fileKey . '.json';

        $data = [
            'name' => $existing ? $existing->name : $name,
            'photo_url' => $photoUrl,
        ];

        try {
            // Сначала сохраняем имя и URL в папку actors.
            $written = File::put(
                $path,
                json_encode(
                    $data,
                    JSON_UNESCAPED_UNICODE
                        | JSON_UNESCAPED_SLASHES
                        | JSON_PRETTY_PRINT
                        | JSON_THROW_ON_ERROR
                ),
                true
            );

            if ($written === false) {
                throw new \RuntimeException(
                    'Не удалось сохранить файл актёра.'
                );
            }

            // Для базы читаем данные из созданного файла.
            $fromFile = json_decode(
                File::get($path),
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            // Если актёр уже есть без фотографии, дополняем его.
            if ($existing) {
                $existing->update([
                    'photo_url' => $fromFile['photo_url'],
                    'file_key' => $fileKey,
                ]);

                return $existing;
            }

            $actor = self::firstOrCreate(
                ['name_key' => $nameKey],
                [
                    'name' => $fromFile['name'],
                    'photo_url' => $fromFile['photo_url'],
                    'file_key' => $fileKey,
                ]
            );

            // Защита от одновременного добавления одного актёра.
            if ($actor->file_key !== $fileKey) {
                File::delete($path);
            }

            return $actor;
        } catch (\Throwable $exception) {
            File::delete($path);

            throw $exception;
        }
    }
    public function movies(): BelongsToMany
    {
        return $this->belongsToMany(
            Movie::class,
            'actor_movie',
            'actor_id',
            'movie_id'
        );
    }
}