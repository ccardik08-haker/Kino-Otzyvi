<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>КиноРейтинг</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>
<header>
    <div class="header-content">
        <a href="/" class="logo">🎬 КиноРейтинг</a>
        <nav>
            <a href="/leaderboard">Рейтинг</a>
            @auth
                @if(in_array(auth()->user()->role->name, ['Модератор', 'Администратор']))
                    <a href="/moderator">Добавить фильм</a>
                @endif
                @if(auth()->user()->role->name === 'Администратор')
                    <a href="/admin">Админка</a>
                @endif
                <span class="user-info">{{ auth()->user()->login }} (⭐{{ auth()->user()->score }})</span>
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