<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LeadModel;

class LeadModelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $models = [
            ['model_number' => '123456', 'model_name' => 'Setrox SLX 58/39 BP', 'device_type' => 'Brady Lead'],
            ['model_number' => '123457', 'model_name' => 'Solox SLX 58/13-BP', 'device_type' => 'Brady Lead'],
            ['model_number' => '300001', 'model_name' => 'Selox SLX 58/13 BP', 'device_type' => 'Brady Lead'],
            ['model_number' => '300002', 'model_name' => 'Setrox S 45', 'device_type' => 'Brady Lead'],
            ['model_number' => '300003', 'model_name' => 'Selox SR 60', 'device_type' => 'Brady Lead'],
            ['model_number' => '346366', 'model_name' => 'Selox ST 53', 'device_type' => 'Brady Lead'],
            ['model_number' => '346367', 'model_name' => 'Selox ST 60', 'device_type' => 'Brady Lead'],
            ['model_number' => '346368', 'model_name' => 'Selox JT 53', 'device_type' => 'Brady Lead'],
            ['model_number' => '346369', 'model_name' => 'Selox JT 45', 'device_type' => 'Brady Lead'],
            ['model_number' => '350974', 'model_name' => 'Setrox S 53', 'device_type' => 'Brady Lead'],
            ['model_number' => '350975', 'model_name' => 'Setrox S 60', 'device_type' => 'Brady Lead'],
            ['model_number' => '370945', 'model_name' => 'Safio S 53', 'device_type' => 'Brady Lead'],
            ['model_number' => '370946', 'model_name' => 'Safio S 60', 'device_type' => 'Brady Lead'],
            ['model_number' => '377176', 'model_name' => 'Solia S 45', 'device_type' => 'Brady Lead'],
            ['model_number' => '377177', 'model_name' => 'Solia S 53', 'device_type' => 'Brady Lead'],
            ['model_number' => '377179', 'model_name' => 'Solia S 60', 'device_type' => 'Brady Lead'],
            ['model_number' => '377180', 'model_name' => 'Solia T 53', 'device_type' => 'Brady Lead'],
            ['model_number' => '377181', 'model_name' => 'Solia T 60', 'device_type' => 'Brady Lead'],
            ['model_number' => '377182', 'model_name' => 'MEX 60/15-BP', 'device_type' => 'Brady Lead'],
            ['model_number' => '395134', 'model_name' => 'Solia JT 53', 'device_type' => 'Brady Lead'],
            ['model_number' => '377166', 'model_name' => 'Linox Smart ProMRI S 65', 'device_type' => 'Tachy Lead'],
            ['model_number' => '377169', 'model_name' => 'Linox Smart ProMRI SD 65/16', 'device_type' => 'Tachy Lead'],
            ['model_number' => '377211', 'model_name' => 'Linox Smart ProMRI S DX 65/15', 'device_type' => 'Tachy Lead'],
            ['model_number' => '394099', 'model_name' => 'Protego ProMRI S 65', 'device_type' => 'Tachy Lead'],
            ['model_number' => '399414', 'model_name' => 'Protego ProMRI SD 65/16', 'device_type' => 'Tachy Lead'],
            ['model_number' => '402262', 'model_name' => 'Plexa ProMRI SD 65/16', 'device_type' => 'Tachy Lead'],
            ['model_number' => '402266', 'model_name' => 'Plexa ProMRI S 65', 'device_type' => 'Tachy Lead'],
            ['model_number' => '413997', 'model_name' => 'Plexa ProMRI DF-1 S 65', 'device_type' => 'Tachy Lead'],
            ['model_number' => '414000', 'model_name' => 'Plexa ProMRI DF-1 SD 65/16', 'device_type' => 'Tachy Lead'],
            ['model_number' => '414005', 'model_name' => 'Plexa ProMRI DF-1 S DX 65/15', 'device_type' => 'Tachy Lead'],
            ['model_number' => '414062', 'model_name' => 'Protego DF-1 ProMRI S 65', 'device_type' => 'Tachy Lead'],
            ['model_number' => '414064', 'model_name' => 'Protego DF-1 ProMRI S DX 65/15', 'device_type' => 'Tachy Lead'],
            ['model_number' => '436909', 'model_name' => 'Plexa ProMRI S DX 65/15', 'device_type' => 'Tachy Lead'],
            ['model_number' => '371723', 'model_name' => 'MyoPore BP25', 'device_type' => 'CRT Lead'],
            ['model_number' => '371724', 'model_name' => 'MyoPore BP35', 'device_type' => 'CRT Lead'],
            ['model_number' => '371725', 'model_name' => 'MyoPore BP54', 'device_type' => 'CRT Lead'],
            ['model_number' => '381490', 'model_name' => 'Corox ProMRI OTW-S 85-BP', 'device_type' => 'CRT Lead'],
            ['model_number' => '381491', 'model_name' => 'Corox ProMRI OTW-L 85-BP', 'device_type' => 'CRT Lead'],
            ['model_number' => '398676', 'model_name' => 'SENTUS PRO MRI OTW BP L-75', 'device_type' => 'CRT Lead'],
            ['model_number' => '398677', 'model_name' => 'Sentus ProMRI OTW BP L-85', 'device_type' => 'CRT Lead'],
            ['model_number' => '401177', 'model_name' => 'Sentus ProMRI OTW BP S-85', 'device_type' => 'CRT Lead'],
            ['model_number' => '401180', 'model_name' => 'Sentus ProMRI OTW QP S-85', 'device_type' => 'CRT Lead'],
            ['model_number' => '401183', 'model_name' => 'Sentus ProMRI OTW QP L-85', 'device_type' => 'CRT Lead'],
            ['model_number' => '408719', 'model_name' => 'Sentus ProMRI OTW QP L-85/49', 'device_type' => 'CRT Lead'],
        ];

        foreach ($models as $model) {
            LeadModel::updateOrCreate(
                ['model_number' => $model['model_number']],
                $model
            );
        }
    }
}