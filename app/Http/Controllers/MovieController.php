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
use Illuminate\Database\Eloquent\Builder;

class MovieController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'q' => 'nullable|string|max:200',
            'search_in' => 'nullable|in:all,title,genre,actor,director',
            'category' => 'nullable|integer|exists:categories,id',
            'sort' => 'nullable|in:newest,rating_desc,rating_asc',
        ]);

        $searchTerm = preg_replace('/\s+/u', ' ', trim($data['q'] ?? '')) ?? '';
        $searchIn = $data['search_in'] ?? 'all';
        $sort = $data['sort'] ?? 'newest';
        $needle = mb_strtolower($searchTerm, 'UTF-8');

        $pattern = '%' . strtr($needle, [
            '!' => '!!',
            '%' => '!%',
            '_' => '!_',
        ]) . '%';

        $lowerFunction = 'LOWER';

        if ($searchTerm !== '') {
            $connection = (new Movie)->getConnection();

            if ($connection->getDriverName() === 'sqlite') {
                $lowerFunction = 'kino_lower';

                $callback = static function (?string $value): string {
                    return mb_strtolower($value ?? '', 'UTF-8');
                };

                foreach ([$connection->getPdo(), $connection->getReadPdo()] as $pdo) {
                    if (method_exists($pdo, 'createFunction')) {
                        $pdo->createFunction('kino_lower', $callback, 1);
                    } else {
                        $pdo->sqliteCreateFunction('kino_lower', $callback, 1);
                    }
                }
            }
        }

        $exactGenreIds = [];

        if ($searchTerm !== '' && in_array($searchIn, ['all', 'genre'], true)) {
            $exactGenreIds = Category::whereRaw($lowerFunction . '(name) = ?', [$needle])->pluck('id')->all();
        }

        $actorResults = null;
        $directorResults = null;

        $searchPeopleEverywhere = $searchIn === 'all' && $searchTerm !== '' && $exactGenreIds === [];

        if ($searchIn === 'actor' || $searchPeopleEverywhere) {
            $actorResults = Actor::withCount('movies')
                ->whereRaw("actors.name_key LIKE ? ESCAPE '!'", [$pattern])
                ->orderBy('name')
                ->orderBy('id')
                ->paginate(12, ['*'], 'actors_page')
                ->withQueryString();
        }

        if ($searchIn === 'director' || $searchPeopleEverywhere) {
            $directorResults = Director::withCount('movies')
                ->whereRaw("directors.name_key LIKE ? ESCAPE '!'", [$pattern])
                ->orderBy('name')
                ->orderBy('id')
                ->paginate(12, ['*'], 'directors_page')
                ->withQueryString();
        }

        $query = Movie::with(['category', 'moderator'])
            ->withAvg('reviews', 'rating')
            ->withExists('reviews');

        $searchOnlyPeople = in_array($searchIn, ['actor', 'director'], true);

        if ($searchOnlyPeople) {
            $query->whereRaw('1 = 0');
        } elseif ($searchTerm !== '') {
            if ($exactGenreIds !== []) {
                $query->whereIn('category_id', $exactGenreIds);
            } else {
                $query->where(function (Builder $search) use ($searchIn, $pattern, $lowerFunction): void {
                    if (in_array($searchIn, ['all', 'title'], true)) {
                        $search->orWhereRaw($lowerFunction . "(movies.title) LIKE ? ESCAPE '!'", [$pattern]);
                    }

                    if (in_array($searchIn, ['all', 'genre'], true)) {
                        $search->orWhereHas('category', function (Builder $categoryQuery) use ($pattern, $lowerFunction): void {
                            $categoryQuery->whereRaw($lowerFunction . "(categories.name) LIKE ? ESCAPE '!'", [$pattern]);
                        });
                    }
                });
            }
        }

        if (!empty($data['category'])) {
            $query->where('category_id', $data['category']);
        }

        if ($sort === 'rating_desc') {
            $query->orderByDesc('reviews_exists')->orderByDesc('reviews_avg_rating');
        } elseif ($sort === 'rating_asc') {
            $query->orderByDesc('reviews_exists')->orderBy('reviews_avg_rating');
        } else {
            $query->orderByDesc('movies.created_at');
        }

        $movies = $query->orderByDesc('movies.id')->paginate(10)->withQueryString();

        $hasPeople = ($actorResults?->total() ?? 0) > 0 || ($directorResults?->total() ?? 0) > 0;

        $showMovieSection = !$searchOnlyPeople && ($searchTerm === '' || $movies->total() > 0 || !$hasPeople);

        $categories = Category::orderBy('name')->get();

        return view('index', compact('movies', 'categories', 'searchTerm', 'searchIn', 'actorResults', 'directorResults', 'showMovieSection'));
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
        $movie = Movie::with(['reviews.user', 'category', 'moderator', 'castMembers', 'directorMembers'])->findOrFail($id);
        return view('movie', compact('movie'));
    }

    public function create()
    {
        $categories = Category::all();
        $actorOptions = Actor::orderBy('name')->get();
        $directorOptions = Director::orderBy('name')->get();
        return view('moderator', compact('categories', 'actorOptions', 'directorOptions'));
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

            $this->syncMovieActors($movie, $people['actors']);
            $this->syncMovieDirectors($movie, $people['directors']);

            return $movie;
        });

        if ($request->filled('poster_url')) {
            $fileName = 'movie_' . $movie->id . '.txt';
            $dir = base_path('media/posters');
            
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
        $movie = Movie::with(['reviews.user', 'castMembers', 'directorMembers'])->findOrFail($id);
        $isAdmin = auth()->user()->role?->name === 'Администратор';
        $isOwner = (int) $movie->moderator_id === (int) auth()->id();

        if (!$isAdmin && !$isOwner) {
            abort(403, 'Вы можете редактировать только свои фильмы.');
        }

        $categories = Category::all();
        $actorOptions = Actor::orderBy('name')->get();
        $directorOptions = Director::orderBy('name')->get();

        return view('movie-edit', compact('movie', 'categories', 'actorOptions', 'directorOptions'));
    }

    public function update(Request $request, $id)
    {
        $movie = Movie::findOrFail($id);
        $isAdmin = auth()->user()->role?->name === 'Администратор';
        $isOwner = (int) $movie->moderator_id === (int) auth()->id();

        if (!$isAdmin && !$isOwner) {
            abort(403, 'Вы можете редактировать только свои фильмы.');
        }

        if ($movie->moderator_id !== auth()->id() && auth()->user()->role->name !== 'Администратор') {
            abort(403);
        }

        $this->validateMovie($request);
        $people = $this->savePeopleFiles($request);
        
        if ($request->filled('poster_url')) {
            $fileName = 'movie_' . $movie->id . '.txt';
            $dir = base_path('media/posters');
            
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

            $this->syncMovieActors($movie, $people['actors']);
            $this->syncMovieDirectors($movie, $people['directors']);
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
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (Movie::resolveTrailer($value) === null) {
                        $fail('Укажите ссылку на YouTube, Rutube, VK Видео или прямую ссылку на MP4, WebM, OGV.');
                    }
                },
            ],
        ], [
            'poster_url.url' => 'Ссылка на постер должна начинаться с http:// или https://.',
            'poster_url.max' => 'Ссылка на постер должна быть не длиннее 255 символов.',
            'trailer_url.url' => 'Ссылка на трейлер должна начинаться с http:// или https://.',
            'trailer_url.max' => 'Ссылка на трейлер должна быть не длиннее 2048 символов.',
        ]);
    }

    private function savePeopleFiles(Request $request): array
    {
        $lists = [
            'actors' => array_values(array_unique(array_map('intval', $request->input('actor_ids') ?? []))),
            'directors' => array_values(array_unique(array_map('intval', $request->input('director_ids') ?? []))),
        ];

        $key = (string) Str::uuid();

        $paths = [
            'actors' => base_path('media/actors/movie_' . $key . '.json'),
            'directors' => base_path('media/directors/movie_' . $key . '.json'),
        ];

        try {
            foreach (['actors', 'directors'] as $folder) {
                $directory = base_path('media/' . $folder);
                if (!is_dir($directory)) {
                    mkdir($directory, 0777, true);
                }
                if (!is_writable($directory)) {
                    throw new \RuntimeException('Папка недоступна для записи: media/' . $folder);
                }
            }

            foreach ($lists as $type => $names) {
                $json = json_encode($names, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
                $written = File::put($paths[$type], $json, true);

                if ($written === false) {
                    throw new \RuntimeException('Не удалось записать файл: ' . $type);
                }
            }

            $actorsFromFile = json_decode(File::get($paths['actors']), true, 512, JSON_THROW_ON_ERROR);
            $directorsFromFile = json_decode(File::get($paths['directors']), true, 512, JSON_THROW_ON_ERROR);

            return [
                'actors' => $actorsFromFile,
                'directors' => $directorsFromFile,
                'people_file_key' => $key,
            ];
        } catch (\Throwable $exception) {
            File::delete(array_values($paths));
            report($exception);
            throw ValidationException::withMessages([
                'people' => 'Не удалось сохранить актёров и режиссёров. Проверьте, что папка media создана в корне проекта и доступна приложению для записи.',
            ]);
        }
    }

    private function parsePeopleNames(?string $value, string $field, int $limit): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($value ?? '')) ?: [];
        $names = array_map(static fn (string $name): string => trim($name), $lines);
        $names = array_filter($names, static fn (string $name): bool => $name !== '');
        $names = array_values(array_unique($names, SORT_STRING));

        if (count($names) > $limit) {
            throw ValidationException::withMessages([
                $field => 'Можно указать не более ' . $limit . ' имён, по одному на строку.',
            ]);
        }

        foreach ($names as $name) {
            if (mb_strlen($name) > 120) {
                throw ValidationException::withMessages([
                    $field => 'Каждое имя должно быть не длиннее 120 символов.',
                ]);
            }
        }

        return $names;
    }

    private function parseActors(array $rows): array
    {
        $actors = [];
        foreach ($rows as $index => $row) {
            $name = trim($row['name'] ?? '');
            $photoUrl = trim($row['photo_url'] ?? '');
            if ($name === '' && $photoUrl === '') continue;
            if ($name === '') {
                throw ValidationException::withMessages([
                    "actors_input.$index.name" => 'Укажите имя актёра, для которого добавлена фотография.',
                ]);
            }
            $actors[] = [
                'name' => $name,
                'photo_url' => $photoUrl !== '' ? $photoUrl : null,
            ];
        }
        return $actors;
    }

    private function selectedActorIds(Request $request): array
    {
        $ids = array_map('intval', $request->input('actor_ids') ?? []);
        if ($request->routeIs('movie.update')) {
            $newActors = $this->parseActors($request->input('actors_input') ?? []);
            foreach ($newActors as $data) {
                $actor = Actor::register($data['name'], $data['photo_url']);
                $ids[] = $actor->id;
            }
        }
        return array_values(array_unique($ids));
    }

    private function syncMovieActors(Movie $movie, array $actorIds): void
    {
        $links = [];
        foreach ($actorIds as $position => $actorId) {
            $links[$actorId] = ['position' => $position];
        }
        $movie->castMembers()->sync($links);
    }

    private function syncMovieDirectors(Movie $movie, array $directorIds): void
    {
        $links = [];
        foreach ($directorIds as $position => $directorId) {
            $links[$directorId] = ['position' => $position];
        }
        $movie->directorMembers()->sync($links);
    }
}