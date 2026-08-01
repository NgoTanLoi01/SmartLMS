<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $accounts = [
            [
                'name' => 'System Admin',
                'email' => 'ngotanloi2424@gmail.com',
                'role' => User::ROLE_ADMIN,
                'password' => env('SMARTLMS_SEED_ADMIN_PASSWORD'),
            ],
            [
                'name' => 'Ngô Tấn Lợi',
                'email' => 'ngotanloi123321@gmail.com',
                'role' => User::ROLE_TEACHER,
                'password' => env('SMARTLMS_SEED_TEACHER_PASSWORD'),
            ],
        ];

        foreach ($accounts as $account) {
            $password = $account['password'];
            unset($account['password']);

            if (blank($password)) {
                $this->command?->warn("Bỏ qua {$account['role']} {$account['email']}: chưa cấu hình mật khẩu seed.");

                continue;
            }

            User::updateOrCreate(
                ['email' => $account['email']],
                [...$account, 'password' => Hash::make($password)]
            );
        }
    }
}
