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
        \App\Models\User::create([
            'name' => 'System Admin',
            'email' => 'ngotanloi2424@gmail.com',
            'password' => bcrypt('Icandoit112'),
            'role' => 'admin',
        ]);

        \App\Models\User::create([
            'name' => 'Ngô Tấn Lợi',
            'email' => 'ngotanloi123321@gmail.com',
            'password' => bcrypt('Icandoit112'),
            'role' => 'teacher',
        ]);
    }
}
