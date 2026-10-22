@include('components.header')

<h2>Вход</h2>
@if(session('ok')) <div class="alert alert-success">{{ session('ok') }}</div> @endif
@if($errors->any()) <div class="alert alert-error">{{ $errors->first() }}</div> @endif

<form method="POST" action="/login" class="admin-form">
    @csrf
    <label>Логин:</label>
    <input type="text" name="login" required>
    
    <label>Пароль:</label>
    <input type="password" name="password" required>
    
    <button type="submit" class="btn-submit">Войти</button>
</form>

@include('components.footer')