@include('components.header')

<h2>Все фильмы</h2>

@if(session('ok'))
    <div class="alert alert-success">{{ session('ok') }}</div>
@endif

<div class="movie-grid">
    @forelse($movies as $movie)
        <div class="movie-card">
            <h3><a href="/movie/{{ $movie->id }}">{{ $movie->title }}</a></h3>
            <p class="meta">{{ $movie->year }} | {{ $movie->category->name }}</p>
            <p class="rating">⭐ {{ $movie->average_rating }} / 10</p>
            <p class="author">Автор: {{ $movie->moderator->login }}</p>
        </div>
    @empty
        <p>Фильмов пока нет.</p>
    @endforelse
</div>

{{ $movies->links() }}

@include('components.footer')