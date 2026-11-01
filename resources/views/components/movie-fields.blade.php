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
            aria-describedby="poster-help"
        >

        <p id="poster-help" class="field-help">
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
            aria-describedby="trailer-help"
        >

        <p id="trailer-help" class="field-help">
            Необязательно. YouTube, Rutube, VK Видео
            или файл MP4, WebM, OGV.
            После сохранения плеер появится под постером.
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
                <label for="movie-year">Год выпуска</label>

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
                <label for="movie-category">Жанр</label>

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
            placeholder="Кратко расскажите о сюжете фильма"
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

        @php
            $selectedActorIds = old(
                'actor_ids',
                $movie ? $movie->castMembers->modelKeys() : []
            );

            $selectedActorIds = array_map(
                'strval',
                is_array($selectedActorIds) ? $selectedActorIds : []
            );
        @endphp

<div class="actor-catalog">
    <h3>Выбрать актёров</h3>

    <p class="field-help">
        Отметьте актёров, которые участвуют в фильме.
    </p>

    <div class="actor-catalog-list">
        @forelse($actorOptions as $actor)
            <label class="actor-catalog-option">
                <input
                    type="checkbox"
                    name="actor_ids[]"
                    value="{{ $actor->id }}"
                    @checked(
                        in_array(
                            (string) $actor->id,
                            $selectedActorIds,
                            true
                        )
                    )
                >

                @if($actor->photo_url)
                    <img
                        src="{{ $actor->photo_url }}"
                        alt=""
                        loading="lazy"
                    >
                @endif

                <span>{{ $actor->name }}</span>
            </label>
        @empty
            <p class="field-help">
                Актёров пока нет.
                Сохраните фильм, откройте его редактирование
                и добавьте новых актёров.
            </p>
        @endforelse
    </div>
</div>

@if($movie)

@php
    $actorRows = old('actors_input', []);

    $actorRows = is_array($actorRows)
        ? array_values($actorRows)
        : [];

    if ($actorRows === []) {
        $actorRows = [
            ['name' => '', 'photo_url' => ''],
        ];
    }
@endphp

<div class="actors-editor">
    <h3>Добавить новых актёров</h3>

    <p class="field-help">
        Укажите имя и, при желании, URL фотографии.
        Для каждого актёра добавьте отдельную строку.
    </p>

    <div id="actor-rows">
        @foreach($actorRows as $index => $actor)
            @php
                // Поддержка ранее сохранённых актёров без фотографий.
                $actorName = is_array($actor)
                    ? ($actor['name'] ?? '')
                    : $actor;

                $actorPhoto = is_array($actor)
                    ? ($actor['photo_url'] ?? '')
                    : '';
            @endphp

            <div class="actor-editor-row" data-actor-row>
                <label>
                    Имя актёра

                    <input
                        type="text"
                        name="actors_input[{{ $index }}][name]"
                        data-actor-field="name"
                        value="{{ $actorName }}"
                        maxlength="120"
                        placeholder="Киану Ривз"
                    >
                </label>

                <label>
                    URL фотографии

                    <input
                        type="url"
                        name="actors_input[{{ $index }}][photo_url]"
                        data-actor-field="photo_url"
                        value="{{ $actorPhoto }}"
                        maxlength="2048"
                        placeholder="https://example.com/actor.jpg"
                    >
                </label>

                <button
                    type="button"
                    class="btn-small"
                    data-remove-actor
                >
                    Убрать
                </button>
            </div>
        @endforeach
    </div>

    <button
        type="button"
        id="add-actor"
        class="btn-small"
    >
        + Добавить актёра
    </button>
</div>

<script>
(() => {
    const rows = document.getElementById('actor-rows');
    const addButton = document.getElementById('add-actor');

    if (!rows || !addButton) {
        return;
    }

    const rowTemplate = rows
        .querySelector('[data-actor-row]')
        .cloneNode(true);

    let nextIndex = rows.querySelectorAll('[data-actor-row]').length;

    const updateButton = () => {
        addButton.disabled =
            rows.querySelectorAll('[data-actor-row]').length >= 100;
    };

    addButton.addEventListener('click', () => {
        if (rows.querySelectorAll('[data-actor-row]').length >= 100) {
            return;
        }

        const row = rowTemplate.cloneNode(true);

        row.querySelectorAll('[data-actor-field]').forEach(input => {
            const field = input.dataset.actorField;

            input.name = `actors_input[${nextIndex}][${field}]`;
            input.value = '';
            input.defaultValue = '';
        });

        nextIndex++;
        rows.appendChild(row);

        row.querySelector('[data-actor-field="name"]').focus();

        updateButton();
    });

    rows.addEventListener('click', event => {
        const button = event.target.closest('[data-remove-actor]');

        if (!button) {
            return;
        }

        const row = button.closest('[data-actor-row]');
        const count = rows.querySelectorAll('[data-actor-row]').length;

        if (count === 1) {
            row.querySelectorAll('input').forEach(input => {
                input.value = '';
            });
        } else {
            row.remove();
        }

        updateButton();
    });

    updateButton();
})();
</script>
@endif

        <p class="field-help">
            Описание будет под названием и жанром.
            Рейтинг появится после оценок пользователей.
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
        const value = input.value.trim();

        if (!/^https?:\/\//i.test(value) || !input.checkValidity()) {
            image.removeAttribute('src');
            showPlaceholder();
            return;
        }

        showPlaceholder();
        image.src = value;
    });

    if (!image.complete || !image.naturalWidth) {
        showPlaceholder();
    }
})();
</script>