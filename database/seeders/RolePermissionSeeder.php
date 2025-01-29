<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        // Admin Role
        $adminRole = Role::findByName('admin');
        $adminRole->givePermissionTo(Permission::all());

        // Sales Representative Role
        $salesRole = Role::findByName('sales-representative');
        $salesRole->givePermissionTo([
            'create-patient',
            'view-patient',
            'edit-patient'
        ]);

        // Distributor Role
        $distributorRole = Role::findByName('distributor');
        $distributorRole->givePermissionTo([
            'view-patient'
        ]);

        // Finance Role
        $financeRole = Role::findByName('finance');
        $financeRole->givePermissionTo([
            'view-finance',
            'manage-finance'
        ]);

        // Supply Role
        $supplyRole = Role::findByName('supply');
        $supplyRole->givePermissionTo([
            'view-inventory',
            'manage-inventory'
        ]);
    }
}