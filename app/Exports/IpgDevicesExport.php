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
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class IpgDevicesExport extends DefaultValueBinder implements 
    FromQuery, 
    WithHeadings, 
    WithMapping, 
    WithCustomValueBinder, 
    ShouldAutoSize, 
    WithStyles,
    WithTitle
{
    use Exportable;

    public function query()
    {
        return IpgSerial::query()
            ->with(['ipgModel', 'distributor', 'ipgDevice']);
    }

    public function title(): string
    {
        return 'IPG Devices Report';
    }

    public function headings(): array
    {
        return [
            'Serial Number',
            'Model Number',
            'Model Name',
            'Device Type',
            'Distributor Name',
            'Invoice Date',
        ];
    }

    public function map($ipgSerial): array
    {
        // Format the date if it exists
        $invoiceDate = $ipgSerial->date_added ? \PhpOffice\PhpSpreadsheet\Shared\Date::dateTimeToExcel(\Carbon\Carbon::parse($ipgSerial->date_added)) : 'N/A';

        return [
            "\t" . (string) $ipgSerial->ipg_serial_number,
            "\t" . (string) $ipgSerial->model_number,
            $ipgSerial->ipgModel ? $ipgSerial->ipgModel->model_name : 'N/A',
            $ipgSerial->ipgModel ? $ipgSerial->ipgModel->device_type : 'N/A',
            $ipgSerial->distributor ? $ipgSerial->distributor->name : 'N/A',
            $invoiceDate,
        ];
    }

    public function bindValue(Cell $cell, $value)
    {
        if ($cell->getColumn() == 'A' || $cell->getColumn() == 'B') {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);
            return true;
        }

        if ($cell->getColumn() == 'F' && is_numeric($value)) {
             $cell->setValueExplicit($value, DataType::TYPE_NUMERIC);
             $cell->getWorksheet()->getStyle($cell->getCoordinate())->getNumberFormat()
                  ->setFormatCode('yyyy-mm-dd');
             return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function styles(Worksheet $sheet)
    {
        // Get the highest row and column indexes
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        
        // Style for headers (first row)
        $sheet->getStyle('A1:' . $highestColumn . '1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ]
        ]);
        
        // Set row height for header
        $sheet->getRowDimension(1)->setRowHeight(22);
        
        // Add borders to all cells
        $sheet->getStyle('A1:' . $highestColumn . $highestRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC'],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        
        // Alternate row colors for better readability
        for ($row = 2; $row <= $highestRow; $row++) {
            if ($row % 2 == 0) {
                $sheet->getStyle('A' . $row . ':' . $highestColumn . $row)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F2F2F2'],
                    ],
                ]);
            }
        }
        
        // Center specific columns (device type and date)
        $sheet->getStyle('D2:D' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('F2:F' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}