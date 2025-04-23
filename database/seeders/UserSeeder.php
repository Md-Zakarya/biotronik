<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Log the start of the seeding process
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Log::info('Starting UserSeeder...');

        // Truncate the users table
        Log::info('Truncating users table...');
        DB::table('users')->truncate();
        Log::info('Users table truncated successfully.');
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        // Create Admin User
        Log::info('Creating Admin User...');
        try {
            $admin = User::create([
                'name' => 'Admin User',
                'email' => 'admin@espandan.com',
                'password' => Hash::make('password123'),
                'phonenumber' => '1234567890'
            ]);
            Log::info('Admin User created successfully.', ['user_id' => $admin->id]);

            // Assign 'admin' role to the Admin User
            Log::info('Assigning "admin" role to Admin User...');
            $admin->assignRole('admin');
            Log::info('Role "admin" assigned successfully to Admin User.', ['user_id' => $admin->id]);

            // Create token for Admin User
            Log::info('Creating token for Admin User...');
            $adminToken = $admin->createToken('AdminToken')->plainTextToken;
            Log::info('Token created successfully for Admin User.', ['user_id' => $admin->id]);
            echo "Admin Token: $adminToken\n";
        } catch (\Exception $e) {
            Log::error('Error creating Admin User.', ['error' => $e->getMessage()]);
            throw $e;
        }

        // Create Sales Representative User
        Log::info('Creating Sales Representative User...');
        try {
            $sales = User::create([
                'name' => 'Sales User',
                'email' => 'sales@espandan.com',
                'password' => Hash::make('password123'),
                'phonenumber' => '2345678901'
            ]);
            Log::info('Sales Representative User created successfully.', ['user_id' => $sales->id]);

            // Assign 'sales-representative' role to the Sales User
            Log::info('Assigning "sales-representative" role to Sales User...');
            $sales->assignRole('sales-representative');
            Log::info('Role "sales-representative" assigned successfully to Sales User.', ['user_id' => $sales->id]);

            // Create token for Sales User
            Log::info('Creating token for Sales User...');
            $salesToken = $sales->createToken('SalesToken')->plainTextToken;
            Log::info('Token created successfully for Sales User.', ['user_id' => $sales->id]);
            echo "Sales Token: $salesToken\n";
        } catch (\Exception $e) {
            Log::error('Error creating Sales Representative User.', ['error' => $e->getMessage()]);
            throw $e;
        }

        // Create Finance User
        Log::info('Creating Finance User...');
        try {
            $finance = User::create([
                'name' => 'Finance User',
                'email' => 'finance@espandan.com',
                'password' => Hash::make('password123'),
                'phonenumber' => '3456789012'
            ]);
            Log::info('Finance User created successfully.', ['user_id' => $finance->id]);

            // Assign 'finance' role to the Finance User
            Log::info('Assigning "finance" role to Finance User...');
            $finance->assignRole('finance');
            Log::info('Role "finance" assigned successfully to Finance User.', ['user_id' => $finance->id]);

            // Create token for Finance User
            Log::info('Creating token for Finance User...');
            $financeToken = $finance->createToken('FinanceToken')->plainTextToken;
            Log::info('Token created successfully for Finance User.', ['user_id' => $finance->id]);
            echo "Finance Token: $financeToken\n";
        } catch (\Exception $e) {
            Log::error('Error creating Finance User.', ['error' => $e->getMessage()]);
            throw $e;
        }

        // Create Logistics User
        Log::info('Creating Logistics User...');
        try {
            $logistics = User::create([
                'name' => 'Logistics Manager',
                'email' => 'logistics@espandan.com',
                'password' => Hash::make('password123'),
                'phonenumber' => '4567890123'
            ]);
            Log::info('Logistics User created successfully.', ['user_id' => $logistics->id]);

            // Assign 'logistics' role to the Logistics User
            Log::info('Assigning "logistics" role to Logistics User...');
            $logistics->assignRole('logistics');
            Log::info('Role "logistics" assigned successfully to Logistics User.', ['user_id' => $logistics->id]);

            // Create token for Logistics User
            Log::info('Creating token for Logistics User...');
            $logisticsToken = $logistics->createToken('LogisticsToken')->plainTextToken;
            Log::info('Token created successfully for Logistics User.', ['user_id' => $logistics->id]);
            echo "Logistics Token: $logisticsToken\n";
        } catch (\Exception $e) {
            Log::error('Error creating Logistics User.', ['error' => $e->getMessage()]);
            throw $e;
        }

        // Log the completion of the seeding process
        Log::info('UserSeeder completed successfully.');
    }
}