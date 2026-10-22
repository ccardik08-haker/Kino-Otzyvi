<?php

namespace App\Http\Controllers;

use App\Models\User;

class LeaderboardController extends Controller
{
    public function index()
    {
        $users = User::with('role')->orderBy('score', 'desc')->get();
        return view('leaderboard', compact('users'));
    }
}