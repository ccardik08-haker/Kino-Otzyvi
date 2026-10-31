<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Movie;
use Illuminate\Http\Request;

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
        $movie = Movie::with(['reviews.user', 'category'])->findOrFail($id);
        return view('movie', compact('movie'));
    }

    public function create()
    {
        $categories = Category::all();
        return view('moderator', compact('categories'));
    }

    public function store(Request $request)
    {
        $this->validateMovie($request);

        $movie = Movie::create([
            'title' => $request->title,
            'year' => $request->year,
            'description' => $request->description,
            'poster_url' => $request->poster_url, 
            'trailer_url' => $request->trailer_url,
            'category_id' => $request->category_id,
            'moderator_id' => auth()->id(),
        ]);

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
        $movie = Movie::with('reviews.user')->findOrFail($id);
        
        if ($movie->moderator_id !== auth()->id() && auth()->user()->role->name !== 'Администратор') {
            abort(403, 'Вы можете редактировать только свои фильмы.');
        }

        $categories = Category::all();
        return view('movie-edit', compact('movie', 'categories'));
    }

    public function update(Request $request, $id)
    {
        $movie = Movie::findOrFail($id);

        if ($movie->moderator_id !== auth()->id() && auth()->user()->role->name !== 'Администратор') {
            abort(403);
        }

        $this->validateMovie($request);

        if ($request->filled('poster_url')) {
            $fileName = 'movie_' . $movie->id . '.txt';
            $dir = public_path('posters');
            
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }
            
            file_put_contents($dir . '/' . $fileName, $request->poster_url);
        }

        $movie->update([
            'title' => $request->title,
            'year' => $request->year,
            'description' => $request->description,
            'poster_url' => $request->poster_url,
            'trailer_url' => $request->trailer_url,
            'category_id' => $request->category_id,
        ]);

        return redirect('/my-movies')->with('ok', 'Фильм обновлен!');
    }
    private function validateMovie(Request $request): void
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'year' => 'required|integer|min:1888|max:2100',
            'description' => 'required|string',
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
}