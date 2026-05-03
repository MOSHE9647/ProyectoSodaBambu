<?php

namespace App\Http\Controllers;

use Amp\Http\HttpStatus;
use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;

class OffcanvasFormController extends Controller
{
    public function __invoke(string $type): View|JsonResponse
    {
        return match ($type) {
            'client' => view('models.clients._form', [
                'action' => route('clients.store'),
                'isOffcanvas' => true,
                'client' => null,
            ]),
            'category' => view('models.category._form', [
                'action' => route('categories.store'),
                'category' => null,
            ]),
            'supplier' => view('models.suppliers.form', [
                'action' => route('suppliers.store'),
                'supplier' => null,
            ]),
            'product' => view('models.products.form', [
                'categories' => Category::all(['id', 'name']),
                'action' => route('products.store'),
                'isOffcanvas' => true,
                'product' => null,
                'productStock' => null,
            ]),
            'supply' => view('models.supplies._form', [
                'action' => route('supplies.store'),
                'isOffcanvas' => true,
                'supply' => null,
            ]),
            default => response()->json([
                'error' => 'Tipo de formulario no válido.',
            ], HttpStatus::BAD_REQUEST),
        };
    }
}
