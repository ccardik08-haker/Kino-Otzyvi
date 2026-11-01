<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\User;
use App\Services\PersonCatalog;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class FilmographyController extends Controller
{
    public function show(
        Request $request,
        string $type,
        int $id
    ): View {
        $data = $request->validate([
            'sort' => 'nullable|in:newest,oldest,rating',
        ]);

        $sort = $data['sort'] ?? 'newest';

        $query = Movie::with('category')
            ->withAvg('reviews', 'rating')
            ->withExists('reviews');

        if ($type === 'authors') {
            $author = User::findOrFail($id);

            $person = (object) [
                'id' => $author->id,
                'name' => $author->login,
                'photo_url' => null,
            ];

            $roleLabel = 'Автор публикаций';
            $sectionTitle = 'Опубликованные фильмы';
            $emptyMessage = 'Этот автор пока не выложил ни одного фильма.';

            $query->where('moderator_id', $author->id);
        } else {
            $modelClass = PersonCatalog::modelClass($type);
            $person = $modelClass::findOrFail($id);

            $relation = $type === 'actors'
                ? 'castMembers'
                : 'directorMembers';

            $roleLabel = $type === 'actors'
                ? 'Актёр'
                : 'Режиссёр';

            $sectionTitle = $type === 'actors'
                ? 'Фильмы с участием'
                : 'Режиссёрские работы';

            $emptyMessage =
                'Фильмы с этим участником пока не добавлены на сайт.';

            $query->whereHas(
                $relation,
                static fn (Builder $personQuery): Builder =>
                    $personQuery->whereKey($person->id)
            );
        }

        $dateColumn = $type === 'authors'
            ? 'movies.created_at'
            : 'movies.year';

        if ($sort === 'rating') {
            $query
                ->orderByDesc('reviews_exists')
                ->orderByDesc('reviews_avg_rating')
                ->orderByDesc($dateColumn);
        } elseif ($sort === 'oldest') {
            $query->orderBy($dateColumn);
        } else {
            $query->orderByDesc($dateColumn);
        }

        $movies = $query
            ->orderByDesc('movies.id')
            ->paginate(12)
            ->appends(['sort' => $sort]);

        return view(
            'filmography',
            compact(
                'person',
                'type',
                'roleLabel',
                'sectionTitle',
                'emptyMessage',
                'movies',
                'sort'
            )
        );
    }
}