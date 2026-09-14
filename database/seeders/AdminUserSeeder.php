<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed bootstrap accounts without shipping public default passwords.
     */
    public function run(): void
    {
        $admin = config('bootstrap.admin');

        if (! empty($admin['email']) && ! empty($admin['password'])) {
            User::updateOrCreate(
                ['email' => $admin['email']],
                [
                    'name' => $admin['name'] ?: 'Platform Administrator',
                    'password' => Hash::make($admin['password']),
                    'is_admin' => true,
                    'email_verified_at' => now(),
                ]
            );
        } else {
            $this->command?->warn('Admin bootstrap skipped. Set BOOTSTRAP_ADMIN_EMAIL and BOOTSTRAP_ADMIN_PASSWORD before seeding.');
        }

        $demo = config('bootstrap.demo_user');

        if (! empty($demo['enabled']) && ! empty($demo['email']) && ! empty($demo['password'])) {
            User::updateOrCreate(
                ['email' => $demo['email']],
                [
                    'name' => $demo['name'] ?: 'Demo User',
                    'password' => Hash::make($demo['password']),
                    'is_admin' => false,
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
