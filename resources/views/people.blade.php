@include('components.header')

@php
    $sectionTitle = $type === 'actors'
        ? 'Актёры'
        : 'Режиссёры';

    $formTitle = $person
        ? 'Редактировать запись'
        : ($type === 'actors'
            ? 'Добавить актёра'
            : 'Добавить режиссёра');
@endphp

<h2>Актёры и режиссёры</h2>

<div class="people-tabs">
    <a
        href="{{ route('people.index', ['type' => 'actors']) }}"
        class="{{ $type === 'actors' ? 'active' : '' }}"
    >
        Актёры
    </a>

    <a
        href="{{ route('people.index', ['type' => 'directors']) }}"
        class="{{ $type === 'directors' ? 'active' : '' }}"
    >
        Режиссёры
    </a>
</div>

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

<div class="people-page-layout">
    <section>
        <h3>{{ $formTitle }}</h3>

        <form
            method="POST"
            action="{{ $person
                ? route('people.update', [
                    'type' => $type,
                    'id' => $person->id,
                ])
                : route('people.store', ['type' => $type]) }}"
            class="admin-form"
        >
            @csrf

            @if($person)
                @method('PUT')
            @endif

            <label for="person-name">Имя</label>

            <input
                id="person-name"
                type="text"
                name="name"
                maxlength="120"
                value="{{ old('name', $person?->name) }}"
                required
            >

            <label for="person-photo">URL фотографии</label>

            <input
                id="person-photo"
                type="url"
                name="photo_url"
                maxlength="2048"
                value="{{ old('photo_url', $person?->photo_url) }}"
                placeholder="https://example.com/photo.jpg"
            >

            <p class="field-help">
                Фотография необязательна.
                Чтобы удалить фотографию, очистите поле.
            </p>

            <button type="submit" class="btn-submit">
                {{ $person ? 'Сохранить изменения' : 'Добавить' }}
            </button>

            @if($person)
                <a
                    href="{{ route('people.index', ['type' => $type]) }}"
                    class="people-cancel"
                >
                    Отмена
                </a>
            @endif
        </form>
    </section>

    <section>
        <h3>{{ $sectionTitle }}</h3>

        <div class="people-list">
            @forelse($people as $item)
                <article class="people-list-item">
                    @if($item->photo_url)
                        <img
                            src="{{ $item->photo_url }}"
                            alt="{{ $item->name }}"
                            class="people-avatar"
                            loading="lazy"
                        >
                    @else
                        <span class="people-avatar people-avatar-empty">
                            {{ mb_substr($item->name, 0, 1) }}
                        </span>
                    @endif

                    <strong>{{ $item->name }}</strong>

                    <a
                        href="{{ route('people.edit', [
                            'type' => $type,
                            'id' => $item->id,
                        ]) }}"
                        class="btn-small"
                    >
                        Редактировать
                    </a>
                </article>
            @empty
                <p>Записей пока нет.</p>
            @endforelse
        </div>

        <div class="people-pagination">
            @if($people->hasPages())
                @if($people->previousPageUrl())
                    <a href="{{ $people->previousPageUrl() }}">
                        ← Назад
                    </a>
                @endif

                <span>
                    Страница {{ $people->currentPage() }}
                    из {{ $people->lastPage() }}
                </span>

                @if($people->hasMorePages())
                    <a href="{{ $people->nextPageUrl() }}">
                        Вперёд →
                    </a>
                @endif
            @endif
        </div>
    </section>
</div>

@include('components.footer')