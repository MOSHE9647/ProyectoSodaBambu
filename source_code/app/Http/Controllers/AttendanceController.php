<?php

namespace App\Http\Controllers;

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
	 * @return \Illuminate\View\View The employees index view with the active tab information
	 */
	public function index()
	{
		$activeTab = session('active_tab', 'nav-attendance');
		$employees = $this->getAttendanceEmployees();

		// Transformamos los datos para el JS
		$attendanceEmployees = $employees->map($this->transformEmployee(...))->values()->all();

		return view('models.employees.index', [
			'activeTab' => $activeTab,
			'employees' => $employees,
			'attendanceEmployees' => $attendanceEmployees,
			'todayDate' => $this->today()
		]);
	}

	public function store(TimesheetRequest $request, StoreAttendanceAction $storeAction)
	{
		$storeAction->execute($request->validated());
		return $this->redirectWithSuccess('creado');
	}

	public function update(TimesheetRequest $request, Timesheet $timesheet, StoreAttendanceAction $storeAction)
	{
		$storeAction->execute($request->validated(), $timesheet);
		return $this->redirectWithSuccess('actualizado');
	}

	public function destroy(Timesheet $timesheet)
	{
		$timesheet->delete();
		return $this->redirectWithSuccess('eliminado');
	}

	public function tab(string $tab): View
	{
		return match ($tab) {
			'attendance' => $this->attendanceTab(),
			'history' => $this->historyTab(),
			'salary' => $this->salaryTab(),
			default => abort(404),
		};
	}

	private function attendanceTab(): View
	{
		$employees = $this->getAttendanceEmployees();
		$todayDate = $this->today();

		return view('models.employees.tabs.attendance', compact('employees', 'todayDate'));
	}

	private function transformEmployee(Employee $employee): array
	{
		$ts = $employee->timesheets->first();
		return [
			'id' => $employee->id,
			'name' => $employee->user?->name,
			'email' => $employee->user?->email,
			'today_timesheet' => $ts ? [
				'id' => $ts->id,
				'work_date' => $ts->work_date?->toDateString(),
				'start_time' => $ts->start_time?->format('H:i'),
				'end_time' => $ts->hours_worked > 0 ? $ts->end_time?->format('H:i') : null,
				'total_hours' => $ts->hours_worked,
				'is_holiday' => $ts->is_holiday,
			] : null,
		];
	}

	/**
	 * Retrieve employees used by attendance views.
	 */
	private function getAttendanceEmployees()
	{
		return Employee::with([
			'user',
			'timesheets' => fn ($q) => $q->whereDate('work_date', $this->today())
		])->get();
	}


	/**
	 * Build a lightweight employee payload consumed by attendance JS.
	 *
	 * @param \Illuminate\Support\Collection<int, Employee> $employees
	 * @return array<int, array<string, mixed>>
	 */
	private function buildAttendanceAppEmployees($employees): array
	{
		return $employees
			->map(function (Employee $employee) {
				$todayTimesheet = $employee->timesheets->first();

				return [
					'id' => $employee->id,
					'name' => $employee->user?->name,
					'email' => $employee->user?->email,
					'today_timesheet' => $todayTimesheet ? [
						'id' => $todayTimesheet->id,
						'work_date' => optional($todayTimesheet->work_date)?->toDateString(),
						'start_time' => optional($todayTimesheet->start_time)?->format('H:i'),
						'end_time' => (float) ($todayTimesheet->total_hours ?? 0) > 0
							? optional($todayTimesheet->end_time)?->format('H:i')
							: null,
						'total_hours' => (float) ($todayTimesheet->total_hours ?? 0),
						'is_holiday' => (bool) $todayTimesheet->is_holiday,
					] : null,
				];
			})
			->filter(fn(array $employee) => filled($employee['id']) && filled($employee['name']))
			->values()
			->all();
	}

	private function redirectWithSuccess(string $action)
	{
		return redirect()->route('attendance.index')->with([
			'active_tab' => 'nav-history',
			'success' => "Registro de asistencia $action exitosamente.",
		]);
	}

	private function historyTab(): View
	{
		return view('models.employees.tabs.history');
	}

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

	private function salaryTab(): View
	{
		return view('models.employees.tabs.salary');
	}

}
