<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function index()
    {
        $users = User::with('role')->get();
        $movies = Movie::with(['moderator', 'category'])->get();
        $roles = Role::all();
        return view('admin', compact('users', 'movies', 'roles'));
    }

    public function toggleBan($id)
    {
        $user = User::findOrFail($id);
        if ($user->role->name === 'Администратор') {
            return back();
        }
        $user->is_banned = !$user->is_banned;
        $user->save();
        return back()->with('ok', 'Статус бана изменен.');
    }

    public function changeScore(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $user->increment('score', $request->amount);
        return back()->with('ok', 'Рейтинг обновлен.');
    }

    public function changeRole(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $user->role_id = $request->role_id;
        $user->save();
        return back()->with('ok', 'Роль изменена.');
    }
}