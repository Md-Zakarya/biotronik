<?php

namespace App\Exports;

use App\Models\IpgModel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class IpgModelsExport extends DefaultValueBinder implements 
    FromCollection, 
    WithHeadings, 
    WithMapping, 
    WithCustomValueBinder,
    WithColumnFormatting,
    ShouldAutoSize
{
    use Exportable;

    public function collection()
    {
        return IpgModel::all();
    }

    public function headings(): array
    {
        return [
            'Model Number',
            'Model Name',
            'Device Type',
            'Warranty (Days)',  
            'CardioMessenger Enabled',
            'MRI Compatible'
        ];
    }

    // "\t" . (string) $ipgSerial->ipg_serial_number,   
    //         "\t" . (string) $ipgSerial->model_number,

    public function map($model): array
    {
        return [
            "\t" .  $model->model_number,
            $model->model_name,
            $model->device_type,
            $model->warranty > 0 ? $model->warranty : 'No Warranty',
            $model->cardiomessenger_enable ? 'Yes' : 'No',
            $model->mr_enabled ? 'Yes' : 'No',
        ];
    }
    
    /**
     * Force model numbers to be treated as strings
     */
    public function bindValue(Cell $cell, $value)
    {
        if ($cell->getColumn() == 'A') {
            // Force Model Number column to be treated as text
            if (is_string($value) && substr($value, 0, 1) === "'") {
                $value = substr($value, 1);
            }
            $cell->setValueExplicit((string)$value, DataType::TYPE_STRING);
            return true;
        }

        // Use default handling for other cells
        return parent::bindValue($cell, $value);
    }

    /**
     * Define column formats
     */
    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT,
            'D' => NumberFormat::FORMAT_NUMBER,
        ];
    }
}