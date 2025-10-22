<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use DB;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;
use Yajra\DataTables\DataTables;

class ClientController extends Controller
{
	/**
	 * Display a listing of the resource.
	 *
	 * @param Request $request
	 * @return Factory|View|JsonResponse|\Illuminate\View\View
	 * @throws Exception
	 */
	public function index(Request $request)
	{
		// Fetch clients
		$clients = Client::all();
		$resource = ClientResource::collection($clients);

		// Handle AJAX request for DataTables
		if ($request->ajax()) {
			return DataTables::of($resource)->make();
		}

		// For non-AJAX requests, return the view
		return view('models.clients.index');
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Factory|View|\Illuminate\View\View
	 */
	public function create()
	{
		return view('models.clients.create');
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @param CreateClientRequest $request
	 * @return RedirectResponse
	 * @throws Throwable
	 */
	public function store(CreateClientRequest $request)
	{
		DB::transaction(function () use ($request) {
			// Create the Client
			$clientData = $request->validated();
			Client::create($clientData);
		});

		return redirect()->route('clients.index')->with('success', 'Cliente creado correctamente.');
	}

	/**
	 * Display the specified resource.
	 *
	 * @param Client $client
	 * @return Factory|View|\Illuminate\View\View
	 */
	public function show(Client $client)
	{
		$resource = ClientResource::make($client);
		return view('models.clients.show', ['client' => $resource]);
	}

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param Client $client
	 * @return Factory|View|\Illuminate\View\View
	 */
	public function edit(Client $client)
	{
		return view('models.clients.edit', compact('client'));
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param UpdateClientRequest $request
	 * @param Client $client
	 * @return RedirectResponse
	 * @throws Throwable
	 */
	public function update(UpdateClientRequest $request, Client $client)
	{
		DB::transaction(function () use ($request, $client) {
			// Update the Client
			$clientData = $request->validated();
			$client->update($clientData);
		});

		return redirect()->route('clients.index')->with('success', 'Cliente actualizado correctamente.');
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param Client $client
	 * @return RedirectResponse
	 * @throws Throwable
	 */
	public function destroy(Client $client)
	{
		// Use a transaction to ensure data integrity
		DB::transaction(function () use ($client) {
			// Delete the client record
			$client->delete();
		});

		// Redirect back with a success message
		return redirect()->back()->with('success', 'Cliente eliminado correctamente.');
	}
}