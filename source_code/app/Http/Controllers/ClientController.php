<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;

class ClientController extends Controller
{
    /**
     * Muestra una lista de clientes.
     */
    public function index()
    {
        $clients = Client::all();
        // Devuelve la vista 'clients.index' con todos los clientes.
        return view('clients.index', compact('clients'));
    }

    /**
     * Muestra el formulario para crear un nuevo cliente.
     */
    public function create()
    {
        // Devuelve la vista del formulario de creación.
        return view('clients.create');
    }

    /**
     * Guarda un nuevo cliente en la base de datos.
     */
    public function store(CreateClientRequest $request)
    {
        Client::create($request->validated());
        // Redirige al listado con un mensaje de éxito.
        return redirect()->route('clients.index')->with('success', 'Cliente creado exitosamente!');
    }

    /**
     * Muestra un cliente específico (opcional, pero útil).
     */
    public function show(Client $client)
    {
        return view('clients.show', compact('client'));
    }

    /**
     * Muestra el formulario para editar un cliente.
     */
    public function edit(Client $client)
    {
        // Devuelve la vista del formulario de edición con los datos del cliente.
        return view('clients.edit', compact('client'));
    }

    /**
     * Actualiza un cliente existente en la base de datos.
     */
    public function update(UpdateClientRequest $request, Client $client)
    {
        $client->update($request->validated());
        // Redirige al listado con un mensaje de éxito.
        return redirect()->route('clients.index')->with('success', 'Cliente actualizado exitosamente!');
    }

    /**
     * Elimina un cliente de la base de datos.
     */
    public function destroy(Client $client)
    {
        $client->delete();
        // Redirige al listado con un mensaje de éxito.
        return redirect()->route('clients.index')->with('success', 'Cliente eliminado exitosamente!');
    }
}