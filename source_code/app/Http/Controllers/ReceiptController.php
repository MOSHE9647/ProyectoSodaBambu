<?php

namespace App\Http\Controllers;

use App\Contracts\Receipable;
use App\Enums\UserRole;
use App\Models\Contract;
use App\Models\Purchase;
use App\Models\Sale;
use App\Services\Receipt\ReceiptBuilder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Middleware\RoleMiddleware;

/**
 * ReceiptController - Maneja operaciones genéricas de recibos.
 *
 * Este controlador proporciona endpoints para cualquier modelo que implemente
 * la interfaz Receipable, permitiendo reutilizar el flujo de pago y recibos
 * para Sales, Contracts y otros modelos.
 */
class ReceiptController extends Controller implements HasMiddleware
{
    public static function middleware()
    {
        $allowedRoles = [UserRole::ADMIN->value, UserRole::EMPLOYEE->value];

        return [
            new Middleware(RoleMiddleware::using($allowedRoles)),
        ];
    }

    /**
     * Obtiene los datos completos del recibo para un modelo Receipable.
     *
     * Endpoint: GET /receipts/{model}/{id}
     *
     * @param  string  $model  El nombre del modelo (ej: 'sales', 'contracts')
     * @param  int  $id  El ID del modelo
     * @return JsonResponse|View Retorna un JSON con los datos del recibo o una vista si se solicita HTML
     */
    public function show(string $model, int $id): JsonResponse|View
    {
        $modelClass = $this->resolveModelClass($model);

        if (! $modelClass) {
            return response()->json([
                'message' => "Tipo de modelo '{$model}' no válido.",
            ], 400);
        }

        // Busca la instancia del modelo
        $instance = $modelClass::findOrFail($id);

        // Verifica que implemente Receipable
        if (! $instance instanceof Receipable) {
            return response()->json([
                'message' => "El modelo {$model} no implementa la interfaz Receipable.",
            ], 400);
        }

        // Construye los datos del recibo
        $receiptData = (new ReceiptBuilder($instance))->build();

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json(['data' => $receiptData]);
        }

        return view('templates.receipt', compact('receiptData'));
    }

    /**
     * Obtiene el HTML del modal de pago para cualquier modelo Receipable.
     *
     * Endpoint: GET /receipts/payment-modal
     *
     * @param  int  $paymentTotal  El total a pagar
     * @return string HTML del modal de pago
     */
    public function paymentModal(int $paymentTotal)
    {
        $validatedData = Validator::make(
            ['total' => $paymentTotal],
            ['total' => ['required', 'integer', 'min:0']],
            [
                'total.required' => 'El total es requerido para mostrar el modal de pago.',
                'total.integer' => 'El total debe ser un número válido.',
                'total.min' => 'El total no puede ser negativo.',
            ]
        )->validate();

        return view('models.payments._payment-modal', [
            'paymentTotal' => (int) $validatedData['total'],
        ])->render();
    }

    /**
     * Resuelve el nombre del modelo al nombre de clase correspondiente.
     *
     * @param  string  $model  El nombre abreviado del modelo (ej: 'sales', 'contracts')
     * @return class-string|null El nombre completo de la clase o null si no es válido
     */
    private function resolveModelClass(string $model): ?string
    {
        $modelMap = [
            'sales' => Sale::class,
            'sale' => Sale::class,
            'contracts' => Contract::class,
            'contract' => Contract::class,
            'purchases' => Purchase::class,
            'purchase' => Purchase::class,
        ];

        return $modelMap[strtolower($model)] ?? null;
    }
}
