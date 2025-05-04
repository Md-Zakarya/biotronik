<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Patient;
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

        // Truncate the users and patients table (if necessary for patients linked to users)
        Log::info('Truncating users table...');
        DB::table('users')->truncate();
        Log::info('Users table truncated successfully.');
        // Consider truncating patients table if sales users always need a fresh patient record
        // Log::info('Truncating patients table...');
        // DB::table('patients')->truncate();
        // Log::info('Patients table truncated successfully.');
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

            // Create corresponding Patient record for the Sales User
            Log::info('Creating Patient record for Sales User...');
            Patient::create([
                'user_id' => $sales->id,
                'Auth_name' => $sales->name,
                'name' => $sales->name,
                'email' => $sales->email,
                'password' => $sales->password,
                'is_service_engineer' => true
            ]);
            Log::info('Patient record created successfully for Sales User.', ['user_id' => $sales->id]);

            // Create token for Sales User
            Log::info('Creating token for Sales User...');
            $salesToken = $sales->createToken('SalesToken')->plainTextToken;
            Log::info('Token created successfully for Sales User.', ['user_id' => $sales->id]);
            echo "Sales Token: $salesToken\n";
        } catch (\Exception $e) {
            Log::error('Error creating Sales Representative User or Patient record.', ['error' => $e->getMessage()]);
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

        // Create Distributor User
        Log::info('Creating Distributor User...');
        try {
            $distributor = User::create([
                'name' => 'Distributor User',
                'email' => 'distributor@espandan.com',
                'password' => Hash::make('password123'),
                'phonenumber' => '5678901234'
            ]);
            Log::info('Distributor User created successfully.', ['user_id' => $distributor->id]);

            // Assign 'distributor' role to the Distributor User
            Log::info('Assigning "distributor" role to Distributor User...');
            $distributor->assignRole('distributor');
            Log::info('Role "distributor" assigned successfully to Distributor User.', ['user_id' => $distributor->id]);


            // Create token for Distributor User
            Log::info('Creating token for Distributor User...');
            $distributorToken = $distributor->createToken('DistributorToken')->plainTextToken;
            Log::info('Token created successfully for Distributor User.', ['user_id' => $distributor->id]);
            echo "Distributor Token: $distributorToken\n";
        } catch (\Exception $e) {
            Log::error('Error creating Distributor User.', ['error' => $e->getMessage()]);
            throw $e;
        }

        // Log the completion of the seeding process
        Log::info('UserSeeder completed successfully.');
    }
}