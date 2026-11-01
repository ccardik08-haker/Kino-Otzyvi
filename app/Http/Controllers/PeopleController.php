<?php

namespace App\Http\Controllers;

use App\Services\PersonCatalog;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PeopleController extends Controller
{
    public function __construct(
        private PersonCatalog $catalog
    ) {
    }

    public function index(string $type): View
    {
        return $this->page($type, null);
    }

    public function edit(string $type, int $id): View
    {
        $modelClass = PersonCatalog::modelClass($type);
        $person = $modelClass::findOrFail($id);

        return $this->page($type, $person);
    }

    public function store(
        Request $request,
        string $type
    ): RedirectResponse {
        $data = $this->validatePerson($request);

        $this->catalog->save(
            $type,
            null,
            $data['name'],
            $data['photo_url'] ?? null
        );

        return redirect()
            ->route('people.index', ['type' => $type])
            ->with('ok', 'Запись добавлена.');
    }

    public function update(
        Request $request,
        string $type,
        int $id
    ): RedirectResponse {
        $modelClass = PersonCatalog::modelClass($type);
        $person = $modelClass::findOrFail($id);

        $data = $this->validatePerson($request);

        $this->catalog->save(
            $type,
            $person,
            $data['name'],
            $data['photo_url'] ?? null
        );

        return redirect()
            ->route('people.index', ['type' => $type])
            ->with('ok', 'Изменения сохранены.');
    }

    private function page(string $type, ?Model $person): View
    {
        $modelClass = PersonCatalog::modelClass($type);

        $people = $modelClass::orderBy('name')->paginate(12);

        return view(
            'people',
            compact('type', 'person', 'people')
        );
    }

    private function validatePerson(Request $request): array
    {
        return $request->validate(
            [
                'name' => 'required|string|max:120',
                'photo_url' => 'nullable|url:http,https|max:2048',
            ],
            [
                'name.required' => 'Введите имя.',
                'name.max' => 'Имя должно быть не длиннее 120 символов.',
                'photo_url.url' => 'Введите корректный URL фотографии.',
            ]
        );
    }
}