<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Super admin (SaaS owner) seeder.
 *
 *   php artisan db:seed --class=AdminSeeder
 *
 * Account comes from config/admin.php → env:
 *   ADMIN_EMAIL    (default owner@aivisibility.app)
 *   ADMIN_PASSWORD (default aivisibility-demo — CHANGE IN PRODUCTION)
 *   ADMIN_NAME     (default "SaaS Owner")
 *
 * Idempotent: updates the row (including password) every run, so re-seeding
 * after changing .env always refreshes the credentials.
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) config('admin.email');
        $password = (string) config('admin.password');

        $admin = Admin::updateOrCreate(
            ['email' => $email],
            [
                'name' => (string) config('admin.name'),
                'role' => 'super_admin',
                'password' => Hash::make($password),
            ]
        );

        $this->command?->info('Super admin ready: '.$admin->email);

        // Never echo the password hint outside a seeded/local context.
        if (config('admin.hide_demo_hint') !== true && app()->environment() !== 'production') {
            $this->command?->warn('  → password used: '.$password);
        } else {
            $this->command?->warn('  → password is private (hidden — set ADMIN_PASSWORD in .env).');
        }
    }
}
