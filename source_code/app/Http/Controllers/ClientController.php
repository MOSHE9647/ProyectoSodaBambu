<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClientRequest;
use App\Models\Client;
use Illuminate\Database\QueryException;
use PDOException;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ClientController extends Controller
{
    public function index()
    {
        try {
            return response()->json(Client::all(), 200);
        } catch (QueryException $e) {
            return $this->handleDatabaseError($e);
        } catch (PDOException $e) {
            return $this->handleDatabaseError($e);
        }
    }

    /**
     * Usa findOrFail para asegurar el 404 si el ID no existe (consistente con update/destroy).
     */
    public function show(string $id)
    {
        try {
            $client = Client::findOrFail($id);
            return response()->json($client, 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Client not found.'], 404);
        }
    }

    public function store(ClientRequest $request)
    {
        try {
            $client = Client::create($request->validated());
            return response()->json([
                'message' => 'Client created successfully.',
                'data' => $client
            ], 201);

        } catch (QueryException $e) {
            return $this->handleDatabaseError($e);
        } catch (PDOException $e) {
            return $this->handleDatabaseError($e);
        } catch (\Exception $e) {
            Log::error('Error storing client: ' . $e->getMessage());
            return response()->json([
                'message' => 'An unexpected error occurred.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Usa string $id y findOrFail para forzar la actualización (evita la creación de un nuevo registro).
     */
    public function update(ClientRequest $request, string $id)
    {
        try {
            $client = Client::findOrFail($id);
            
            $client->fill($request->validated());
            $client->save();

            return response()->json([
                'message' => 'Client updated successfully.',
                'data' => $client 
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Client not found.'], 404);
        } catch (QueryException $e) {
            return $this->handleDatabaseError($e);
        } catch (PDOException $e) {
            return $this->handleDatabaseError($e);
        } catch (\Exception $e) {
            Log::error('Error updating client: ' . $e->getMessage());
            return response()->json([
                'message' => 'An unexpected error occurred.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Usa string $id y findOrFail para forzar la eliminación.
     */
    public function destroy(string $id)
    {
        try {
            $client = Client::findOrFail($id);
            
            $client->delete();

            return response()->json([
                'message' => 'Client deleted successfully.'
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Client not found.'], 404);
        } catch (QueryException $e) {
            return $this->handleDatabaseError($e);
        } catch (PDOException $e) {
            return $this->handleDatabaseError($e);
        } catch (\Exception $e) {
             Log::error('Error deleting client: ' . $e->getMessage());
            return response()->json([
                'message' => 'An unexpected error occurred.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Maneja errores de la base de datos (conexión, tabla inexistente, etc.).
     */
    protected function handleDatabaseError(\Exception $e)
    {
        Log::error('Database Error: ' . $e->getMessage(), ['exception' => $e]);

        $message = 'Error de Base de Datos.';
        $status = 500;
        $errorMessage = $e->getMessage();

        // 503: Falla de Conexión
        if (str_contains($errorMessage, 'SQLSTATE[HY000]') || str_contains($errorMessage, 'Connection refused') || str_contains($errorMessage, 'Unknown database')) {
            $message = 'Error de Conexión o BD Desconocida. Asegúrate de que el servidor de MySQL esté activo y que el nombre de la BD en .env sea correcto.';
            $status = 503; 
        } 
        // 500: Falla de Tabla
        elseif (str_contains($errorMessage, 'table or view not found') || str_contains($errorMessage, 'Base table or view not found') || str_contains($errorMessage, 'does not exist')) {
             $message = 'Error de Tabla. La tabla \'clients\' no existe en la base de datos.';
             $status = 500;
        }

        return response()->json([
            'message' => $message,
            'details' => $errorMessage
        ], $status);
    }
}