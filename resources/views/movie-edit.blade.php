@include('components.header')

<h2>Редактировать фильм: {{ $movie->title }}</h2>

<p class="movie-form-intro">
    Измените данные фильма или добавьте ссылку на трейлер.
</p>

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

@include('components.footer')