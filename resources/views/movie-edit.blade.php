@include('components.header')

<h2>Редактировать фильм: {{ $movie->title }}</h2>

<p class="movie-form-intro">
    Измените данные фильма или добавьте ссылку на трейлер.
</p>

@if(session('ok'))
    <div class="alert alert-success" role="status">
        {{ session('ok') }}
    </div>
@endif

@if($errors->any())
    <div class="alert alert-error" role="alert">
        @foreach($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<form
    method="POST"
    action="{{ route('movie.update', $movie->id) }}"
    class="admin-form movie-editor"
>
    @csrf

    @include('components.movie-fields', ['movie' => $movie])

    <div class="movie-editor-actions">
        <button type="submit" class="btn-submit">
            Сохранить изменения
        </button>

        <a href="{{ route('my-movies') }}">Отмена</a>
    </div>
</form>

@if(auth()->user()->role->name === 'Администратор')
    @php
        $comments = $movie->reviews
            ->filter(fn ($review) => filled($review->comment))
            ->sortByDesc('id');
    @endphp

    <section class="reviews-list" id="movie-comments">
        <h2>Комментарии к фильму ({{ $comments->count() }})</h2>

        @forelse($comments as $review)
            <div class="review">
                <div>
                    <strong>{{ $review->user->login }}</strong>
                    — оценка {{ $review->rating }}/10
                </div>

                <p style="white-space: pre-wrap; overflow-wrap: anywhere;">{{ $review->comment }}</p>

                <form
                    method="POST"
                    action="{{ route('admin.comment.delete', [
                        'movieId' => $movie->id,
                        'reviewId' => $review->id,
                    ]) }}"
                    style="margin-top: 12px;"
                >
                    @csrf
                    @method('DELETE')

                    <button type="submit" class="btn-small">
                        Удалить комментарий
                    </button>
                </form>
            </div>
        @empty
            <p style="color: #999;">
                У этого фильма пока нет комментариев.
            </p>
        @endforelse
    </section>
@endif

@include('components.footer')