@include('components.header')

<h2>Добавить фильм</h2>

@if ($errors->any())
    <div class="alert alert-error">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<form method="POST" action="/moderator" class="admin-form">
    @csrf
    <label>Название:</label>
    <input type="text" name="title" value="{{ old('title') }}" required>
    
    <label>Год (от 1888 до 2100):</label>
    <input type="number" name="year" min="1888" max="2100" value="{{ old('year') }}" required>
    
    <label>Категория:</label>
    <select name="category_id" required>
        @foreach($categories as $cat)
            <option value="{{ $cat->id }}" @if(old('category_id') == $cat->id) selected @endif>{{ $cat->name }}</option>
        @endforeach
    </select>
    
    <label>Описание:</label>
    <textarea name="description" required>{{ old('description') }}</textarea>
    
    <label>Ссылка на постер (URL):</label>
    <input type="text" name="poster_url" value="{{ old('poster_url') }}" placeholder="https://example.com/image.jpg" style="width: 100%; padding: 10px; background: #2a2a2a; border: 1px solid #444; color: #fff; border-radius: 4px; margin-bottom: 15px;">
    
    <button type="submit" class="btn-submit">Добавить</button>
</form>

@include('components.footer')