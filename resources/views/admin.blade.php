@include('components.header')

<h2>Админ-панель</h2>

@if(session('ok'))
    <div class="alert alert-success">{{ session('ok') }}</div>
@endif

<h3>Пользователи</h3>
<table class="table">
    <tr>
        <th>Логин</th>
        <th>Роль</th>
        <th>Очки</th>
        <th>Бан</th>
        <th>Действия</th>
    </tr>
    @foreach($users as $u)
        <tr>
            <td>{{ $u->login }}</td>
            <td>{{ $u->role->name }}</td>
            <td>{{ $u->score }}</td>
            <td>{{ $u->is_banned ? 'Забанен' : 'Активен' }}</td>
            <td>
                @if($u->role->name !== 'Администратор')
                    <form method="POST" action="/admin/user/{{ $u->id }}/ban" style="display:inline">
                        @csrf
                        <button type="submit" class="btn-small">{{ $u->is_banned ? 'Разбанить' : 'Забанить' }}</button>
                    </form>
                @endif
                
                @if($u->role->name !== 'Администратор')
                    <form method="POST" action="/admin/user/{{ $u->id }}/score" style="display:inline">
                        @csrf
                        <input type="number" name="amount" placeholder="+/-" style="width: 50px;">
                        <button type="submit" class="btn-small">Очки</button>
                    </form>
                @endif


                <form method="POST" action="/admin/user/{{ $u->id }}/role" style="display:inline">
                    @csrf
                    <select name="role_id">
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" @if($u->role_id == $role->id) selected @endif>{{ $role->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn-small">Сменить роль</button>
                </form>
            </td>
        </tr>
    @endforeach
</table>

@include('components.footer')