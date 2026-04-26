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
        $currencyFormat = '"₡" #,##0';
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($activeSection === 'products' ? 'Productos Vendidos' : 'Reporte de Ventas');
        $sheet->setCellValue('A1', $activeSection === 'products' ? 'Productos Más Vendidos' : 'Reporte de Ventas');
        $sheet->setCellValue('A2', 'Periodo: '.($reportData['periodLabel'] ?? 'N/A'));
        $sheet->setCellValue('A3', 'Tipo de producto: '.($reportData['activeProductTypeLabel'] ?? 'Todos'));
        $sheet->setCellValue('A4', 'Categoría: '.($reportData['activeCategoryName'] ?? 'Todas'));
        $sheet->setCellValue('A5', 'Generado el: '.$currentDate->format('d/m/Y H:i'));

        if ($activeSection === 'products') {
            $productsIncomeOriginal = collect($reportData['topProducts'] ?? [])->sum('income');
            $productsIncomeTotal = (int) ($productsIncomeOriginal / 1000);
            $sheet->setCellValue('A6', 'Ingresos totales (según tabla): ₡ '.number_format($productsIncomeTotal, 0, ',', '.'));

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
                $incomeAdjusted = (int) (($product['income'] ?? 0) / 1000);
                $sheet->setCellValue('E'.$row, $incomeAdjusted);
                $sheet->setCellValue('F'.$row, (float) ($product['total_percent'] ?? 0));
                $row++;
            }

            $summaryRow = $row;
            $sheet->setCellValue('D'.$summaryRow, 'TOTAL INGRESOS');
            $sheet->setCellValue('E'.$summaryRow, $productsIncomeTotal);
            $sheet->setCellValue('F'.$summaryRow, '100%');
            $currencyFormat = '"₡" #,##0';
            $sheet->getStyle('E8:E'.$row)->getNumberFormat()->setFormatCode($currencyFormat);
            $sheet->getStyle('F8:F'.$row)->getNumberFormat()->setFormatCode('0.00"%"');
            $lastRow = max($summaryRow, 7);
            $this->applyStyles($sheet, $lastRow, 'F', $summaryRow);
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
        $totalSalesIncome = 0;
        $row = 8;
        foreach (($reportData['dailyReports'] ?? []) as $report) {
            $income = (int) ($report['income'] ?? 0 / 1000);
            $totalSalesIncome += $income;
            $sheet->setCellValue('A'.$row, $report['date'] ?? '');
            $sheet->setCellValue('B'.$row, (int) ($report['orders'] ?? 0));
            $sheet->setCellValue('C'.$row, $income);
            $avgTicket = (int) (($report['avg_ticket'] ?? 0) / 1000);
            $sheet->setCellValue('D'.$row, $avgTicket);
            $row++;
        }
        $currencyFormat = '"₡" #,##0';
        $sheet->getStyle('C8:C'.$row)->getNumberFormat()->setFormatCode($currencyFormat);
        $sheet->getStyle('D8:D'.$row)->getNumberFormat()->setFormatCode($currencyFormat);
        $lastRow = max($row - 1, 7);
        $summaryRow = $row;
        $sheet->setCellValue('B'.$summaryRow, 'TOTALES');
        $sheet->setCellValue('C'.$summaryRow, $totalSalesIncome);
        $currencyFormat = '"₡" #,##0';
        $sheet->getStyle('C8:C'.$summaryRow)->getNumberFormat()->setFormatCode($currencyFormat);
        $sheet->getStyle('D8:D'.($row - 1))->getNumberFormat()->setFormatCode($currencyFormat);

        $lastRow = max($summaryRow, 7);
        $this->applyStyles($sheet, $lastRow, 'D', $summaryRow);

        foreach (['A', 'B', 'C', 'D'] as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return $spreadsheet;
    }

    /**
     * Helper para aplicar estilos comunes
     */
    private function applyStyles($sheet, $lastRow, $lastColLetter, $summaryRow)
    {
        $sheet->getStyle('A1:'.$lastColLetter.'6')->getFont()->setBold(true);
        $sheet->getStyle('A7:'.$lastColLetter.'7')->getFont()->setBold(true);
        $sheet->getStyle('A7:'.$lastColLetter.'7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A7:'.$lastColLetter.'7')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD9EAD3');

        // Estilo fila de totales
        $sheet->getStyle('A'.$summaryRow.':'.$lastColLetter.$summaryRow)->getFont()->setBold(true);
        $sheet->getStyle('A'.$summaryRow.':'.$lastColLetter.$summaryRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF3F8ED');

        $sheet->getStyle('A1:'.$lastColLetter.$lastRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    }
}
