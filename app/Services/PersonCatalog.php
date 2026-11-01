<?php

namespace App\Services;

use App\Models\Actor;
use App\Models\Director;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PersonCatalog
{
    public static function modelClass(string $type): string
    {
        return match ($type) {
            'actors' => Actor::class,
            'directors' => Director::class,
            default => abort(404),
        };
    }

    public static function cleanName(string $name): string
    {
        return preg_replace('/\s+/u', ' ', trim($name))
            ?? trim($name);
    }

    public function findOrCreate(
        string $type,
        string $name,
        ?string $photoUrl = null
    ): Model {
        $modelClass = self::modelClass($type);
        $name = self::cleanName($name);

        $existing = $modelClass::where(
            'name_key',
            mb_strtolower($name, 'UTF-8')
        )->first();

        if ($existing) {
            return $existing;
        }

        return $this->save($type, null, $name, $photoUrl);
    }

    public function save(
        string $type,
        ?Model $person,
        string $name,
        ?string $photoUrl = null
    ): Model {
        $modelClass = self::modelClass($type);

        if ($person && !($person instanceof $modelClass)) {
            abort(404);
        }

        $name = self::cleanName($name);

        $photoUrl = trim($photoUrl ?? '');
        $photoUrl = $photoUrl !== '' ? $photoUrl : null;

        $data = [
            'name' => $name,
            'name_key' => mb_strtolower($name, 'UTF-8'),
            'photo_url' => $photoUrl,
        ];

        Validator::make(
            $data,
            [
                'name' => 'required|string|max:120',
                'name_key' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique(
                        (new $modelClass)->getTable(),
                        'name_key'
                    )->ignore($person?->getKey()),
                ],
                'photo_url' => 'nullable|url:http,https|max:2048',
            ],
            [
                'name.required' => 'Введите имя.',
                'name.max' => 'Имя должно быть не длиннее 120 символов.',
                'name_key.unique' => 'Запись с таким именем уже существует.',
                'photo_url.url' => 'Введите корректный URL фотографии.',
                'photo_url.max' => 'URL должен быть не длиннее 2048 символов.',
            ]
        )->validate();

        $directory = base_path($type);

        if (!is_dir($directory) || !is_writable($directory)) {
            throw ValidationException::withMessages([
                'catalog' => 'Папка ' . $type
                    . ' должна существовать в корне проекта '
                    . 'и быть доступна приложению для записи.',
            ]);
        }

        $fileKey = (string) Str::uuid();
        $prefix = $type === 'actors' ? 'actor_' : 'director_';
        $path = $directory . '/' . $prefix . $fileKey . '.json';

        try {
            $written = File::put(
                $path,
                json_encode(
                    [
                        'name' => $name,
                        'photo_url' => $photoUrl,
                    ],
                    JSON_UNESCAPED_UNICODE
                        | JSON_UNESCAPED_SLASHES
                        | JSON_PRETTY_PRINT
                        | JSON_THROW_ON_ERROR
                ),
                true
            );

            if ($written === false) {
                throw new \RuntimeException(
                    'Не удалось сохранить файл.'
                );
            }

            $fromFile = json_decode(
                File::get($path),
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            $attributes = [
                'name' => $fromFile['name'],
                'name_key' => mb_strtolower(
                    $fromFile['name'],
                    'UTF-8'
                ),
                'photo_url' => $fromFile['photo_url'],
                'file_key' => $fileKey,
            ];

            if ($person) {
                $person->fill($attributes);
                $person->save();

                return $person;
            }

            return $modelClass::create($attributes);
        } catch (UniqueConstraintViolationException $exception) {
            File::delete($path);

            throw ValidationException::withMessages([
                'name' => 'Запись с таким именем уже существует.',
            ]);
        } catch (\Throwable $exception) {
            File::delete($path);

            throw $exception;
        }
    }
}