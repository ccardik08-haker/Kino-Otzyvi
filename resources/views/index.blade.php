@use('Illuminate\Support\Str')

@include('components.header')

<form
    method="GET"
    action="{{ route('home') }}"
    class="site-search home-search"
    role="search"
>
    <input
        type="search"
        name="q"
        value="{{ $searchTerm }}"
        maxlength="200"
        placeholder="Название, жанр, актёр или режиссёр"
        aria-label="Поисковый запрос"
    >

    <select name="search_in" aria-label="Область поиска">
        <option value="all" @selected($searchIn === 'all')>
            Везде
        </option>

        <option value="title" @selected($searchIn === 'title')>
            Название фильма
        </option>

        <option value="genre" @selected($searchIn === 'genre')>
            Жанр
        </option>

        <option value="actor" @selected($searchIn === 'actor')>
            Актёр
        </option>

        <option value="director" @selected($searchIn === 'director')>
            Режиссёр
        </option>
    </select>

    <button type="submit">Найти</button>
</form>

@php
    $currentSort = request('sort') ?: 'newest';

    $filterParams = array_filter(
        [
            'q' => $searchTerm,
            'search_in' => $searchIn,
            'sort' => $currentSort,
            'category' => request('category'),
        ],
        static fn ($value) => $value !== null && $value !== ''
    );
@endphp

@if(
    $searchTerm !== ''
    || in_array($searchIn, ['actor', 'director'], true)
)
    <h2>
        @if($searchTerm !== '')
            Результаты поиска: «{{ $searchTerm }}»
        @else
            {{ $searchIn === 'actor' ? 'Актёры' : 'Режиссёры' }}
        @endif
    </h2>

    <div class="search-summary">
        <span>
            Найдено:
            {{
                $movies->total()
                + ($actorResults?->total() ?? 0)
                + ($directorResults?->total() ?? 0)
            }}
        </span>

        <a href="{{ route('home') }}">Сбросить поиск</a>
    </div>
@else
    <h2>Все фильмы</h2>
@endif

@if(session('ok'))
    <div class="alert alert-success">
        {{ session('ok') }}
    </div>
@endif

@if($errors->any())
    <div class="alert alert-error">
        @foreach($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

@if(
    $actorResults
    && ($actorResults->total() > 0 || $searchIn === 'actor')
)
    @include('components.search-people', [
        'results' => $actorResults,
        'type' => 'actors',
        'title' => 'Актёры',
    ])
@endif

@if(
    $directorResults
    && ($directorResults->total() > 0 || $searchIn === 'director')
)
    @include('components.search-people', [
        'results' => $directorResults,
        'type' => 'directors',
        'title' => 'Режиссёры',
    ])
@endif

@if($showMovieSection)

<div class="filters">
    <div class="filter-group">
        <span class="filter-label">Сортировка:</span>

        <a
            href="{{ route('home', array_merge(
                $filterParams,
                ['sort' => 'newest']
            )) }}"
            class="filter-btn {{ $currentSort === 'newest' ? 'active' : '' }}"
        >
            Новинки
        </a>

        <a
            href="{{ route('home', array_merge(
                $filterParams,
                ['sort' => 'rating_desc']
            )) }}"
            class="filter-btn {{ $currentSort === 'rating_desc' ? 'active' : '' }}"
        >
            Рейтинг ▼
        </a>

        <a
            href="{{ route('home', array_merge(
                $filterParams,
                ['sort' => 'rating_asc']
            )) }}"
            class="filter-btn {{ $currentSort === 'rating_asc' ? 'active' : '' }}"
        >
            Рейтинг ▲
        </a>
    </div>

    <div class="filter-group">
        <span class="filter-label">Жанр:</span>

        <a
            href="{{ route('home', array_merge(
                $filterParams,
                ['category' => null]
            )) }}"
            class="filter-btn {{ !request('category') ? 'active' : '' }}"
        >
            Все
        </a>

        @foreach($categories as $cat)
            <a
                href="{{ route('home', array_merge(
                    $filterParams,
                    ['category' => $cat->id]
                )) }}"
                class="filter-btn {{ request('category') == $cat->id ? 'active' : '' }}"
            >
                {{ $cat->name }}
            </a>
        @endforeach
    </div>
</div>

<div class="movie-grid">
    @forelse($movies as $movie)
        <div class="movie-card-link">
            <article class="movie-card author-linked-card">
                <a
                    href="{{ route('movie', $movie->id) }}"
                    class="film-card-main-link"
                >
                    @if($movie->poster_url)
                        <img
                            src="{{ $movie->poster_url }}"
                            alt="{{ $movie->title }}"
                            class="card-poster"
                            loading="lazy"
                        >
                    @else
                        <div class="card-poster card-poster-empty">
                            Нет постера
                        </div>
                    @endif

                    <h3>{{ $movie->title }}</h3>
                </a>

                <p class="meta">
                    {{ $movie->year }}
                    |
                    {{ $movie->category->name }}
                </p>

                <p class="rating">
                    ⭐ {{ number_format(
                        (float) ($movie->reviews_avg_rating ?? 0),
                        1,
                        '.',
                        ''
                    ) }} / 10
                </p>

                <p class="description">
                    {{ Str::limit($movie->description, 50) }}
                </p>

                <p class="author">
                    Автор:

                    <a
                        href="{{ route('filmography', [
                            'type' => 'authors',
                            'id' => $movie->moderator_id,
                        ]) }}"
                        class="author-filmography-link"
                    >
                        {{ $movie->moderator->login }}
                    </a>
                </p>
            </article>
        </div>
    @empty
        <p class="movie-grid-empty">
            @if($searchTerm !== '')
                Ничего не найдено.
                Попробуйте изменить запрос или область поиска.
            @else
                Фильмов пока нет.
            @endif
        </p>
    @endforelse
</div>

@if($movies->hasPages())
    <div class="movies-pagination">
        @if($movies->previousPageUrl())
            <a href="{{ $movies->previousPageUrl() }}">
                ← Назад
            </a>
        @endif

        <span>
            Страница {{ $movies->currentPage() }}
            из {{ $movies->lastPage() }}
        </span>

        @if($movies->hasMorePages())
            <a href="{{ $movies->nextPageUrl() }}">
                Вперёд →
            </a>
        @endif
    </div>
@endif
@endif
@include('components.footer')