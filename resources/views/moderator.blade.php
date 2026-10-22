@include('components.header')

<h2>Добавить фильм</h2>

<form method="POST" action="/moderator" class="admin-form">
    @csrf
    <label>Название:</label>
    <input type="text" name="title" required>
    
    <label>Год (от 1888 до 2100):</label>
    <input type="number" name="year" min="1888" max="2100" required placeholder="Например: 2024">
    <small style="color: #888; display: block; margin-top: -5px; margin-bottom: 15px;">
        * Фильмы могли быть сняты не раньше 1888 года и не позже 2100.
    </small>
    
    <label>Категория:</label>
    <select name="category_id" required>
        @foreach($categories as $cat)
            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
        @endforeach
    </select>
    
    <label>Описание:</label>
    <textarea name="description" required></textarea>
    
    <label>Ссылка на постер (необязательно):</label>
    <input type="text" name="poster_url">
    
    <button type="submit" class="btn-submit">Добавить</button>
</form>

@include('components.footer')