@include('components.header')

<h2>Редактировать фильм: {{ $movie->title }}</h2>

@if ($errors->any())
    <div class="alert alert-error">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<form method="POST" action="/my-movies/{{ $movie->id }}/update" class="admin-form">
    @csrf
    
    <label>Название:</label>
    <input type="text" name="title" value="{{ old('title', $movie->title) }}" required>
    
    <label>Год (от 1888 до 2100):</label>
    <input type="number" name="year" min="1888" max="2100" value="{{ old('year', $movie->year) }}" required>
    
    <label>Категория:</label>
    <select name="category_id" required>
        @foreach($categories as $cat)
            <option value="{{ $cat->id }}" @if($movie->category_id == $cat->id) selected @endif>{{ $cat->name }}</option>
        @endforeach
    </select>
    
    <label>Описание:</label>
    <textarea name="description" required>{{ old('description', $movie->description) }}</textarea>
    
    <label>Ссылка на постер (URL):</label>
    <input type="text" name="poster_url" value="{{ old('poster_url', $movie->poster_url) }}" placeholder="https://example.com/image.jpg" style="width: 100%; padding: 10px; background: #2a2a2a; border: 1px solid #444; color: #fff; border-radius: 4px; margin-bottom: 15px;">
    
    <button type="submit" class="btn-submit">Сохранить изменения</button>
    <a href="/my-movies" style="margin-left: 15px; color: #aaa;">Отмена</a>
</form>

@include('components.footer')