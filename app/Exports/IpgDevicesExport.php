<?php

namespace App\Exports;

use App\Models\IpgSerial;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;

class IpgDevicesExport extends DefaultValueBinder implements FromQuery, WithHeadings, WithMapping, WithCustomValueBinder
{
    use Exportable;

    public function query()
    {
        return IpgSerial::query()
            ->with(['ipgModel', 'distributor', 'ipgDevice']);
    }

    public function headings(): array
    {
        return [
            'Serial Number',
            'Model Number',
            'Model Name',
            'Device Type',
            'Distributor Name',
        ];
    }

    public function map($ipgSerial): array
    {
        return [
            "\t" . (string) $ipgSerial->ipg_serial_number, // Add tab character
            "\t" . (string) $ipgSerial->model_number,   
            $ipgSerial->ipgModel ? $ipgSerial->ipgModel->model_name : 'N/A',
            $ipgSerial->ipgModel ? $ipgSerial->ipgModel->device_type : 'N/A',
            $ipgSerial->distributor ? $ipgSerial->distributor->name : 'N/A',
        ];
    }
    
    /**
     * Force serial numbers to be treated as strings
     */
    public function bindValue(Cell $cell, $value)
    {
        if ($cell->getColumn() == 'A' || $cell->getColumn() == 'B') {
            // Force Serial Number and Model Number columns to be treated as text
            $cell->setValueExplicit($value, DataType::TYPE_STRING);
            return true;
        }

        // Use default handling for other cells
        return parent::bindValue($cell, $value);
    }
}