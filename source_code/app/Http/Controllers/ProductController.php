<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Product;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;
use Yajra\DataTables\Facades\DataTables;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return Factory|View|JsonResponse
     * @throws Exception
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return DataTables::of(Product::with('category'))->toJson();
        }

        return view('models.products.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Factory|View
     */
    public function create()
    {
        $categories = \App\Models\Category::all();
        return view('models.products.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param ProductRequest $request
     * @return RedirectResponse|JsonResponse
     * @throws Throwable
     */
    public function store(ProductRequest $request)
    {
        $product = Product::create($request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Producto creado correctamente.',
                'product' => [
                    'id'         => $product->id,
                    'name'       => $product->name,
                    'sale_price' => $product->sale_price,
                ]
            ]);
        }

        return redirect()->route('products.index')->with('success', 'Producto creado correctamente.');
    }

    /**
     * Display the specified resource.
     *
     * @param Product $product
     * @return Factory|View
     */
    public function show(Product $product)
    {
        return view('models.products.show', compact('product'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Product $product
     * @return Factory|View
     */
    public function edit(Product $product)
    {
        $categories = \App\Models\Category::all();
        return view('models.products.edit', compact('product', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param ProductRequest $request
     * @param Product $product
     * @return RedirectResponse
     * @throws Throwable
     */
    public function update(ProductRequest $request, Product $product)
    {
        $product->update($request->validated());

        return redirect()->route('products.index')->with('success', 'Producto actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Product $product
     * @return RedirectResponse
     * @throws Throwable
     */
    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('products.index')->with('success', 'Producto eliminado correctamente.');
    }
}