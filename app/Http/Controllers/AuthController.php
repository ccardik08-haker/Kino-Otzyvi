<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'login' => 'required|string|min:3|max:50|unique:users,login',
            'password' => 'required|string|min:4|confirmed',
        ]);

        $role = Role::where('name', 'Пользователь')->first();

        User::create([
            'login' => $data['login'],
            'password' => Hash::make($data['password']),
            'role_id' => $role->id,
        ]);

        return redirect('/login')->with('ok', 'Регистрация успешна.');
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($credentials)) {
            if (auth()->user()->is_banned) {
                Auth::logout();
                return back()->withErrors(['login' => 'Вы забанены.']);
            }
            $request->session()->regenerate();
            return redirect('/');
        }

        return back()->withErrors(['login' => 'Неверный логин или пароль']);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}