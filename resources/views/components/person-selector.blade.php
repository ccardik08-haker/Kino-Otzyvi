@php
    $marker = $field . '_present';

    $selectedIds = session()->hasOldInput($marker)
        ? old($field, [])
        : $selected;

    $selectedIds = array_map(
        'strval',
        is_array($selectedIds) ? $selectedIds : []
    );
@endphp

<input type="hidden" name="{{ $marker }}" value="1">

<section class="person-selector">
    <h3>{{ $title }}</h3>

    <div class="person-selector-list">
        @forelse($options as $person)
            <label class="person-selector-option">
                <input
                    type="checkbox"
                    name="{{ $field }}[]"
                    value="{{ $person->id }}"
                    @checked(
                        in_array(
                            (string) $person->id,
                            $selectedIds,
                            true
                        )
                    )
                >

                @if($person->photo_url)
                    <img
                        src="{{ $person->photo_url }}"
                        alt=""
                        loading="lazy"
                    >
                @endif

                <span>{{ $person->name }}</span>
            </label>
        @empty
            <p class="field-help">
                Записей пока нет. Добавьте их
                в разделе «Актёры и режиссёры».
            </p>
        @endforelse
    </div>
</section>