<?php

namespace App\Exports;

use App\Models\LeadSerial;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class LeadsExport implements FromQuery, WithHeadings, WithMapping
{
    public function query()
    {
        return LeadSerial::with(['leadModel', 'distributor']);
    }

    public function headings(): array
    {
        return [
            'Serial Number',
            'Model Number',
            'Model Name',
            'Device Type',
            'Channel Partner'
        ];
    }

    public function map($lead): array
    {
        return [
            "\t" . $lead->serial_number,
            "\t" . $lead->lead_model_number,
            $lead->leadModel ? $lead->leadModel->model_name : 'N/A',
            $lead->leadModel ? $lead->leadModel->device_type : 'N/A',
            $lead->distributor ? $lead->distributor->name : 'Unassigned'
        ];
    }
}