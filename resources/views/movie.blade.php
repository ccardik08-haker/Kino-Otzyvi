@include('components.header')

@php
    $reviewCount = $movie->reviews->count();

    $average = $reviewCount
        ? round($movie->reviews->avg('rating'), 1)
        : null;

    $user = auth()->user();

    $isAuthor = $user
        && (int) $user->id === (int) $movie->moderator_id;

    $userReview = $user
        ? $movie->reviews->firstWhere('user_id', $user->id)
        : null;

    $canRate = $user
        && !$user->is_banned
        && !$isAuthor
        && !$userReview;

    $trailer = $movie->trailer_media;

    $ratingClass = $average === null
        ? 'is-empty'
        : ($average >= 7
            ? 'is-high'
            : ($average >= 5 ? 'is-medium' : 'is-low'));
@endphp

<main class="movie-page">
    @if(session('ok'))
        <div class="alert alert-success" role="status">
            {{ session('ok') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-error" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="movie-detail-layout">
        <div class="movie-media">
            @if($movie->poster_url)
                <img
                    class="movie-poster"
                    src="{{ $movie->poster_url }}"
                    alt="Постер: {{ $movie->title }}"
                >
            @else
                <div class="movie-poster movie-poster-placeholder">
                    Постер не добавлен
                </div>
            @endif

            @if($trailer)
                <figure class="movie-trailer">
                    <div class="trailer-player">
                        @if($trailer['type'] === 'video')
                            <video
                                src="{{ $trailer['url'] }}"
                                controls
                                playsinline
                                preload="metadata"
                                aria-label="Трейлер: {{ $movie->title }}"
                            >
                                Ваш браузер не поддерживает видео.
                            </video>
                        @else
                            <iframe
                                src="{{ $trailer['url'] }}"
                                title="Трейлер: {{ $movie->title }}"
                                loading="lazy"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen"
                                referrerpolicy="strict-origin-when-cross-origin"
                                allowfullscreen
                            ></iframe>
                        @endif
                    </div>

                    <figcaption>Трейлер фильма</figcaption>

                    <a
                        class="trailer-source"
                        href="{{ $movie->trailer_url }}"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        Открыть видео отдельно ↗
                    </a>
                </figure>
            @endif
        </div>

        <section class="movie-summary" aria-labelledby="movie-title">
            <h1 id="movie-title">
                {{ $movie->title }} ({{ $movie->year }})
            </h1>

            <p class="movie-subtitle">
                {{ $movie->category->name }}
            </p>

            <div class="movie-synopsis">{{ $movie->description }}</div>

            <p class="movie-author">
                Выложил:
                <strong>{{ $movie->moderator->login }}</strong>
            </p>
        </section>

        <aside class="movie-rating" aria-label="Рейтинг фильма">
            <p class="movie-rating-value {{ $ratingClass }}">
                {{ $average === null ? '—' : number_format($average, 1, '.', '') }}
            </p>

            <p class="movie-rating-count">
                Оценок: {{ $reviewCount }}
            </p>

            <a class="movie-review-link" href="#movie-reviews">
                Отзывы: {{ $reviewCount }}
            </a>

            <form
                method="POST"
                action="/movie/{{ $movie->id }}/review"
                class="movie-rating-form"
            >
                @csrf

                <fieldset class="rating-picker" @disabled(!$canRate)>
                    <legend>Оценить фильм</legend>

                    <div class="rating-scale">
                        @for($i = 1; $i <= 10; $i++)
                            <label class="rating-option">
                                <input
                                    type="radio"
                                    name="rating"
                                    value="{{ $i }}"
                                    required
                                    aria-label="Оценка {{ $i }} из 10"
                                    @checked(
                                        (string) old('rating', $userReview?->rating)
                                        === (string) $i
                                    )
                                >
                                <span>{{ $i }}</span>
                            </label>
                        @endfor
                    </div>
                </fieldset>

                @if($canRate)
                    <label
                        class="movie-comment-label"
                        for="review-comment"
                    >
                        Отзыв (необязательно)
                    </label>

                    <textarea
                        id="review-comment"
                        name="comment"
                        rows="4"
                        placeholder="Ваше впечатление о фильме"
                    >{{ old('comment') }}</textarea>

                    <button type="submit" class="btn-submit">
                        Отправить оценку
                    </button>
                @endif
            </form>

            @guest
                <p class="movie-rating-note">
                    <a href="{{ route('login') }}">Войдите</a>,
                    чтобы оценить фильм.
                </p>
            @else
                @if($user->is_banned)
                    <p class="movie-rating-note">
                        Вашему аккаунту запрещено оставлять оценки.
                    </p>
                @elseif($isAuthor)
                    <p class="movie-rating-note">
                        Вы добавили этот фильм и не можете его оценивать.
                    </p>
                @elseif($userReview)
                    <p class="movie-rating-note">
                        Ваша оценка:
                        <strong>{{ $userReview->rating }}/10</strong>.
                    </p>
                @endif
            @endguest
        </aside>
    </div>

    <section
        class="reviews-list movie-reviews"
        id="movie-reviews"
    >
        <h3>Отзывы ({{ $reviewCount }})</h3>

        @forelse($movie->reviews as $review)
            <div class="review">
                <strong>{{ $review->user->login }}</strong>
                поставил {{ $review->rating }}/10

                @if($review->comment)
                    <p>{{ $review->comment }}</p>
                @endif
            </div>
        @empty
            <p class="movie-rating-note">
                У этого фильма пока нет оценок и отзывов.
            </p>
        @endforelse
    </section>
</main>

@include('components.footer')