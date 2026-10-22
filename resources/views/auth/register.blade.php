@include('components.header')

<h2>Регистрация</h2>
<form method="POST" action="/register" class="admin-form">
    @csrf
    <label>Логин:</label>
    <input type="text" name="login" required>
    
    <label>Пароль:</label>
    <input type="password" name="password" required>
    
    <label>Подтверждение пароля:</label>
    <input type="password" name="password_confirmation" required>
    
    <button type="submit" class="btn-submit">Зарегистрироваться</button>
</form>

@include('components.footer')