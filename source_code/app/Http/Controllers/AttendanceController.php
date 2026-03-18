<?php

namespace App\Http\Controllers;

use App\Actions\Timesheets\BuildSalaryTabDataAction;
use App\Actions\Timesheets\StoreAttendanceAction;
use App\Enums\UserRole;
use App\Http\Requests\TimesheetRequest;
use App\Models\Employee;
use App\Models\Timesheet;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;
use Yajra\DataTables\DataTables;

class AttendanceController extends Controller implements HasMiddleware
{
	private const TZ = 'America/Costa_Rica';

	/**
	 * Define middleware for the controller.
	 *
	 * @return array<int, Middleware>
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
	 * Display the attendance management view.
	 *
	 * Retrieves the active tab from the session and returns the employees index view
	 * with the attendance tab selected by default.
	 *
	 * @return View The employees index view with active tab and attendance payload.
	 */
	public function index()
	{
		$activeTab = session('active_tab', 'nav-attendance');
		$employees = $this->getAttendanceEmployees();

		// Transform employees for the attendance tab JavaScript payload.
		$attendanceEmployees = $employees->map($this->transformEmployee(...))->values()->all();

		return view('models.employees.index', [
			'activeTab' => $activeTab,
			'employees' => $employees,
			'attendanceEmployees' => $attendanceEmployees,
			'todayDate' => $this->today(),
		]);
	}

	/**
	 * Store a new attendance record.
	 *
	 * @param TimesheetRequest $request Validated timesheet request.
	 * @param StoreAttendanceAction $storeAction Attendance persistence action.
	 * @return \Illuminate\Http\RedirectResponse Redirect to attendance index with success message.
	 */
	public function store(TimesheetRequest $request, StoreAttendanceAction $storeAction)
	{
		$storeAction->execute($request->validated());
		return $this->redirectWithSuccess('creado');
	}

	/**
	 * Update an existing attendance record.
	 *
	 * @param TimesheetRequest $request Validated timesheet request.
	 * @param Timesheet $timesheet Existing timesheet model.
	 * @param StoreAttendanceAction $storeAction Attendance persistence action.
	 * @return \Illuminate\Http\RedirectResponse Redirect to attendance index with success message.
	 */
	public function update(TimesheetRequest $request, Timesheet $timesheet, StoreAttendanceAction $storeAction)
	{
		$storeAction->execute($request->validated(), $timesheet);
		return $this->redirectWithSuccess('actualizado');
	}

	/**
	 * Soft delete an attendance record.
	 *
	 * @param Timesheet $timesheet Timesheet model to delete.
	 * @return \Illuminate\Http\RedirectResponse Redirect to attendance index with success message.
	 */
	public function destroy(Timesheet $timesheet)
	{
		$timesheet->delete();
		return $this->redirectWithSuccess('eliminado');
	}

	/**
	 * Resolve and render a lazy-loaded tab view.
	 *
	 * @param string $tab Tab key.
	 * @return View
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
	 * Build the attendance tab view.
	 *
	 * @return View
	 */
	private function attendanceTab(): View
	{
		$employees = $this->getAttendanceEmployees();
		$todayDate = $this->today();

		return view('models.employees.tabs.attendance', compact('employees', 'todayDate'));
	}

	/**
	 * Transform an employee model into the attendance payload expected by the frontend.
	 *
	 * @param Employee $employee Employee model instance.
	 * @return array<string, mixed>
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
	 * Retrieve employees used by attendance views.
	 *
	 * @return \Illuminate\Database\Eloquent\Collection<int, Employee>
	 */
	private function getAttendanceEmployees()
	{
		return Employee::with([
			'user',
			'timesheets' => fn ($q) => $q->whereDate('work_date', $this->today())
		])->get();
	}

	/**
	 * Redirect to attendance index with a success flash message.
	 *
	 * @param string $action Action suffix used in the success message.
	 * @return \Illuminate\Http\RedirectResponse
	 */
	private function redirectWithSuccess(string $action)
	{
		return redirect()->route('attendance.index')->with([
			'active_tab' => 'nav-history',
			'success' => "Registro de asistencia $action exitosamente.",
		]);
	}

	/**
	 * Build the attendance history tab view.
	 *
	 * @return View
	 */
	private function historyTab(): View
	{
		return view('models.employees.tabs.history');
	}

	/**
	 * Provide attendance history data for DataTables with optional filters.
	 *
	 * @param Request $request HTTP request with table and filter parameters.
	 * @return \Illuminate\Http\JsonResponse
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
			->map(fn(Timesheet $t) => [
				'id' => $t->employee_id,
				'name' => $t->employee?->user?->name,
			])
			->filter(fn(array $e) => filled($e['id']) && filled($e['name']))
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
			->addColumn('employee', fn(Timesheet $t) => [
				'name' => $t->employee?->user?->name,
				'email' => $t->employee?->user?->email,
			])
			->with(['employee_filters' => $employeeFilters])
			->toJson();
	}

	/**
	 * Build the salary tab view.
	 *
	 * @return View
	 */
	private function salaryTab(Request $request, BuildSalaryTabDataAction $buildSalaryTabDataAction): View
	{
		$salaryData = $buildSalaryTabDataAction->execute(
			$request->integer('employee_id'),
			$request->input('payroll_period'),
			$request->input('payroll_half'),
		);

		return view('models.employees.tabs.salary', $salaryData);
	}
}
