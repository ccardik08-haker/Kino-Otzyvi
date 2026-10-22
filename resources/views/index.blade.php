@use('Illuminate\Support\Str')
@include('components.header')

<h2>Все фильмы</h2>

@if(session('ok'))
    <div class="alert alert-success">{{ session('ok') }}</div>
@endif

<!-- БЛОК ФИЛЬТРОВ -->
<div class="filters">
    <div class="filter-group">
        <span class="filter-label">Сортировка:</span>
        <!-- В ссылки сортировки добавляем текущий выбранный жанр -->
        <a href="/?sort=newest{{ request('category') ? '&category=' . request('category') : '' }}" 
           class="filter-btn {{ request('sort') == 'newest' || !request('sort') ? 'active' : '' }}">Новинки</a>
        
        <a href="/?sort=rating_desc{{ request('category') ? '&category=' . request('category') : '' }}" 
           class="filter-btn {{ request('sort') == 'rating_desc' ? 'active' : '' }}">Рейтинг ▼</a>
        
        <a href="/?sort=rating_asc{{ request('category') ? '&category=' . request('category') : '' }}" 
           class="filter-btn {{ request('sort') == 'rating_asc' ? 'active' : '' }}">Рейтинг ▲</a>
    </div>

    <div class="filter-group">
        <span class="filter-label">Жанр:</span>
        <!-- В ссылки жанров добавляем текущую выбранную сортировку -->
        <a href="/{{ request('sort') ? '?sort=' . request('sort') : '' }}" 
           class="filter-btn {{ !request('category') ? 'active' : '' }}">Все</a>
        
        @foreach($categories as $cat)
            <a href="/?category={{ $cat->id }}{{ request('sort') ? '&sort=' . request('sort') : '' }}" 
               class="filter-btn {{ request('category') == $cat->id ? 'active' : '' }}">
               {{ $cat->name }}
            </a>
        @endforeach
    </div>
</div>

<div class="movie-grid">
    @forelse($movies as $movie)
        <a href="/movie/{{ $movie->id }}" class="movie-card-link">
            <div class="movie-card">
                @if($movie->poster_url)
                    <img src="{{ $movie->poster_url }}" alt="{{ $movie->title }}" class="card-poster">
                @else
                    <div class="card-poster" style="background: #333; display:flex; align-items:center; justify-content:center; color:#666;">Нет постера</div>
                @endif

                <h3>{{ $movie->title }}</h3>
                <p class="meta">{{ $movie->year }} | {{ $movie->category->name }}</p>
                <p class="rating">⭐ {{ $movie->average_rating }} / 10</p>
                <p class="description">{{ Str::limit($movie->description, 50) }}</p>
                <p class="author">Автор: {{ $movie->moderator->login }}</p>
            </div>
        </a>
    @empty
        <p>Фильмов пока нет.</p>
    @endforelse
</div>

<div style="margin-top: 30px;">
    {{ $movies->appends(request()->query())->links() }}
</div>

@include('components.footer')