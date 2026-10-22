@include('components.header')

<h2>Регистрация</h2>
<form method="POST" action="/register" class="admin-form">
    @csrf
    
    @if ($errors->any())
        <div class="alert alert-error">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <label>Логин:</label>
    <input type="text" name="login" value="{{ old('login') }}" required>
    
    <label>Пароль:</label>
    <input type="password" name="password" required>
    
    <label>Подтверждение пароля:</label>
    <input type="password" name="password_confirmation" required>

    <label>Кто вы?</label>
    <select name="role" required>
        <option value="Пользователь">Обычный пользователь (читать, ставить оценки)</option>
        <option value="Модератор">Автор / Модератор (добавлять фильмы)</option>
    </select>
    
    <button type="submit" class="btn-submit">Зарегистрироваться</button>
</form>

@include('components.footer')