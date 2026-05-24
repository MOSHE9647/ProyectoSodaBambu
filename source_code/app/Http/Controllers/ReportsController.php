<?php

namespace App\Http\Controllers;

use App\Actions\Sale\BuildSalesReportSpreadsheetAction;
use App\Actions\Sale\GetSalesReportDataAction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Yajra\DataTables\Facades\DataTables;

class ReportsController extends Controller
{
    public function index(Request $request, GetSalesReportDataAction $getSalesReportDataAction)
    {
        if ($request->wantsJson() || $request->ajax()) {
            $reportData = $getSalesReportDataAction->execute($request->all());

            $section = $request->input('section', 'sales');

            $data = $section === 'products'
                ? collect($reportData['topProducts'])
                : collect($reportData['dailyReports']);

            return DataTables::collection($data)->toJson();
        }

        $activeSection = $request->input('section', 'sales');
        $reportData = $getSalesReportDataAction->execute($request->all());

        return view('models.reports.index', array_merge($reportData,
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
