<?php

namespace App\Console\Commands;

use App\Models\Actor;
use App\Models\Movie;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportMovieActors extends Command
{
    protected $signature = 'actors:import-existing';

    protected $description =
        'Перенос ранее добавленных актёров в общий справочник';

    public function handle(): int
    {
        $processed = 0;

        foreach (Movie::query()->lazyById(100) as $movie) {
            $oldActors = $movie->actors ?? [];

            if (!is_array($oldActors) || $oldActors === []) {
                continue;
            }

            $links = [];

            foreach ($oldActors as $item) {
                $name = is_array($item)
                    ? ($item['name'] ?? '')
                    : $item;

                $photoUrl = is_array($item)
                    ? ($item['photo_url'] ?? null)
                    : null;

                if (!is_string($name) || trim($name) === '') {
                    continue;
                }

                $actor = Actor::register(
                    $name,
                    is_string($photoUrl) ? $photoUrl : null
                );

                if (!isset($links[$actor->id])) {
                    $links[$actor->id] = [
                        'position' => count($links),
                    ];
                }
            }

            DB::transaction(function () use ($movie, $links) {
                $movie->castMembers()->syncWithoutDetaching($links);

                // После переноса используем связи с общим справочником.
                $movie->update([
                    'actors' => null,
                ]);
            });

            $processed++;
        }

        $this->info(
            'Перенос завершён. Обработано фильмов: ' . $processed
        );

        return self::SUCCESS;
    }
}