<?php

namespace Database\Seeders;

use App\Models\DeviceType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class DeviceTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Log::info('Starting DeviceTypeSeeder...');

        $deviceTypes = [
            ['device_id' => 1, 'device_name' => 'Single Chamber Brady', 'therapy_id' => 1],
            ['device_id' => 2, 'device_name' => 'Dual Chamber Brady', 'therapy_id' => 1],
            ['device_id' => 3, 'device_name' => 'Triple Chamber Brady', 'therapy_id' => 1],
            ['device_id' => 4, 'device_name' => 'Single Chamber Tachy', 'therapy_id' => 2],
            ['device_id' => 5, 'device_name' => 'Dual Chamber Tachy', 'therapy_id' => 2],
            ['device_id' => 6, 'device_name' => 'Triple Chamber Tachy', 'therapy_id' => 2],
        ];

        foreach ($deviceTypes as $deviceType) {
            DeviceType::updateOrCreate(
                ['device_id' => $deviceType['device_id']],
                $deviceType
            );
        }

        Log::info('DeviceTypeSeeder completed successfully.');
    }
}