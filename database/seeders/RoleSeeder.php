<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run()
    {   
        
        $roles = [
            'admin',
            'sales-representative', 
            'distributor',
            'finance',
            'supply',
            'back-office',
            'zonal-manager',
            'user' ,
            'logistics'
        ];

        foreach ($roles as $role) {
            Role::create(['name' => $role]);
        }
    }
}