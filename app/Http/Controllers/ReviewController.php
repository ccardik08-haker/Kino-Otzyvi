<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request, $movie_id)
{
    $request->validate(['rating' => 'required|integer|min:1|max:10']);

    $user = auth()->user();
    
    if ($user->is_banned) {
        return back()->withErrors(['msg' => 'Вы забанены и не можете оставлять оценки.']);
    }

    $movie = Movie::findOrFail($movie_id);
    if ($movie->moderator_id === $user->id) {
        return back()->withErrors(['msg' => 'Вы не можете оценивать фильм, который добавили сами.']);
    }

    $existing = Review::where('movie_id', $movie_id)->where('user_id', $user->id)->first();
    if ($existing) {
        return back()->withErrors(['msg' => 'Вы уже оценили этот фильм.']);
    }

    Review::create([
        'rating' => $request->rating,
        'comment' => $request->comment,
        'movie_id' => $movie_id,
        'user_id' => $user->id,
    ]);
    
    if ($user->role->name !== 'Администратор') {
        $user->increment('score', 1);
    }

    if ($movie->moderator->role->name !== 'Администратор') {
        $movie->moderator->increment('score', $request->rating); 
    }

    return back()->with('ok', 'Оценка добавлена! Вам +1 очко, автору фильма +' . $request->rating . ' очков.');
}
}