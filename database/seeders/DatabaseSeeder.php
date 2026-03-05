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

        // Assign permissions to roles
        $barberPermissions = [
            'ViewAny:DailyEntry',
            'View:DailyEntry',
            'Create:DailyEntry',
            'ViewAny:AdvanceRequest',
            'View:AdvanceRequest',
            'Create:AdvanceRequest',
            'ViewAny:LedgerEntry',
            'View:LedgerEntry',
            'ViewAny:Notification',
            'View:Notification',
            'Update:Notification',
        ];

        $managerPermissions = [
            'ViewAny:DailyEntry',
            'View:DailyEntry',
            'Create:DailyEntry',
            'Update:DailyEntry',
            'Delete:DailyEntry',
            'ViewAny:DayClosure',
            'View:DayClosure',
            'Create:DayClosure',
            'ViewAny:AdvanceRequest',
            'View:AdvanceRequest',
            'Create:AdvanceRequest',
            'Update:AdvanceRequest',
            'ViewAny:LedgerEntry',
            'View:LedgerEntry',
            'Create:LedgerEntry',
            'ViewAny:User',
            'View:User',
            'Create:User',
            'Update:User',
            'ViewAny:Branch',
            'View:Branch',
            'ViewAny:Document',
            'View:Document',
            'Create:Document',
            'Update:Document',
            'ViewAny:Notification',
            'View:Notification',
            'Create:Notification',
            'Update:Notification',
        ];

        $ownerPermissions = array_merge($managerPermissions, [
            'Delete:User',
            'Update:Branch',
            'Create:Branch',
            'Delete:DayClosure',
            'Delete:AdvanceRequest',
            'ViewAny:Activity',
            'View:Activity',
            'ViewAny:Role',
            'View:Role',
        ]);

        // Ensure permissions exist before assigning
        $allPermissions = \Spatie\Permission\Models\Permission::pluck('name')->toArray();

        $barberRole = Role::findByName('barber', 'web');
        $barberRole->syncPermissions(array_intersect($barberPermissions, $allPermissions));

        $managerRole = Role::findByName('manager', 'web');
        $managerRole->syncPermissions(array_intersect($managerPermissions, $allPermissions));

        $ownerRole = Role::findByName('owner', 'web');
        $ownerRole->syncPermissions(array_intersect($ownerPermissions, $allPermissions));

        // Receptionist gets similar to barber
        $receptionistRole = Role::findByName('receptionist', 'web');
        $receptionistRole->syncPermissions(array_intersect($barberPermissions, $allPermissions));

        // Accountant gets financial permissions
        $accountantPermissions = [
            'ViewAny:DailyEntry',
            'View:DailyEntry',
            'ViewAny:DayClosure',
            'View:DayClosure',
            'Create:DayClosure',
            'ViewAny:LedgerEntry',
            'View:LedgerEntry',
            'Create:LedgerEntry',
            'ViewAny:AdvanceRequest',
            'View:AdvanceRequest',
            'Update:AdvanceRequest',
            'ViewAny:User',
            'View:User',
            'ViewAny:Branch',
            'View:Branch',
            'ViewAny:Notification',
            'View:Notification',
            'Update:Notification',
        ];
        $accountantRole = Role::findByName('accountant', 'web');
        $accountantRole->syncPermissions(array_intersect($accountantPermissions, $allPermissions));

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
