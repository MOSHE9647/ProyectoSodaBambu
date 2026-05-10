<?php

namespace App\Actions\Sale;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class BuildSalesReportSpreadsheetAction
{
    private const CURRENCY_FORMAT = '"₡" #,##0';
    private const PERCENT_FORMAT = '0.00"%"';

    /**
     * Build an Excel spreadsheet with the sales report data.
     */
    public function execute(array $reportData): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $activeSection = $reportData['activeSection'] ?? 'sales';

        // 1. Configurar Metadatos y Encabezados de reporte
        $this->buildMetadata($sheet, $reportData, $activeSection);

        // 2. Construir el cuerpo de la tabla según la sección
        if ($activeSection === 'products') {
            $this->buildProductsSection($sheet, $reportData);
        } else {
            $this->buildSalesSection($sheet, $reportData);
        }

        return $spreadsheet;
    }

    /**
     * Escribe la información general del reporte en las primeras filas.
     */
    private function buildMetadata($sheet, array $reportData, string $activeSection): void
    {
        $sheet->setTitle($activeSection === 'products' ? 'Productos Vendidos' : 'Reporte de Ventas');

        $sheet->setCellValue('A1', $activeSection === 'products' ? 'Productos Más Vendidos' : 'Reporte de Ventas');
        $sheet->setCellValue('A2', 'Periodo: '.($reportData['periodLabel'] ?? 'N/A'));
        $sheet->setCellValue('A3', 'Tipo de producto: '.($reportData['activeProductTypeLabel'] ?? 'Todos'));
        $sheet->setCellValue('A4', 'Categoría: '.($reportData['activeCategoryName'] ?? 'Todas'));
        $sheet->setCellValue('A5', 'Generado el: '.Carbon::now('America/Costa_Rica')->format('d/m/Y H:i'));
    }

    /**
     * Construye la tabla específica de productos más vendidos.
     */
    private function buildProductsSection($sheet, array $reportData): void
    {
        $products = $reportData['topProducts'] ?? [];
        $totalIncome = (int) collect($products)->sum('income');
        $sheet->setCellValue('A6', 'Ingresos totales (según tabla): ₡ '.number_format($totalIncome, 0, ',', '.'));

        $headers = ['Producto', 'Categoría', 'Tipo', 'Cantidad Vendida', 'Ingresos', '% del Total'];
        $this->writeHeaders($sheet, $headers);

        $row = 8;
        foreach ($products as $product) {
            $sheet->setCellValue('A'.$row, $product['product_name'] ?? '');
            $sheet->setCellValue('B'.$row, $product['category_name'] ?? '');
            $sheet->setCellValue('C'.$row, $product['product_type_label'] ?? '');
            $sheet->setCellValue('D'.$row, (int) ($product['sold_quantity'] ?? 0));
            $sheet->setCellValue('E'.$row, (int) ($product['income'] ?? 0));
            $sheet->setCellValue('F'.$row, (float) ($product['total_percent'] ?? 0));
            $row++;
        }

        // Totales y Formato
        $sheet->setCellValue('D'.$row, 'TOTAL INGRESOS');
        $sheet->setCellValue('E'.$row, $totalIncome);
        $sheet->setCellValue('F'.$row, 1); // 100%

        $sheet->getStyle('E8:E'.$row)->getNumberFormat()->setFormatCode(self::CURRENCY_FORMAT);
        $sheet->getStyle('F8:F'.$row)->getNumberFormat()->setFormatCode(self::PERCENT_FORMAT);

        $this->applyStyles($sheet, $row, 'F');
    }

    /**
     * Construye la tabla de reporte de ventas diario.
     */
    private function buildSalesSection($sheet, array $reportData): void
    {
        $reports = $reportData['dailyReports'] ?? [];
        $headers = ['Fecha', 'Ordenes', 'Ingresos', 'Ticket Promedio'];
        $this->writeHeaders($sheet, $headers);

        $row = 8;
        $totalIncome = 0;
        foreach ($reports as $report) {
            $income = (int) ($report['income'] ?? 0);
            $totalIncome += $income;

            $sheet->setCellValue('A'.$row, $report['date'] ?? '');
            $sheet->setCellValue('B'.$row, (int) ($report['orders'] ?? 0));
            $sheet->setCellValue('C'.$row, $income);
            $sheet->setCellValue('D'.$row, (int) ($report['avg_ticket'] ?? 0));
            $row++;
        }

        // Totales y Formato
        $sheet->setCellValue('B'.$row, 'TOTALES');
        $sheet->setCellValue('C'.$row, $totalIncome);

        $sheet->getStyle('C8:C'.$row)->getNumberFormat()->setFormatCode(self::CURRENCY_FORMAT);
        $sheet->getStyle('D8:D'.($row - 1))->getNumberFormat()->setFormatCode(self::CURRENCY_FORMAT);

        $this->applyStyles($sheet, $row, 'D');
    }

    /**
     * Escribe los encabezados de la tabla y ajusta el ancho de columnas.
     */
    private function writeHeaders($sheet, array $headers): void
    {
        foreach ($headers as $index => $header) {
            $column = chr(ord('A') + $index);
            $sheet->setCellValue($column.'7', $header);
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    /**
     * Aplica los estilos visuales a la tabla.
     */
    private function applyStyles($sheet, int $lastRow, string $lastColLetter): void
    {
        // Estilo de metadatos y encabezados
        $sheet->getStyle('A1:'.$lastColLetter.'6')->getFont()->setBold(true);
        
        $sheet->getStyle('A7:'.$lastColLetter.'7')->getFont()->setBold(true);
        $sheet->getStyle('A7:'.$lastColLetter.'7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A7:'.$lastColLetter.'7')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD9EAD3');

        // Estilo fila de totales
        $sheet->getStyle('A'.$lastRow.':'.$lastColLetter.$lastRow)->getFont()->setBold(true);
        $sheet->getStyle('A'.$lastRow.':'.$lastColLetter.$lastRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF3F8ED');

        $sheet->getStyle('A1:'.$lastColLetter.$lastRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    }
}
