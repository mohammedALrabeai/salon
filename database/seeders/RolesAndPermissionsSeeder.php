<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Create all roles
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
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        // 2. Assign permissions to roles
        $allPermissions = Permission::pluck('name')->toArray();

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

        Role::findByName('barber', 'web')->syncPermissions(array_intersect($barberPermissions, $allPermissions));
        Role::findByName('manager', 'web')->syncPermissions(array_intersect($managerPermissions, $allPermissions));
        Role::findByName('owner', 'web')->syncPermissions(array_intersect($ownerPermissions, $allPermissions));
        Role::findByName('accountant', 'web')->syncPermissions(array_intersect($accountantPermissions, $allPermissions));
        Role::findByName('receptionist', 'web')->syncPermissions(array_intersect($barberPermissions, $allPermissions));

        // 3. Assign Spatie roles to all users based on their 'role' column
        $users = User::all();
        foreach ($users as $user) {
            if ($user->role && Role::where('name', $user->role)->where('guard_name', 'web')->exists()) {
                $user->syncRoles([$user->role]);
            }
        }

        $this->command->info('✅ Roles and permissions assigned successfully!');
        $this->command->info('   Barber: ' . count(array_intersect($barberPermissions, $allPermissions)) . ' permissions');
        $this->command->info('   Manager: ' . count(array_intersect($managerPermissions, $allPermissions)) . ' permissions');
        $this->command->info('   Owner: ' . count(array_intersect($ownerPermissions, $allPermissions)) . ' permissions');
        $this->command->info('   Accountant: ' . count(array_intersect($accountantPermissions, $allPermissions)) . ' permissions');
        $this->command->info('   Users synced: ' . $users->count());
    }
}
