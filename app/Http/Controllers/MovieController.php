<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Movie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Models\Actor;
use Illuminate\Support\Facades\DB;
use App\Models\Director;

class MovieController extends Controller
{
    public function index(Request $request)
    {
        $query = Movie::with(['category', 'moderator']);

        if ($request->has('category')) {
            $query->where('category_id', $request->category);
        }

        if ($request->has('sort')) {
            if ($request->sort == 'rating_desc') {
                $query->withAvg('reviews', 'rating')->orderByRaw('reviews_avg_rating DESC NULLS LAST');
            } elseif ($request->sort == 'rating_asc') {
                $query->withAvg('reviews', 'rating')->orderByRaw('reviews_avg_rating ASC NULLS LAST');
            } elseif ($request->sort == 'newest') {
                $query->latest();
            }
        } else {
            $query->latest();
        }

        $movies = $query->paginate(10);
        $categories = Category::all();

        return view('index', compact('movies', 'categories'));
    }

    public function byCategory($id)
    {
        $category = Category::findOrFail($id);
        $movies = $category->movies()->with('moderator')->latest()->paginate(10);
        $categories = Category::all();
        return view('category', compact('category', 'movies', 'categories'));
    }

    public function show($id)
    {
        $movie = Movie::with([
            'reviews.user',
            'category',
            'moderator',
            'castMembers',
            'directorMembers',
        ])->findOrFail($id);

        return view('movie', compact('movie'));
    }

    public function create()
    {
        $categories = Category::all();

        $actorOptions = Actor::orderBy('name')->get();
        $directorOptions = Director::orderBy('name')->get();

        return view(
            'moderator',
            compact('categories', 'actorOptions', 'directorOptions')
        );
    }

    public function store(Request $request)
    {
        $this->validateMovie($request);
        $people = $this->savePeopleFiles($request);
        $movie = DB::transaction(function () use ($request, $people) {
            $movie = Movie::create([
                'title' => $request->title,
                'year' => $request->year,
                'description' => $request->description,
                'poster_url' => $request->poster_url,
                'trailer_url' => $request->trailer_url,
                'category_id' => $request->category_id,
                'moderator_id' => auth()->id(),
                
                'people_file_key' => $people['people_file_key'],
            ]);

        $this->syncMovieActors(
            $movie,
            $people['actors']
        );

        $this->syncMovieDirectors(
            $movie,
            $people['directors']
        );

        return $movie;
    });

        if ($request->filled('poster_url')) {
            $fileName = 'movie_' . $movie->id . '.txt';
            $dir = public_path('posters');
            
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }
            
            file_put_contents($dir . '/' . $fileName, $request->poster_url);
        }

