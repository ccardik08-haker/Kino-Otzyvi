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

        $movie = Movie::findOrFail($movie_id);
        
        $user->increment('score', 1);
        $movie->moderator->increment('score', 20);

        return back()->with('ok', 'Оценка добавлена! Вам +1 очко, автору фильма +20.');
    }
}