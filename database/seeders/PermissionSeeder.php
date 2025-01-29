<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder 
{
    public function run()
    {
        $permissions = [
            // Patient Management
            'create-patient',
            'view-patient',
            'edit-patient',
            'delete-patient',
            
            // User Management
            'manage-users',
            'view-users',
            
            // Role Management
            'manage-roles',
            'view-roles',
            
            // Finance Permissions
            'view-finance',
            'manage-finance',
            
            // Supply Permissions
            'view-inventory',
            'manage-inventory'
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }
    }
}