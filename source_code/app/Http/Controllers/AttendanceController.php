<?php

namespace App\Http\Controllers;

use App\Actions\Timesheets\BuildSalaryTabDataAction;
use App\Actions\Timesheets\StoreAttendanceAction;
use App\Enums\UserRole;
use App\Http\Requests\TimesheetRequest;
use App\Models\Employee;
use App\Models\Timesheet;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Yajra\DataTables\DataTables;

class AttendanceController extends Controller implements HasMiddleware
{
    private const TZ = 'America/Costa_Rica';

    /**
     * Define middleware for the controller.
     *
     * Restricts access to authenticated users with the ADMIN role. Applied to all controller actions.
     *
     * @return array<int, Middleware> Array with admin role middleware requirement
     */
    public static function middleware(): array
    {
        $role = UserRole::ADMIN->value;

        return [
            new Middleware("role:$role"),
        ];
    }

    /**
     * Get today's date in Costa Rica timezone.
     *
     * @return string Today's date in 'Y-m-d' format.
     */
    private function today(): string
    {
        return Carbon::now(self::TZ)->toDateString();
    }

    /**
     * Display the attendance management interface.
     *
     * Loads active employees with their today's timesheet records and renders the main
     * attendance view. Attaches employee data in two formats: raw models and transformed
     * JavaScript-ready payload. Restores the last visited tab from session.
     *
     * @return View The attendance index view with active tab, employees, and today's date
     */
    public function index()
    {
        $activeTab = session('active_tab', 'nav-attendance');
        $employees = $this->getAttendanceEmployees();

        // Transform employees for the attendance tab JavaScript payload.
        $attendanceEmployees = $employees->map($this->transformEmployee(...))->values()->all();

        return view('models.attendance.index', [
            'activeTab' => $activeTab,
            'employees' => $employees,
            'attendanceEmployees' => $attendanceEmployees,
            'todayDate' => $this->today(),
        ]);
    }

    /**
     * Create and store a new attendance record.
     *
     * Persists timesheet data for an employee. Uses validated request data to create
     * a new timesheet entry or update existing if already present for the date.
     * Redirects to history tab with success confirmation.
     *
     * @param  TimesheetRequest  $request  Validated timesheet data (employee_id, work_date, start_time, end_time, is_holiday)
     * @param  StoreAttendanceAction  $storeAction  Action handler for timesheet persistence
     * @return RedirectResponse Redirect to attendance.index with success message
     */
    public function store(TimesheetRequest $request, StoreAttendanceAction $storeAction)
    {
        $storeAction->execute($request->validated());

        return $this->redirectWithSuccess('creado');
    }

    /**
     * Update an existing attendance record.
     *
     * Modifies an existing timesheet entry with new clock-in/out times, break adjustments,
     * or holiday status. Route model binding automatically resolves the timesheet from URL.
     * Redirects to history tab with success confirmation.
     *
     * @param  TimesheetRequest  $request  Validated timesheet data to apply
     * @param  Timesheet  $timesheet  Existing timesheet (auto-resolved via route model binding)
     * @param  StoreAttendanceAction  $storeAction  Action handler for timesheet updates
     * @return RedirectResponse Redirect to attendance.index with success message
     */
    public function update(TimesheetRequest $request, Timesheet $timesheet, StoreAttendanceAction $storeAction)
    {
        $storeAction->execute($request->validated(), $timesheet);

        return $this->redirectWithSuccess('actualizado');
    }

    /**
     * Delete (soft delete) an attendance record.
     *
     * Performs a soft delete on the timesheet, preserving data for audit trails while
     * removing it from active records. Route model binding automatically resolves the timesheet.
     * Redirects to history tab with success confirmation.
     *
     * @param  Timesheet  $timesheet  Timesheet to delete (auto-resolved via route model binding)
     * @return RedirectResponse Redirect to attendance.index with success message
     */
    public function destroy(Timesheet $timesheet)
    {
        $timesheet->delete();

        return $this->redirectWithSuccess('eliminado');
    }

    /**
     * Resolve and render a lazy-loaded attendance tab view.
     *
     * Routes tab requests to appropriate sub-views based on tab identifier:
     * - 'attendance': Current day clock in/out form
     * - 'history': Past records with filters and DataTable
     * - 'salary': Payroll calculation with employee and period selection
     *
     * @param  string  $tab  Tab identifier (attendance, history, or salary)
     * @param  Request  $request  HTTP request with filter/selection parameters
     * @param  BuildSalaryTabDataAction  $buildSalaryTabDataAction  Action to prepare salary tab data
     * @return View Rendered tab partial view with appropriate data
     *
     * @throws NotFoundHttpException If tab key is invalid
     */
    public function tab(string $tab, Request $request, BuildSalaryTabDataAction $buildSalaryTabDataAction): View
    {
        return match ($tab) {
            'attendance' => $this->attendanceTab(),
            'history' => $this->historyTab(),
            'salary' => $this->salaryTab($request, $buildSalaryTabDataAction),
            default => abort(404),
        };
    }

    /**
     * Render the attendance (clock in/out) tab view.
     *
     * Displays the current day's clock in/out form with active employees and today's date.
     * Used for real-time attendance recording.
     *
     * @return View The attendance tab partial with employees and today's date
     */
    private function attendanceTab(): View
    {
        $employees = $this->getAttendanceEmployees();
        $todayDate = $this->today();

        return view('models.attendance.tabs.attendance', compact('employees', 'todayDate'));
    }

