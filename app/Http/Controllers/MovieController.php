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
    $categories = \App\Models\Category::all();

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
        $request->validate([
            'title' => 'required|string|max:255',
            'year' => 'required|integer',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
        ]);

        Movie::create([
            'title' => $request->title,
            'year' => $request->year,
            'description' => $request->description,
            'poster_url' => $request->poster_url,
            'category_id' => $request->category_id,
            'moderator_id' => auth()->id(),
        ]);

        return redirect('/')->with('ok', 'Фильм добавлен!');
    }
    public function myMovies()
{
    // Берем только фильмы текущего автора
    $movies = Movie::where('moderator_id', auth()->id())->latest()->get();
    return view('my-movies', compact('movies'));
}

public function edit($id)
{
    $movie = Movie::findOrFail($id);
    
    // Проверка: автор может редактировать только свои фильмы
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

    $request->validate([
        'title' => 'required|string|max:255',
        'year' => 'required|integer|min:1888|max:2100',
        'description' => 'required|string',
        'category_id' => 'required|exists:categories,id',
    ]);

    $movie->update([
        'title' => $request->title,
        'year' => $request->year,
        'description' => $request->description,
        'poster_url' => $request->poster_url,
        'category_id' => $request->category_id,
    ]);

    return redirect('/my-movies')->with('ok', 'Фильм успешно обновлен!');
}
}