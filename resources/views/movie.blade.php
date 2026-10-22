@include('components.header')

<div class="movie-page">
    <h1>{{ $movie->title }} ({{ $movie->year }})</h1>
    <p class="genre">Жанр: {{ $movie->category->name }}</p>
    <p class="avg-rating">Средняя оценка: ⭐ <strong>{{ $movie->average_rating }}</strong> / 10</p>
    
    <div class="description">
        <p>{{ $movie->description }}</p>
    </div>

    @auth
        @if(!auth()->user()->is_banned)
            <div class="review-form">
                <h3>Оценить фильм</h3>
                @if($errors->any())
                    <div class="alert alert-error">{{ $errors->first() }}</div>
                @endif
                <form method="POST" action="/movie/{{ $movie->id }}/review">
                    @csrf
                    <label>Оценка (1-10):</label>
                    <input type="number" name="rating" min="1" max="10" required>
                    <textarea name="comment" placeholder="Ваш отзыв..."></textarea>
                    <button type="submit" class="btn-submit">Отправить</button>
                </form>
            </div>
        @endif
    @endauth

    <div class="reviews-list">
        <h3>Отзывы ({{ $movie->reviews->count() }})</h3>
        @foreach($movie->reviews as $review)
            <div class="review">
                <strong>{{ $review->user->login }}</strong> поставил {{ $review->rating }}/10
                <p>{{ $review->comment }}</p>
            </div>
        @endforeach
    </div>
</div>

@include('components.footer')