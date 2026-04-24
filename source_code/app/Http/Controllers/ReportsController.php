<?php

namespace App\Http\Controllers;

use App\Actions\Sale\BuildSalesReportSpreadsheetAction;
use App\Actions\Sale\GetSalesReportDataAction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReportsController extends Controller
{
    public function reports(Request $request, GetSalesReportDataAction $getSalesReportDataAction)
    {
        $activeSection = $request->input('section', 'sales');

        $viewName = $activeSection === 'products'
            ? 'models.reports.bestsellingproducts'
            : 'models.reports.salesreports';

        return view($viewName, array_merge(
            $getSalesReportDataAction->execute($request->all()),
            [
                'activeSection' => $activeSection,
            ]
        ));
    }

    public function exportReports(
        Request $request,
        GetSalesReportDataAction $getSalesReportDataAction,
        BuildSalesReportSpreadsheetAction $buildSalesReportSpreadsheetAction,
    ) {
        $reportData = array_merge(
            $getSalesReportDataAction->execute($request->all()),
            ['activeSection' => $request->input('section', 'sales')]
        );
        $spreadsheet = $buildSalesReportSpreadsheetAction->execute($reportData);
        $currentDate = Carbon::now('America/Costa_Rica');
        $filePrefix = ($reportData['activeSection'] ?? 'sales') === 'products'
            ? 'reporte_productos_mas_vendidos_'
            : 'reporte_ventas_';
        $fileName = $filePrefix.$currentDate->format('d-m-Y_His').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
