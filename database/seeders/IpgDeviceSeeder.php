<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\IpgDevice;

class IpgDeviceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Define model families
        $modelFamilies = [
            [
                'name' => 'CardioRhythm',
                'number_prefix' => 'CR',
                'variants' => ['Pro', 'Plus', 'Advanced', 'Elite']
            ],
            [
                'name' => 'NeuroStim',
                'number_prefix' => 'NS',
                'variants' => ['Standard', 'Premium', 'Ultimate']
            ],
            [
                'name' => 'PaceMaker',
                'number_prefix' => 'PM',
                'variants' => ['Basic', 'Extended', 'Pro']
            ],
            [
                'name' => 'VitalPulse',
                'number_prefix' => 'VP',
                'variants' => ['Lite', 'Regular', 'Enhanced']
            ],
        ];

        // Generate 20 unique IPG devices
        for ($i = 0; $i < 20; $i++) {
            // Select a random model family
            $family = $modelFamilies[array_rand($modelFamilies)];
            
            // Select a random variant from the family
            $variant = $family['variants'][array_rand($family['variants'])];
            
            // Generate model name
            $modelName = $family['name'] . ' ' . $variant;
            
            // Generate model number (format: PREFIX-VARIANT-YEAR-NUMBER)
            $modelYear = rand(21, 23); // Years 2021-2023
            $modelNumber = sprintf(
                '%s-%s-%d-%03d', 
                $family['number_prefix'],
                substr($variant, 0, 2), 
                $modelYear,
                rand(100, 999)
            );
            
            // Generate serial number (format: PREFIX-YYYYMMDD-NNNNN)
            $manufacturingDate = date('Ymd', strtotime('-' . rand(1, 365) . ' days'));
            $serialNumber = sprintf(
                '%s%s%05d',
                $family['number_prefix'],
                $manufacturingDate,
                rand(10000, 99999)
            );
            
            IpgDevice::create([
                'ipg_serial_number' => $serialNumber,
                'ipg_model_name' => $modelName,
                'ipg_model_number' => $modelNumber,
                'is_linked' => false,
            ]);
        }

        $this->command->info('20 IPG devices successfully seeded!');
    }
}