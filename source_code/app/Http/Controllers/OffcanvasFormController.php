<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

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
                'isOffcanvas' => true,
                'category' => null,
            ]),
            'supplier' => view('models.suppliers._form', [
                'action' => route('suppliers.store'),
                'isOffcanvas' => true,
                'supplier' => null,
            ]),
            'product' => view('models.products._form', [
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
            ], HttpStatus::HTTP_BAD_REQUEST),
        };
    }
}
