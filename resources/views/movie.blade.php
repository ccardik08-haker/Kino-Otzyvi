@include('components.header')

<div class="movie-page">
    <h1>{{ $movie->title }} ({{ $movie->year }})</h1>
    @if($movie->poster_url)
        <div style="text-align: center; margin: 20px 0;">
            <img src="{{ $movie->poster_url }}" alt="{{ $movie->title }}" style="max-width: 300px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.5);">
        </div>
    @endif
    <p class="genre">Жанр: {{ $movie->category->name }}</p>
    <p class="author-info">Выложил: <strong>{{ $movie->moderator->login }}</strong></p>
    <p class="avg-rating">Средняя оценка: ⭐ <strong>{{ $movie->average_rating }}</strong> / 10</p>
    
    <div class="description">
        <p>{!! nl2br(e($movie->description)) !!}</p>
    </div>

    @auth
    @if(!auth()->user()->is_banned && auth()->id() !== $movie->moderator_id)
        @php
            $hasReviewed = $movie->reviews->where('user_id', auth()->id())->first();
        @endphp
        
        @if($hasReviewed)
            <div class="alert alert-success">
                Вы уже оценили этот фильм на {{ $hasReviewed->rating }}/10.
            </div>
        @else
            <div class="review-form">
                <h3>Оценить фильм</h3>
                @if($errors->any())
                    <div class="alert alert-error">{{ $errors->first() }}</div>
                @endif
                <form method="POST" action="/movie/{{ $movie->id }}/review">
                    @csrf
                    <label>Оценка (1-10):</label>
                    <label>Оценка:</label>
                    <select name="rating" required style="width: 100%; padding: 10px; background: #2a2a2a; border: 1px solid #444; color: #fff; border-radius: 4px; margin-bottom: 15px;">
                        <option value="" disabled selected>Выберите оценку...</option>
                        @for ($i = 1; $i <= 10; $i++)
                            <option value="{{ $i }}">{{ $i }} / 10</option>
                        @endfor
                    </select>
                    <textarea name="comment" placeholder="Ваш отзыв..."></textarea>
                    <button type="submit" class="btn-submit">Отправить</button>
                </form>
            </div>
        @endif
    @elseif(auth()->id() === $movie->moderator_id)
        <p style="color: #888; font-style: italic;">Вы автор этого фильма и не можете его оценивать.</p>
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