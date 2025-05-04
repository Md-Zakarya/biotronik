<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\IpgModel;
use App\Models\IpgSerial;
use App\Models\User; // Assuming you have a User model for distributors
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str; // For generating unique serials

class IpgInventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        Log::info('Starting IpgInventorySeeder...');

        // Number of inventory items to create
        $inventoryCount = 20; 

        // Fetch all existing IPG model numbers
        $modelNumbers = IpgModel::pluck('model_number')->toArray();

        // Fetch users with the 'distributor' role (optional)
        // Ensure RoleSeeder and UserSeeder run before this
        $distributorIds = User::whereHas('roles', function ($query) {
            $query->where('name', 'distributor');
        })->pluck('id')->toArray();

        if (empty($modelNumbers)) {
            Log::warning('IpgInventorySeeder: No IPG models found. Skipping inventory seeding.');
            $this->command->warn('No IPG models found in the database. Skipping IpgInventorySeeder.');
            return;
        }

        $createdCount = 0;
        $maxAttempts = $inventoryCount * 2; // Avoid infinite loops if serial generation clashes
        $attempts = 0;
        $generatedSerials = [];

        while ($createdCount < $inventoryCount && $attempts < $maxAttempts) {
            $attempts++;
            // Generate a unique serial number (adjust format as needed)
            $serialNumber = 'SER' . strtoupper(Str::random(10));

            // Ensure uniqueness within this seeding run
            if (in_array($serialNumber, $generatedSerials)) {
                continue; 
            }
            
            // Check if serial already exists in DB (optional but recommended)
            if (IpgSerial::where('ipg_serial_number', $serialNumber)->exists()) {
                continue;
            }

            // Select a random model number
            $randomModelNumber = $modelNumbers[array_rand($modelNumbers)];

            // Select a random distributor ID (optional)
            $randomDistributorId = !empty($distributorIds) ? $distributorIds[array_rand($distributorIds)] : null;

            try {
                IpgSerial::create([
                    'ipg_serial_number' => $serialNumber,
                    'model_number' => $randomModelNumber,
                    'distributor_id' => $randomDistributorId,
                    'patient_id' => null, // Inventory items are not assigned to patients
                    'date_added' => now(),
                    'is_implanted' => false, // Inventory items are not implanted
                ]);
                $generatedSerials[] = $serialNumber;
                $createdCount++;
            } catch (\Exception $e) {
                Log::error('IpgInventorySeeder: Failed to create serial.', [
                    'serial_number' => $serialNumber, 
                    'model_number' => $randomModelNumber,
                    'error' => $e->getMessage()
                ]);
            }
        }

        if ($createdCount > 0) {
            Log::info("IpgInventorySeeder completed successfully. Created {$createdCount} inventory items.");
            $this->command->info("{$createdCount} IPG inventory items successfully seeded!");
        } else {
             Log::warning('IpgInventorySeeder: No inventory items were created.');
             $this->command->warn('IpgInventorySeeder: No inventory items were created.');
        }
         if ($attempts >= $maxAttempts && $createdCount < $inventoryCount) {
             Log::warning("IpgInventorySeeder: Hit max attempts ({$maxAttempts}) before creating desired count ({$inventoryCount}). Only created {$createdCount}.");
             $this->command->warn("IpgInventorySeeder: Hit max attempts, only created {$createdCount}/{$inventoryCount} items.");
        }
    }
}