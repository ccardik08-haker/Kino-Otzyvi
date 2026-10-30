@use('Illuminate\Support\Str')
@include('components.header')

<h2>Мои фильмы</h2>

@if(session('ok'))
    <div class="alert alert-success">{{ session('ok') }}</div>
@endif

<div class="movie-grid">
    @forelse($movies as $movie)
        <div class="movie-card" style="position: relative;">
            @if($movie->poster_url)
                <img src="{{ $movie->poster_url }}" alt="{{ $movie->title }}" class="card-poster">
            @else
                <div class="card-poster" style="background: #333; display:flex; align-items:center; justify-content:center; color:#666;">Нет постера</div>
            @endif
            <h3>{{ $movie->title }}</h3>
            <p class="meta">{{ $movie->year }} | {{ $movie->category->name }}</p>
            <p class="rating">⭐ {{ $movie->average_rating }} / 10</p>
            <p class="description">{{ Str::limit($movie->description, 50) }}</p>
            <div style="margin-top: auto; padding-top: 15px;">
                <a href="/my-movies/{{ $movie->id }}/edit" class="btn-small" style="display: block; text-align: center; text-decoration: none; background: #ffcc00; color: #121212; padding: 10px; font-weight: bold; border-radius: 4px;">Редактировать</a>
            </div>
        </div>
    @empty
        <p style="grid-column: 1 / -1;">Вы пока не добавили ни одного фильма.</p>
    @endforelse
</div>
@include('components.footer')