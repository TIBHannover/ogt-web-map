<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Admin',
            'email' => 'admin@gestapo-terror-orte.de',
            'password' => Hash::make('admin123456'),
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        $this->command->info('Admin user created with email: admin@gestapo-terror-orte.de and password: admin123456');
    }
}
