<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
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
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '0500000000',
            'role' => 'owner',
        ]);

        User::updateOrCreate(
            ['phone' => '0571718153'],
            [
                'name' => 'Super Admin',
                'email' => 'admin@admin.com',
                'password_hash' => Hash::make('12345678'),
                'role' => 'super_admin',
                'status' => 'active',
            ]
        );
    }
}
