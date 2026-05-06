<?php

namespace App\Http\Controllers;

use Amp\Http\HttpStatus;
use App\Actions\Contract\UpsertContractAction;
use App\Enums\ProductType;
use App\Http\Requests\ContractRequest;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
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
        $clients = Client::all(['id', 'first_name', 'last_name']);
        $products = Product::whereIn('type', [ProductType::DISH, ProductType::DRINK])
            ->get(['id', 'name', 'sale_price', 'type']);

        return view('models.contracts.create', compact('clients', 'products'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ContractRequest $contractRequest, UpsertContractAction $upsertContractAction): JsonResponse
    {
        // The validated data is automatically retrieved from the ContractRequest
        $validatedData = $contractRequest->validated();

        // Separate the main contract data from the details and payment data
        $contractData = Arr::except($validatedData, ['contract_details', 'payment_details']);
        $contractDetailsData = $validatedData['contract_details'] ?? [];
        $paymentDetailsData = $validatedData['payment_details'] ?? null;

        // Execute the upsert action to create the contract along with its details and payment
        $upsertContractAction->execute(
            $contractData,
            $contractDetailsData,
            $paymentDetailsData
        );

        // Flash a success message to the session
        session()->flash('success', 'Contrato creado exitosamente.');

        return response()->json([
            'redirect' => route('contracts.index'),
            'message' => 'Datos del contrato guardados exitosamente.',
        ], HttpStatus::CREATED);
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
        $clients = Client::all(['id', 'first_name', 'last_name']);
        $products = Product::whereIn('type', [ProductType::DISH, ProductType::DRINK])
            ->get(['id', 'name', 'sale_price', 'type']);

        return view('models.contracts.edit', compact('contract', 'clients', 'products'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ContractRequest $contractRequest, UpsertContractAction $upsertContractAction): JsonResponse
    {
        // The validated data is automatically retrieved from the ContractRequest
        $validatedData = $contractRequest->validated();

        // Separate the main contract data from the details and payment data
        $contractData = Arr::except($validatedData, ['contract_details', 'payment_details']);
        $contractDetailsData = $validatedData['contract_details'] ?? [];
        $paymentDetailsData = $validatedData['payment_details'] ?? null;

        // Execute the upsert action to create the contract along with its details and payment
        $upsertContractAction->execute(
            $contractData,
            $contractDetailsData,
            $paymentDetailsData
        );

        // Flash a success message to the session
        session()->flash('success', 'Contrato actualizado exitosamente.');

        return response()->json([
            'redirect' => route('contracts.index'),
            'message' => 'Datos del contrato actualizados exitosamente.',
        ], HttpStatus::OK);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Contract $contract)
    {
        //
    }
}
