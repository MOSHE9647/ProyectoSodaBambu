<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;

class ClientController extends Controller
{
    public function index()
    {
        return Client::all();
    }

    public function show(Client $client)
    {
        return $client;
    }

    public function store(CreateClientRequest $request)
    {
        return Client::create($request->validated());
    }

    public function update(UpdateClientRequest $request, Client $client)
    {
        $client->update($request->validated());
        return $client;
    }

    public function destroy(Client $client)
    {
        $client->delete();
        return response()->json(['message' => 'Client deleted successfully']);
    }
}