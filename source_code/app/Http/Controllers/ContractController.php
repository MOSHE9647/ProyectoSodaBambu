<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ContractController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse|View
    {
        if ($request->ajax()) {
            $today = now()->startOfDay()->toDateString(); // Only compare dates without time

            $query = Contract::query()->withTrashed()
                ->when($request->filled('status') && $request->status !== 'all', fn ($q) => match ($request->status) {
                    'inactive' => $q->onlyTrashed(), // Show only soft-deleted contracts
                    'upcoming' => $q->whereNull('deleted_at')->where('start_date', '>', $today),
                    'expired' => $q->whereNull('deleted_at')->where('end_date', '<', $today),
                    'active' => $q->whereNull('deleted_at')
                        ->where('start_date', '<=', $today)
                        ->where('end_date', '>=', $today),
                    default => $q,
                });

            return DataTables::of($query)
                ->addColumn('status', fn (Contract $contract) => $contract->status)
                ->toJson();
        }

        return view('models.contracts.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Contract $contract)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Contract $contract)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Contract $contract)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Contract $contract)
    {
        //
    }
}
