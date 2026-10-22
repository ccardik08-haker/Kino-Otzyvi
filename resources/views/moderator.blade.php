@include('components.header')

<h2>Добавить фильм</h2>

<form method="POST" action="/moderator" class="admin-form">
    @csrf
    <label>Название:</label>
    <input type="text" name="title" required>
    
    <label>Год:</label>
    <input type="number" name="year" required>
    
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