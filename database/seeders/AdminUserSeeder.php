<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    /**
     * Ensure a default admin user exists.
     * Safe to run on every Railway restart/redeploy:
     *  - Creates the user only if the email is not already in the database.
     *  - Never modifies or removes existing users.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@admin.com'],          // search key (unique column)
            [
                'username' => 'admin',
                'password' => Hash::make('123456'),
                'role'     => 'admin',
                'status'   => 'active',
            ]
        );
    }
}
