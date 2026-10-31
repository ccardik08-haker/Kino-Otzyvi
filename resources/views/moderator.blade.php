@include('components.header')

<h2>Добавить фильм</h2>

<p class="movie-form-intro">
    Постер и трейлер будут слева,
    название и описание — рядом с ними.
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
    action="{{ route('moderator') }}"
    class="admin-form movie-editor"
>
    @csrf

    @include('components.movie-fields', ['movie' => null])

    <div class="movie-editor-actions">
        <button type="submit" class="btn-submit">
            Добавить фильм
        </button>

        <a href="{{ route('home') }}">Отмена</a>
    </div>
</form>

@include('components.footer')