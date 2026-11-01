@include('components.header')

@php
    $personRoute = [
        'type' => $type,
        'id' => $person->id,
    ];
@endphp

<main class="filmography-page">
    <a href="{{ route('home') }}" class="filmography-back">
        ← Все фильмы
    </a>

    <section class="filmography-hero">
        @if($person->photo_url)
            <img
                src="{{ $person->photo_url }}"
                alt="{{ $person->name }}"
                class="filmography-person-photo"
            >
        @else
            <div
                class="filmography-person-photo filmography-person-placeholder"
                aria-hidden="true"
            >
                {{ mb_substr($person->name, 0, 1) }}
            </div>
        @endif

        <div class="filmography-person-info">
            <p class="filmography-eyebrow">
                {{ $roleLabel }} · Фильмография
            </p>

            <h1>{{ $person->name }}</h1>

            <p class="filmography-counter">
                Фильмов на сайте:
                <strong>{{ $movies->total() }}</strong>
            </p>
        </div>
    </section>

    <div class="filmography-toolbar">
        <h2>{{ $sectionTitle }}</h2>

        <div class="filmography-sort">
            <a
                href="{{ route('filmography', array_merge(
                    $personRoute,
                    ['sort' => 'newest']
                )) }}"
                class="filter-btn {{ $sort === 'newest' ? 'active' : '' }}"
            >
                Сначала новые
            </a>

            <a
                href="{{ route('filmography', array_merge(
                    $personRoute,
                    ['sort' => 'oldest']
                )) }}"
                class="filter-btn {{ $sort === 'oldest' ? 'active' : '' }}"
            >
                Сначала старые
            </a>

            <a
                href="{{ route('filmography', array_merge(
                    $personRoute,
                    ['sort' => 'rating']
                )) }}"
                class="filter-btn {{ $sort === 'rating' ? 'active' : '' }}"
            >
                По рейтингу
            </a>
        </div>
    </div>

    <div class="movie-grid">
        @forelse($movies as $movie)
            <a
                href="{{ route('movie', $movie->id) }}"
                class="movie-card-link"
            >
                <article class="movie-card filmography-movie-card">
                    <div class="filmography-poster-wrap">
                        @if($movie->poster_url)
                            <img
                                src="{{ $movie->poster_url }}"
                                alt="Постер: {{ $movie->title }}"
                                class="card-poster"
                                loading="lazy"
                            >
                        @else
                            <div class="card-poster filmography-poster-empty">
                                Нет постера
                            </div>
                        @endif

                        <span
                            class="filmography-rating"
                            title="Средняя оценка"
                        >
                            {{ $movie->reviews_avg_rating === null
                                ? '—'
                                : number_format(
                                    (float) $movie->reviews_avg_rating,
                                    1,
                                    '.',
                                    ''
                                ) }}
                        </span>
                    </div>

                    <h3>{{ $movie->title }}</h3>

                    <p class="meta">
                        {{ $movie->year }}
                        ·
                        {{ $movie->category->name }}
                    </p>
                </article>
            </a>
        @empty
            <div class="filmography-empty">
                <h3>Фильмы пока не добавлены</h3>

                <p>{{ $emptyMessage }}</p>
            </div>
        @endforelse
    </div>

    @if($movies->hasPages())
        <div class="filmography-pagination">
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
</main>

@include('components.footer')