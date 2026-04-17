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
        'email' => 'admin@gmail.com',
        'password' => bcrypt('123456'),
        'role' => 'admin'
    ]);

    \App\Models\User::create([
        'name' => 'Teacher User',
        'email' => 'teacher@gmail.com',
        'password' => bcrypt('123456'),
        'role' => 'teacher'
    ]);

    \App\Models\User::create([
        'name' => 'Student User',
        'email' => 'student@gmail.com',
        'password' => bcrypt('123456'),
        'role' => 'student'
    ]);
}
}
