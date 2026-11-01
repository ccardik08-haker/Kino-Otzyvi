<section class="search-person-section">
    <div class="search-person-heading">
        <h2>{{ $title }}</h2>
        <span>{{ $results->total() }}</span>
    </div>

    <div class="search-person-grid">
        @forelse($results as $person)
            <a
                href="{{ route('filmography', [
                    'type' => $type,
                    'id' => $person->id,
                ]) }}"
                class="search-person-card"
            >
                @if($person->photo_url)
                    <img
                        src="{{ $person->photo_url }}"
                        alt=""
                        class="search-person-photo"
                        loading="lazy"
                    >
                @else
                    <span
                        class="search-person-photo search-person-placeholder"
                        aria-hidden="true"
                    >
                        {{ mb_substr($person->name, 0, 1) }}
                    </span>
                @endif

                <div class="search-person-info">
                    <span class="search-person-role">
                        {{ $type === 'actors' ? 'Актёр' : 'Режиссёр' }}
                    </span>

                    <h3>{{ $person->name }}</h3>

                    <p>
                        Фильмов на сайте:
                        <strong>{{ $person->movies_count }}</strong>
                    </p>

                    <span class="search-person-open">
                        Фильмография →
                    </span>
                </div>
            </a>
        @empty
            <p class="search-person-empty">
                По вашему запросу ничего не найдено.
            </p>
        @endforelse
    </div>

    @if($results->hasPages())
        <div class="search-person-pagination">
            @if($results->previousPageUrl())
                <a href="{{ $results->previousPageUrl() }}">
                    ← Назад
                </a>
            @endif

            <span>
                Страница {{ $results->currentPage() }}
                из {{ $results->lastPage() }}
            </span>

            @if($results->hasMorePages())
                <a href="{{ $results->nextPageUrl() }}">
                    Вперёд →
                </a>
            @endif
        </div>
    @endif
</section>