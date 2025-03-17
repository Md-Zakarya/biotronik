<?php

namespace App\Exports;

use App\Models\LeadModel;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;

class LeadModelsExport extends DefaultValueBinder implements 
    FromQuery, 
    WithHeadings, 
    WithMapping,
    WithCustomValueBinder
{
    public function query()
    {
        return LeadModel::query();
    }

    public function headings(): array
    {
        return [
            'Model Number',
            'Model Name',
            'Device Type'
        ];
    }

    public function map($model): array
    {
        return [
            "\t" . $model->model_number,
            $model->model_name,
            $model->device_type
        ];
    }

    public function bindValue(Cell $cell, $value)
    {
        if ($cell->getColumn() == 'A') {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);
            return true;
        }
        return parent::bindValue($cell, $value);
    }
}