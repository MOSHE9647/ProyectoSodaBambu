<?php

namespace App\Actions\Sale;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class BuildSalesReportSpreadsheetAction
{
    /**
     * Build an Excel spreadsheet with the sales report data.
     *
     * @param  array<string, mixed>  $reportData
     */
    public function execute(array $reportData): Spreadsheet
    {
        $currentDate = Carbon::now('America/Costa_Rica');
        $activeSection = $reportData['activeSection'] ?? 'sales';

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($activeSection === 'products' ? 'Productos Vendidos' : 'Reporte de Ventas');

        $sheet->setCellValue('A1', $activeSection === 'products' ? 'Productos Más Vendidos' : 'Reporte de Ventas');
        $sheet->setCellValue('A2', 'Periodo: '.($reportData['periodLabel'] ?? 'N/A'));
        $sheet->setCellValue('A3', 'Tipo de producto: '.($reportData['activeProductTypeLabel'] ?? 'Todos'));
        $sheet->setCellValue('A4', 'Categoría: '.($reportData['activeCategoryName'] ?? 'Todas'));
        $sheet->setCellValue('A5', 'Generado el: '.$currentDate->format('d/m/Y H:i'));

        if ($activeSection === 'products') {
            $headers = ['Producto', 'Categoría', 'Tipo', 'Cantidad Vendida', 'Ingresos', '% del Total'];

            foreach ($headers as $index => $header) {
                $column = chr(ord('A') + $index);
                $sheet->setCellValue($column.'7', $header);
            }

            $row = 8;
            foreach (($reportData['topProducts'] ?? []) as $product) {
                $sheet->setCellValue('A'.$row, $product['product_name'] ?? '');
                $sheet->setCellValue('B'.$row, $product['category_name'] ?? '');
                $sheet->setCellValue('C'.$row, $product['product_type_label'] ?? '');
                $sheet->setCellValue('D'.$row, (int) ($product['sold_quantity'] ?? 0));
                $sheet->setCellValue('E'.$row, (float) ($product['income'] ?? 0));
                $sheet->setCellValue('F'.$row, (float) ($product['total_percent'] ?? 0));
                $row++;
            }

            $lastRow = max($row - 1, 7);
            $sheet->getStyle('A1:F5')->getFont()->setBold(true);
            $sheet->getStyle('A7:F7')->getFont()->setBold(true);
            $sheet->getStyle('A7:F7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('A7:F7')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD9EAD3');
            $sheet->getStyle('A1:F'.$lastRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('A1:F'.$lastRow)->getAlignment()->setWrapText(true);

            foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }

            return $spreadsheet;
        }

        $headers = ['Fecha', 'Ordenes', 'Ingresos', 'Ticket Promedio'];
        foreach ($headers as $index => $header) {
            $column = chr(ord('A') + $index);
            $sheet->setCellValue($column.'7', $header);
        }

        $row = 8;
        foreach (($reportData['dailyReports'] ?? []) as $report) {
            $sheet->setCellValue('A'.$row, $report['date'] ?? '');
            $sheet->setCellValue('B'.$row, (int) ($report['orders'] ?? 0));
            $sheet->setCellValue('C'.$row, (float) ($report['income'] ?? 0));
            $sheet->setCellValue('D'.$row, (float) ($report['avg_ticket'] ?? 0));
            $row++;
        }

        $lastRow = max($row - 1, 7);
        $sheet->getStyle('A1:D5')->getFont()->setBold(true);
        $sheet->getStyle('A7:D7')->getFont()->setBold(true);
        $sheet->getStyle('A7:D7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A7:D7')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD9EAD3');
        $sheet->getStyle('A1:D'.$lastRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A1:D'.$lastRow)->getAlignment()->setWrapText(true);

        foreach (['A', 'B', 'C', 'D'] as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return $spreadsheet;
    }
}
