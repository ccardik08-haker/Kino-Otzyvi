<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Category;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Role::insert([
            ['name' => 'Администратор'],
            ['name' => 'Модератор'],
            ['name' => 'Пользователь'],
        ]);

        Category::insert([
            ['name' => 'Боевик'],
            ['name' => 'Комедия'],
            ['name' => 'Ужасы'],
            ['name' => 'Фантастика'],
        ]);
    }
}