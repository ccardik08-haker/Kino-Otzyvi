@include('components.header')

<h2>Рейтинг пользователей</h2>

<table class="table">
    <thead>
        <tr>
            <th>#</th>
            <th>Пользователь</th>
            <th>Роль</th>
            <th>Очки</th>
        </tr>
    </thead>
    <tbody>
        @foreach($users as $index => $u)
            <tr>
                <td>
                    @if($index == 0) 🥇
                    @elseif($index == 1) 🥈
                    @elseif($index == 2) 🥉
                    @else {{ $index + 1 }}
                    @endif
                </td>
                <td>{{ $u->login }}</td>
                <td>{{ $u->role->name }}</td>
                <td><strong>{{ $u->score }}</strong></td>
            </tr>
        @endforeach
    </tbody>
</table>

@include('components.footer')