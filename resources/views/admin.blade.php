@use('Illuminate\Support\Str')
@include('components.header')

<h2>Админ-панель</h2>

@if(session('ok')) <div class="alert alert-success">{{ session('ok') }}</div> @endif
@if ($errors->any()) <div class="alert alert-error">@foreach ($errors->all() as $error) <p>{{ $error }}</p> @endforeach</div> @endif

<h3>Пользователи</h3>
<table class="table">
    <thead><tr><th>Логин</th><th>Роль</th><th>Очки</th><th>Бан</th><th>Действия</th></tr></thead>
    <tbody>
        @foreach($users as $u)
            <tr>
                <td>{{ $u->login }}</td>
                <td>{{ $u->role->name }}</td>
                <td>@if($u->role->name === 'Администратор') <span style="color: #666; font-style: italic;">—</span> @else <strong>{{ $u->score }}</strong> @endif</td>
                <td>{{ $u->is_banned ? 'Забанен' : 'Активен' }}</td>
                <td>
                    @if($u->role->name !== 'Администратор')
                        <form method="POST" action="/admin/user/{{ $u->id }}/ban" style="display:inline">@csrf<button type="submit" class="btn-small">{{ $u->is_banned ? 'Разбанить' : 'Забанить' }}</button></form>
                        <form method="POST" action="/admin/user/{{ $u->id }}/score" style="display:inline">@csrf<input type="number" name="amount" placeholder="+/-" style="width: 50px;"><button type="submit" class="btn-small">Очки</button></form>
                    @endif
                    <form method="POST" action="/admin/user/{{ $u->id }}/role" style="display:inline">
                        @csrf
                        <select name="role_id">@foreach($roles as $role) <option value="{{ $role->id }}" @if($u->role_id == $role->id) selected @endif>{{ $role->name }}</option> @endforeach</select>
                        <button type="submit" class="btn-small">Сменить роль</button>
                    </form>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

<h3 style="margin-top: 40px; border-bottom: 1px solid #333; padding-bottom: 10px;">Все фильмы</h3>
<div class="movie-grid" style="margin-top: 20px;">
    @forelse($movies as $movie)
        <div class="movie-card" style="position: relative;">
            @if($movie->poster_url)
    @if(str_starts_with($movie->poster_url, 'http'))
        <img src="{{ $movie->poster_url }}" alt="{{ $movie->title }}" class="card-poster">
    @else
        <img src="/media/posters/{{ $movie->poster_url }}" alt="{{ $movie->title }}" class="card-poster">
    @endif
@else
    <div class="card-poster" style="background: #333; display:flex; align-items:center; justify-content:center; color:#666;">Нет постера</div>
@endif
            <h3>{{ $movie->title }}</h3>
            <p class="meta">{{ $movie->year }} | {{ $movie->category->name }}</p>
            <p class="rating">⭐ {{ $movie->average_rating }} / 10</p>
            <p class="description">{{ Str::limit($movie->description, 50) }}</p>
            <p class="author">Автор: {{ $movie->moderator->login }}</p>
            <div style="margin-top: auto; padding-top: 15px;">
                <a href="/my-movies/{{ $movie->id }}/edit" class="btn-small" style="display: block; text-align: center; text-decoration: none; background: #ffcc00; color: #121212; padding: 8px; font-weight: bold; border-radius: 4px;">Редактировать</a>
            </div>
        </div>
    @empty
        <p style="grid-column: 1 / -1; color: #888;">Фильмов пока нет.</p>
    @endforelse
</div>
@include('components.footer')