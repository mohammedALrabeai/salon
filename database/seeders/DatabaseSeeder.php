<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $roles = [
            'super_admin',
            'owner',
            'manager',
            'accountant',
            'barber',
            'doc_supervisor',
            'receptionist',
            'auditor',
            'other',
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate([
                'name' => $role,
                'guard_name' => 'web',
            ]);
        }

        User::updateOrCreate(
            ['phone' => '0500000000'],
            [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password_hash' => Hash::make('12345678'),
                'role' => 'owner',
                'status' => 'active',
            ]
        );

        $superAdmin = User::updateOrCreate(
            ['phone' => '0571718153'],
            [
                'name' => 'Super Admin',
                'email' => 'admin@admin.com',
                'password_hash' => Hash::make('12345678'),
                'role' => 'super_admin',
                'status' => 'active',
            ]
        );

        $superAdmin->syncRoles(['super_admin']);

        $this->call(DemoDataSeeder::class);
    }
}
