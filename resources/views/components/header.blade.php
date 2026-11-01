<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>КиноРейтинг</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>
<header>
    <div class="header-content">
        <span class="logo">🎬 КиноРейтинг</span>
        <nav>
            <a href="/">Главная</a>
            <a href="/leaderboard">Рейтинг</a>
            @auth

                @if(in_array(
                    auth()->user()->role?->name,
                    ['Модератор', 'Администратор'],
                    true
                ))
                    <a
                        href="{{ route('people.index', ['type' => 'actors']) }}"
                        class="header-people-link"
                    >
                        Актёры и режиссёры
                    </a>
                @endif

                @if(auth()->user()->role->name === 'Модератор')
                    <a href="/moderator">Добавить фильм</a>
                    <a href="/my-movies">Мои фильмы</a>
                @endif
                @if(auth()->user()->role->name === 'Администратор')
                    <a href="/admin">Админка</a>
                @endif
                <span class="user-info">
                {{ auth()->user()->login }} 
                @if(auth()->user()->role->name !== 'Администратор')
                    (⭐{{ auth()->user()->score }})
                @endif
                </span>
                <form method="POST" action="/logout" style="display:inline">
                    @csrf
                    <button type="submit" class="btn-logout">Выйти</button>
                </form>
            @else
                <a href="/login">Вход</a>
                <a href="/register">Регистрация</a>
            @endauth
        </nav>
        

    
    </div>
</header>
<div class="container">