        return redirect('/')->with('ok', 'Фильм добавлен!');
    }

    public function myMovies()
    {
        $movies = Movie::where('moderator_id', auth()->id())->latest()->get();
        return view('my-movies', compact('movies'));
    }

    public function edit($id)
{
    $movie = Movie::with([
        'reviews.user',
        'castMembers',
        'directorMembers',
    ])->findOrFail($id);

    if (
        (int) $movie->moderator_id !== (int) auth()->id()
        && auth()->user()->role->name !== 'Администратор'
    ) {
        abort(
            403,
            'Вы можете редактировать только свои фильмы.'
        );
    }

    $categories = Category::all();

    $actorOptions = Actor::orderBy('name')->get();
    $directorOptions = Director::orderBy('name')->get();

    return view(
        'movie-edit',
        compact(
            'movie',
            'categories',
            'actorOptions',
            'directorOptions'
        )
    );
}

    public function update(Request $request, $id)
    {
        $movie = Movie::findOrFail($id);

        if ($movie->moderator_id !== auth()->id() && auth()->user()->role->name !== 'Администратор') {
            abort(403);
        }

        $this->validateMovie($request);
        $people = $this->savePeopleFiles($request);
        if ($request->filled('poster_url')) {
            $fileName = 'movie_' . $movie->id . '.txt';
            $dir = public_path('posters');
            
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }
            
            file_put_contents($dir . '/' . $fileName, $request->poster_url);
        }

        DB::transaction(function () use ($movie, $request, $people) {
            $movie->update([
                'title' => $request->title,
                'year' => $request->year,
                'description' => $request->description,
                'poster_url' => $request->poster_url,
                'trailer_url' => $request->trailer_url,
                'category_id' => $request->category_id,
                'actors' => null,
                'directors' => null,
                'people_file_key' => $people['people_file_key'],
            ]);

            $this->syncMovieActors(
                $movie,
                $people['actors']
            );
            $this->syncMovieDirectors(
                $movie,
                $people['directors']
            );
        });

        return redirect('/my-movies')->with('ok', 'Фильм обновлен!');
    }
    private function validateMovie(Request $request): void
{
    $request->validate([
        'title' => 'required|string|max:255',
        'year' => 'required|integer|min:1888|max:2100',
        'description' => 'required|string',

        'actor_ids' => 'nullable|array|max:100',
        'actor_ids.*' => 'required|integer|distinct|exists:actors,id',

        'director_ids' => 'nullable|array|max:20',
        'director_ids.*' => 'required|integer|distinct|exists:directors,id',

        'actors_input' => 'prohibited',
        'directors_input' => 'prohibited',

        'category_id' => 'required|exists:categories,id',
        'poster_url' => 'nullable|url:http,https|max:255',

        'trailer_url' => [
            'bail',
            'nullable',
            'string',
            'url:http,https',
            'max:2048',
            function (
                string $attribute,
                mixed $value,
                \Closure $fail
            ): void {
                if (Movie::resolveTrailer($value) === null) {
                    $fail(
                        'Укажите ссылку на YouTube, Rutube, VK Видео '
                        . 'или прямую ссылку на MP4, WebM, OGV.'
                    );
                }
            },
        ],
    ], [
        'poster_url.url' =>
            'Ссылка на постер должна начинаться с http:// или https://.',

        'poster_url.max' =>
            'Ссылка на постер должна быть не длиннее 255 символов.',

        'trailer_url.url' =>
            'Ссылка на трейлер должна начинаться с http:// или https://.',

        'trailer_url.max' =>
            'Ссылка на трейлер должна быть не длиннее 2048 символов.',
    ]);
}
        private function savePeopleFiles(Request $request): array
        {
            $lists = [
                'actors' => array_values(array_unique(array_map(
                    'intval',
                    $request->input('actor_ids') ?? []
                ))),

                'directors' => array_values(array_unique(array_map(
                    'intval',
                    $request->input('director_ids') ?? []
                ))),
            ];

            $key = (string) Str::uuid();

            $paths = [
                'actors' => base_path('actors/movie_' . $key . '.json'),
                'directors' => base_path('directors/movie_' . $key . '.json'),
            ];

            try {
                // Сначала проверяем обе папки.
                foreach (['actors', 'directors'] as $folder) {
                    $directory = base_path($folder);

                    if (!is_dir($directory) || !is_writable($directory)) {
                        throw new \RuntimeException(
                            'Папка недоступна для записи: ' . $folder
                        );
                    }
                }

                // Сначала сохраняем оба списка на диск.
                foreach ($lists as $type => $names) {
                    $json = json_encode(
                        $names,
                        JSON_UNESCAPED_UNICODE
                            | JSON_UNESCAPED_SLASHES
                            | JSON_PRETTY_PRINT
                            | JSON_THROW_ON_ERROR
                    );

                    $written = File::put($paths[$type], $json, true);

                    if ($written === false) {
                        throw new \RuntimeException(
                            'Не удалось записать файл: ' . $type
                        );
                    }
                }

                // Для базы берём данные именно из записанных файлов.
                $actorsFromFile = json_decode(
                    File::get($paths['actors']),
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                );

                $directorsFromFile = json_decode(
                    File::get($paths['directors']),
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                );

                return [
                    'actors' => $actorsFromFile,
                    'directors' => $directorsFromFile,
                    'people_file_key' => $key,
                ];
            } catch (\Throwable $exception) {
                File::delete(array_values($paths));

                report($exception);

                throw ValidationException::withMessages([
                    'people' => 'Не удалось сохранить актёров и режиссёров. '
                        . 'Проверьте, что папки actors и directors созданы '
                        . 'в корне проекта и доступны приложению для записи.',
                ]);
            }
        }

        private function parsePeopleNames(
            ?string $value,
            string $field,
            int $limit
        ): array {
            $lines = preg_split(
                '/\r\n|\r|\n/',
                trim($value ?? '')
            ) ?: [];

            $names = array_map(
                static fn (string $name): string => trim($name),
                $lines
            );

            $names = array_filter(
                $names,
                static fn (string $name): bool => $name !== ''
            );

            $names = array_values(array_unique($names, SORT_STRING));

            if (count($names) > $limit) {
                throw ValidationException::withMessages([
                    $field => 'Можно указать не более '
                        . $limit . ' имён, по одному на строку.',
                ]);
            }

            foreach ($names as $name) {
                if (mb_strlen($name) > 120) {
                    throw ValidationException::withMessages([
                        $field => 'Каждое имя должно быть '
                            . 'не длиннее 120 символов.',
                    ]);
                }
            }

            return $names;
        }
        /**
     * @return array<int, array{name: string, photo_url: string|null}>
     */
    private function parseActors(array $rows): array
    {
        $actors = [];

        foreach ($rows as $index => $row) {
            $name = trim($row['name'] ?? '');
            $photoUrl = trim($row['photo_url'] ?? '');

            // Полностью пустую строку пропускаем.
            if ($name === '' && $photoUrl === '') {
                continue;
            }

            if ($name === '') {
                throw ValidationException::withMessages([
                    "actors_input.$index.name" =>
                        'Укажите имя актёра, для которого добавлена фотография.',
                ]);
            }

            $actors[] = [
                'name' => $name,
                'photo_url' => $photoUrl !== '' ? $photoUrl : null,
            ];
        }

        return $actors;
    }
    /**
     * @return array<int, int>
     */
    private function selectedActorIds(Request $request): array
    {
        $ids = array_map(
            'intval',
            $request->input('actor_ids') ?? []
        );

        // Новых актёров создаём только через редактирование фильма.
        if ($request->routeIs('movie.update')) {
            $newActors = $this->parseActors(
                $request->input('actors_input') ?? []
            );

            foreach ($newActors as $data) {
                $actor = Actor::register(
                    $data['name'],
                    $data['photo_url']
                );

                $ids[] = $actor->id;
            }
        }

        return array_values(array_unique($ids));
    }

    private function syncMovieActors(
        Movie $movie,
        array $actorIds
    ): void {
        $links = [];

        foreach ($actorIds as $position => $actorId) {
            $links[$actorId] = [
                'position' => $position,
            ];
        }

        $movie->castMembers()->sync($links);
    }
    private function syncMovieDirectors(
        Movie $movie,
        array $directorIds
    ): void {
        $links = [];

        foreach ($directorIds as $position => $directorId) {
            $links[$directorId] = [
                'position' => $position,
            ];
        }

        $movie->directorMembers()->sync($links);
    }
}