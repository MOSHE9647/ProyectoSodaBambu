<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\Timesheet;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;
use Yajra\DataTables\DataTables;

class AttendanceController extends Controller implements HasMiddleware
{
	/**
	 * Define middleware for the controller.
	 * @see https://laravel.com/docs/10.x/controllers#controller-middleware
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

		// Get all employees to populate the employee selection dropdown in the attendance form
		$employees = Employee::with(['user', 'timesheets' => function ($query) {
			$query->whereDate('work_date', now()->toDateString());
		}])->get();

		return view('models.employees.index', compact('activeTab', 'employees'));
	}

	public function store()
	{
		// Test data for development purposes
		return redirect()->route('attendance.index')->with([
			'active_tab' => 'nav-attendance',
			'success' => 'Asistencia registrada exitosamente.',
		]);
	}
	
	public function update()
	{
		// Test data for development purposes
		return redirect()->route('attendance.index')->with([
			'active_tab' => 'nav-attendance',
			'success' => 'Hora de salida registrada exitosamente.',
		]);
	}

	public function destroy()
	{
		// This method is not needed since attendance records are not deleted in this implementation
		abort(404);
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
		$employees = Employee::all();
		return view('models.employees.tabs.attendance', compact('employees'));
	}

	private function historyTab(): View
	{
		return view('models.employees.tabs.history');
	}

	public function historyData(Request $request)
	{
		$workDate = (string) $request->input('work_date');
		$month = (string) $request->input('month');

		$employeeFiltersQuery = Timesheet::query()
			->with(['employee.user'])
			->select('timesheets.employee_id');

		$this->applyHistoryDateFilters($employeeFiltersQuery, $workDate, $month);

		$employeeFilters = $employeeFiltersQuery
			->get()
			->map(function (Timesheet $timesheet): array {
				return [
					'id' => $timesheet->employee_id,
					'name' => $timesheet->employee?->user?->name,
				];
			})
			->filter(fn (array $employee): bool => filled($employee['id']) && filled($employee['name']))
			->unique('id')
			->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
			->values()
			->all();

		$query = Timesheet::query()
			->with(['employee.user'])
			->select('timesheets.*');

		$this->applyHistoryDateFilters($query, $workDate, $month);

		$employeeId = $request->integer('employee_id');
		if ($employeeId > 0) {
			$query->where('employee_id', $employeeId);
		}

		return DataTables::of($query)
			->addColumn('employee', function (Timesheet $timesheet): array {
				return [
					'name' => $timesheet->employee?->user?->name,
					'email' => $timesheet->employee?->user?->email,
				];
			})
			->with([
				'employee_filters' => $employeeFilters,
			])
			->toJson();
	}

	private function salaryTab(): View
	{
		return view('models.employees.tabs.salary');
	}

	private function applyHistoryDateFilters($query, string $workDate, string $month): void
	{
		if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $workDate) === 1) {
			$query->whereDate('work_date', $workDate);
			return;
		}

		if (preg_match('/^\d{4}-\d{2}$/', $month) === 1) {
			[$year, $monthNumber] = explode('-', $month);
			$query
				->whereYear('work_date', (int) $year)
				->whereMonth('work_date', (int) $monthNumber);
		}
	}
}
