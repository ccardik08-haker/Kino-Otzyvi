<?php

namespace App\Console\Commands;

use App\Models\Movie;
use App\Services\PersonCatalog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportMovieDirectors extends Command
{
    protected $signature = 'directors:import-existing';

    protected $description =
        'Перенос режиссёров фильмов в общий справочник';

    public function handle(PersonCatalog $catalog): int
    {
        $processed = 0;

        foreach (Movie::query()->lazyById(100) as $movie) {
            $oldDirectors = $movie->directors ?? [];

            if (!is_array($oldDirectors) || $oldDirectors === []) {
                continue;
            }

            $links = [];

            foreach ($oldDirectors as $item) {
                $name = is_array($item)
                    ? ($item['name'] ?? '')
                    : $item;

                $photoUrl = is_array($item)
                    ? ($item['photo_url'] ?? null)
                    : null;

                if (!is_string($name) || trim($name) === '') {
                    continue;
                }

                $director = $catalog->findOrCreate(
                    'directors',
                    $name,
                    is_string($photoUrl) ? $photoUrl : null
                );

                if (!isset($links[$director->id])) {
                    $links[$director->id] = [
                        'position' => count($links),
                    ];
                }
            }

            DB::transaction(function () use ($movie, $links) {
                $movie->directorMembers()->syncWithoutDetaching($links);

                $movie->update([
                    'directors' => null,
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