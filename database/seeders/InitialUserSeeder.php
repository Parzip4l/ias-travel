<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class InitialUserSeeder extends Seeder
{
    /**
     * Seed initial login accounts for local setup.
     */
    public function run(): void
    {
        $this->seedUser(
            'superadmin@ias-travel.test',
            'Super Admin',
            'admin',
            'superadmin123'
        );

        $this->seedUser(
            'user@ias-travel.test',
            'User Demo',
            'user',
            'user12345'
        );
    }

    private function seedUser(string $email, string $name, string $role, string $password): void
    {
        $user = User::firstOrNew(['email' => $email]);
        $user->forceFill([
            'name' => $name,
            'role' => $role,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
        ])->save();
    }
}
