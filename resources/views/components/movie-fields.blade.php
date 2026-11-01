@php
    $posterUrl = old('poster_url', $movie?->poster_url);
@endphp

<div class="movie-editor-layout">
    <div class="movie-editor-media">
        <div
            class="movie-poster-preview"
            aria-label="Предпросмотр постера"
        >
            <img
                id="poster-preview"
                class="movie-poster"
                @if($posterUrl) src="{{ $posterUrl }}" @endif
                alt="Предпросмотр постера"
                @if(!$posterUrl) hidden @endif
            >

            <div
                id="poster-placeholder"
                class="movie-poster movie-poster-placeholder"
                @if($posterUrl) hidden @endif
            >
                Постер фильма
            </div>
        </div>

        <label for="poster-url">
            Ссылка на постер (URL)
        </label>

        <input
            id="poster-url"
            type="url"
            name="poster_url"
            maxlength="255"
            value="{{ $posterUrl }}"
            placeholder="https://example.com/poster.jpg"
        >

        <p class="field-help">
            Необязательно. Прямая ссылка на изображение.
        </p>

        <label for="trailer-url">
            Ссылка на трейлер (URL)
        </label>

        <input
            id="trailer-url"
            type="url"
            name="trailer_url"
            maxlength="2048"
            value="{{ old('trailer_url', $movie?->trailer_url) }}"
            placeholder="https://www.youtube.com/watch?v=..."
        >

        <p class="field-help">
            YouTube, Rutube, VK Видео или файл MP4, WebM, OGV.
            Чтобы удалить трейлер, очистите поле.
        </p>
    </div>

    <div class="movie-editor-text">
        <label for="movie-title-input">
            Название фильма
        </label>

        <input
            id="movie-title-input"
            type="text"
            name="title"
            maxlength="255"
            value="{{ old('title', $movie?->title) }}"
            required
        >

        <div class="movie-editor-meta">
            <div>
                <label for="movie-year">
                    Год выпуска
                </label>

                <input
                    id="movie-year"
                    type="number"
                    name="year"
                    min="1888"
                    max="2100"
                    value="{{ old('year', $movie?->year) }}"
                    required
                >
            </div>

            <div>
                <label for="movie-category">
                    Жанр
                </label>

                <select
                    id="movie-category"
                    name="category_id"
                    required
                >
                    <option
                        value=""
                        disabled
                        @selected(!old('category_id', $movie?->category_id))
                    >
                        Выберите жанр
                    </option>

                    @foreach($categories as $cat)
                        <option
                            value="{{ $cat->id }}"
                            @selected(
                                (string) old('category_id', $movie?->category_id)
                                === (string) $cat->id
                            )
                        >
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <label for="movie-description">
            Описание фильма
        </label>

        <textarea
            id="movie-description"
            name="description"
            rows="12"
            required
        >{{ old('description', $movie?->description) }}</textarea>

        @include('components.person-selector', [
            'field' => 'director_ids',
            'title' => 'Режиссёры',
            'options' => $directorOptions,
            'selected' => $movie
                ? $movie->directorMembers->modelKeys()
                : [],
        ])

        @include('components.person-selector', [
            'field' => 'actor_ids',
            'title' => 'Актёры',
            'options' => $actorOptions,
            'selected' => $movie
                ? $movie->castMembers->modelKeys()
                : [],
        ])

        <p class="field-help">
            Отметьте участников фильма.
            Чтобы убрать человека из этого фильма,
            снимите галочку и сохраните изменения.
        </p>
    </div>
</div>

<script>
(() => {
    const input = document.getElementById('poster-url');
    const image = document.getElementById('poster-preview');
    const placeholder = document.getElementById('poster-placeholder');

    const showPlaceholder = () => {
        image.hidden = true;
        placeholder.hidden = false;
    };

    image.addEventListener('error', showPlaceholder);

    image.addEventListener('load', () => {
        image.hidden = false;
        placeholder.hidden = true;
    });

    input.addEventListener('change', () => {
        try {
            const url = new URL(input.value.trim());

            if (
                !['http:', 'https:'].includes(url.protocol)
                || !input.checkValidity()
            ) {
                throw new Error('Invalid image URL');
            }

            showPlaceholder();
            image.src = url.href;
        } catch {
            image.removeAttribute('src');
            showPlaceholder();
        }
    });

    if (!image.complete || !image.naturalWidth) {
        showPlaceholder();
    }
})();
</script>