    /**
     * Transform an employee model into JavaScript-ready attendance data.
     *
     * Extracts essential employee info and today's timesheet (if exists), formats time values
     * as H:i strings, and omits end_time unless hours are logged. Used to populate the
     * attendance form's employee dropdown with pre-filled data.
     *
     * @param  Employee  $employee  Employee model with user and timesheet relations loaded
     * @return array<string, mixed> Transformed data: id, name, email, today_timesheet (or null)
     */
    private function transformEmployee(Employee $employee): array
    {
        $ts = $employee->timesheets->first();

        return [
            'id' => $employee->id,
            'name' => $employee->user?->name,
            'email' => $employee->user?->email,
            'today_timesheet' => $ts ? [
                'id' => $ts->id,
                'work_date' => $ts->work_date,
                'start_time' => $ts->start_time
                    ? Carbon::parse($ts->start_time)->format('H:i')
                    : null,
                'end_time' => ($ts->total_hours > 0 && $ts->end_time)
                    ? Carbon::parse($ts->end_time)->format('H:i')
                    : null,
                'total_hours' => $ts->total_hours,
                'is_holiday' => $ts->is_holiday,
            ] : null,
        ];
    }

    /**
     * Load all employees with today's timesheet records.
     *
     * Eager-loads employee relationships: user data and today's timesheets filtered by
     * current date in Costa Rica timezone. Used to populate attendance interface.
     *
     * @return Collection<int, Employee> Employees with user and today's timesheet relations
     */
    private function getAttendanceEmployees()
    {
        return Employee::with([
            'user',
            'timesheets' => fn ($q) => $q->whereDate('work_date', $this->today()),
        ])->get();
    }

    /**
     * Redirect to attendance index with a localized success flash message.
     *
     * Routes to attendance.index, sets active_tab to history, and flashes a Spanish success
     * message with the provided action verb (e.g., 'creado' → 'Registro de asistencia creado exitosamente.').
     *
     * @param  string  $action  Action verb to include in message (creado, actualizado, eliminado, etc.)
     * @return RedirectResponse Redirect to attendance.index with flash data
     */
    private function redirectWithSuccess(string $action)
    {
        return redirect()->route('attendance.index')->with([
            'active_tab' => 'nav-history',
            'success' => "Registro de asistencia $action exitosamente.",
        ]);
    }

    /**
     * Render the attendance history tab view.
     *
     * Displays the history interface with filters and DataTable for browsing past attendance records.
     * DataTable data is loaded dynamically via historyData() endpoint.
     *
     * @return View The history tab partial view
     */
    private function historyTab(): View
    {
        return view('models.attendance.tabs.history');
    }

    /**
     * Fetch attendance history records as JSON for DataTable rendering.
     *
     * Queries timesheets with employee relations, applies optional date/month filters,
     * and returns employee filter dropdown options. Supports filtering by specific date,
     * month (YYYY-MM format), and/or individual employee_id.
     *
     * Query parameters: work_date (optional), month (optional, YYYY-MM), employee_id (optional)
     *
     * @param  Request  $request  HTTP request with DataTables parameters and filters
     * @return JsonResponse DataTables-formatted JSON with timesheet rows and employee filter options
     */
    public function historyData(Request $request)
    {
        $workDate = $request->input('work_date');
        $month = $request->input('month');

        $employeeFilters = Timesheet::query()
            ->with(['employee.user'])
            ->select('timesheets.employee_id')
            ->filterByDate($workDate, $month)
            ->get()
            ->map(fn (Timesheet $t) => [
                'id' => $t->employee_id,
                'name' => $t->employee?->user?->name,
            ])
            ->filter(fn (array $e) => filled($e['id']) && filled($e['name']))
            ->unique('id')
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();

        $query = Timesheet::query()
            ->with(['employee.user'])
            ->select('timesheets.*')
            ->filterByDate($workDate, $month);

        $employeeId = $request->integer('employee_id');
        if ($employeeId > 0) {
            $query->where('employee_id', $employeeId);
        }

        return DataTables::of($query)
            ->addColumn('employee', fn (Timesheet $t) => [
                'name' => $t->employee?->user?->name,
                'email' => $t->employee?->user?->email,
            ])
            ->with(['employee_filters' => $employeeFilters])
            ->toJson();
    }

    /**
     * Render the payroll calculation tab view.
     *
     * Loads salary calculation data for a selected employee and payroll period, then
     * renders the salary tab with daily breakdown, totals, and formatted amounts.
     *
     * Query parameters: employee_id (int), payroll_period (string), payroll_half (string)
     *
     * @param  Request  $request  HTTP request with employee_id, payroll_period, and payroll_half
     * @param  BuildSalaryTabDataAction  $buildSalaryTabDataAction  Action to compute salary data
     * @return View The salary tab partial with computed salary data
     */
    private function salaryTab(Request $request, BuildSalaryTabDataAction $buildSalaryTabDataAction): View
    {
        $salaryData = $buildSalaryTabDataAction->execute(
            $request->integer('employee_id'),
            $request->input('payroll_period'),
            $request->input('payroll_half'),
        );

        return view('models.attendance.tabs.salary', $salaryData);
    }
}
