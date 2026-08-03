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
        User::firstOrCreate(
            ['email' => 'it@example.com'],
            [
                'name' => 'IT Admin',
                'password' => Hash::make('password'),
                'branch' => 'ALL',
                'role' => 'it_admin',
                'menu_permissions' => ['dashboard', 'total-stock', 'rental-pairs', 'in-stock', 'active-rentals', 'in-service', 'lor', 'crm'],
            ]
        );
    }
}
