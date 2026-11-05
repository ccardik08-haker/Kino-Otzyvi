@include('components.header')

<h2>Категория: {{ $category->name }}</h2>

<div class="category-list">
    @foreach($categories as $cat)
        <a href="/category/{{ $cat->id }}" class="cat-link">{{ $cat->name }}</a>
    @endforeach
</div>

<div class="movie-grid">
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
            
            <h3><a href="/movie/{{ $movie->id }}" style="color: #ffcc00; text-decoration: none;">{{ $movie->title }}</a></h3>
            <p class="meta">{{ $movie->year }}</p>
            <p class="rating">⭐ {{ $movie->average_rating }} / 10</p>
        </div>
    @empty
        <p>В этой категории пока нет фильмов.</p>
    @endforelse
</div>

@include('components.footer')