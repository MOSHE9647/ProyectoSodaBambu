<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Category;
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
	 * @return Factory|View|JsonResponse|\Illuminate\View\View
	 * @throws Exception
	 */
	public function index(Request $request)
	{
		if ($request->ajax()) {
			return DataTables::of(Product::with('category')->select('products.*'))->toJson();
		}

		return view('models.products.index');
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Factory|View|\Illuminate\View\View
	 */
	public function create()
	{
		$categories = Category::orderBy('name')->get();

		return view('models.products.create', compact('categories'));
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @param ProductRequest $request
	 * @return RedirectResponse
	 * @throws Throwable
	 */
	public function store(ProductRequest $request)
	{
		$productData = $request->validated();

		$product = Product::withTrashed()
			->where('barcode', $productData['barcode'])
			->first();
		$message = 'Producto creado exitosamente.';

		if ($product?->trashed()) {
			$product->restore();
			$product->update($productData);
			$message = 'Producto restaurado y actualizado exitosamente.';
		} else {
			Product::create($productData);
		}

		return redirect()->route('products.index')->with('success', $message);
	}

	/**
	 * Display the specified resource.
	 *
	 * @param Product $product
	 * @return Factory|View|\Illuminate\View\View
	 */
	public function show(Product $product)
	{
		$product->load('category');

		return view('models.products.show', compact('product'));
	}

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param Product $product
	 * @return Factory|View|\Illuminate\View\View
	 */
	public function edit(Product $product)
	{
		$categories = Category::orderBy('name')->get();

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
		$productData = $request->validated();

		$product->update($productData);

		return redirect()->route('products.index')->with('success', 'Producto actualizado exitosamente.');
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

		return redirect()->route('products.index')->with('success', 'Producto eliminado exitosamente.');
	}
}
