<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::create([
            'name' => 'System Admin',
            'email' => 'ngotanloi2424@gmail.com',
            'password' => bcrypt('12345678'),
            'role' => 'admin',
        ]);

        User::create([
            'name' => 'Ngô Tấn Lợi',
            'email' => 'ngotanloi123321@gmail.com',
            'password' => bcrypt('12345678'),
            'role' => 'teacher',
        ]);
    }
}
