@include('components.header')

<h2>Категория: {{ $category->name }}</h2>

<div class="category-list">
    @foreach($categories as $cat)
        <a href="/category/{{ $cat->id }}" class="cat-link">{{ $cat->name }}</a>
    @endforeach
</div>

<div class="movie-grid">
    @forelse($movies as $movie)
        <div class="movie-card">
            <h3><a href="/movie/{{ $movie->id }}">{{ $movie->title }}</a></h3>
            <p class="meta">{{ $movie->year }}</p>
            <p class="rating">⭐ {{ $movie->average_rating }}</p>
        </div>
    @empty
        <p>В этой категории пока нет фильмов.</p>
    @endforelse
</div>

@include('components.footer')