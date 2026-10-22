<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Movie;
use Illuminate\Http\Request;

class MovieController extends Controller
{
    public function index()
    {
        $movies = Movie::with(['category', 'moderator'])->latest()->paginate(10);
        return view('index', compact('movies'));
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
}