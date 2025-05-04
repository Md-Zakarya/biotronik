<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LeadModel;
use App\Models\LeadSerial;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LeadInventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        Log::info('Starting LeadInventorySeeder...');

        // Number of inventory items to create
        $inventoryCount = 30;

        // Fetch all existing Lead model numbers
        // Ensure LeadModelSeeder runs before this
        $modelNumbers = LeadModel::pluck('model_number')->toArray();

        // Fetch users with the 'distributor' role
        // Ensure RoleSeeder and UserSeeder run before this
        $distributorIds = User::whereHas('roles', function ($query) {
            $query->where('name', 'distributor');
        })->pluck('id')->toArray();

        if (empty($modelNumbers)) {
            Log::warning('LeadInventorySeeder: No Lead models found. Skipping inventory seeding.');
            $this->command->warn('No Lead models found in the database. Skipping LeadInventorySeeder.');
            return;
        }

        $createdCount = 0;
        $maxAttempts = $inventoryCount * 2; // Avoid infinite loops
        $attempts = 0;
        $generatedSerials = [];

        while ($createdCount < $inventoryCount && $attempts < $maxAttempts) {
            $attempts++;
            // Generate a unique serial number
            $serialNumber = 'LEAD' . strtoupper(Str::random(12));

            // Ensure uniqueness within this seeding run
            if (in_array($serialNumber, $generatedSerials)) {
                continue;
            }

            // Check if serial already exists in DB
            if (LeadSerial::where('serial_number', $serialNumber)->exists()) {
                continue;
            }

            // Select a random model number
            $randomModelNumber = $modelNumbers[array_rand($modelNumbers)];

            // Select a random distributor ID
            $randomDistributorId = !empty($distributorIds) ? $distributorIds[array_rand($distributorIds)] : null;

            try {
                // Create entry with column names matching the migration
                LeadSerial::create([
                    'serial_number' => $serialNumber,
                    'lead_model_number' => $randomModelNumber, // Corrected column name
                    'distributor_id' => $randomDistributorId,
                    'patient_id' => null,
                    'is_implanted' => false,
                ]);
                $generatedSerials[] = $serialNumber;
                $createdCount++;
            } catch (\Exception $e) {
                Log::error('LeadInventorySeeder: Failed to create serial.', [
                    'serial_number' => $serialNumber,
                    'lead_model_number' => $randomModelNumber, // Corrected column name in log
                    'error' => $e->getMessage()
                ]);
            }
        }

        if ($createdCount > 0) {
            Log::info("LeadInventorySeeder completed successfully. Created {$createdCount} inventory items.");
            $this->command->info("{$createdCount} Lead inventory items successfully seeded!");
        } else {
            Log::warning('LeadInventorySeeder: No inventory items were created.');
            $this->command->warn('LeadInventorySeeder: No inventory items were created.');
        }
        
        if ($attempts >= $maxAttempts && $createdCount < $inventoryCount) {
            Log::warning("LeadInventorySeeder: Hit max attempts ({$maxAttempts}) before creating desired count ({$inventoryCount}). Only created {$createdCount}.");
            $this->command->warn("LeadInventorySeeder: Hit max attempts, only created {$createdCount}/{$inventoryCount} items.");
        }
    }
}