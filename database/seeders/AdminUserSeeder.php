<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/** First admin account, from ADMIN_EMAIL / ADMIN_PASSWORD in .env (skipped when no password is set). */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('ADMIN_PASSWORD');
        if (! $password) {
            $this->command?->warn('ADMIN_PASSWORD not set: no admin user created.');

            return;
        }

        $user = User::firstOrNew(['email' => env('ADMIN_EMAIL', 'admin@volterra.test')]);
        $user->forceFill([
            'name' => env('ADMIN_NAME', 'VOLTERRA Admin'),
            'password' => $password,
            'role' => 'admin',
        ])->save();
    }
}
