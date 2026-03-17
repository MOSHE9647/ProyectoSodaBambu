<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;
use Yajra\DataTables\DataTables;

class CategoryController extends Controller
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
        // Lista simple para selects (no requiere ajax estricto)
        if ($request->has('simple') && $request->wantsJson()) {
            return response()->json(Category::select('id', 'name')->get());
        }

        // DataTables
        if ($request->ajax() && $request->wantsJson()) {
            return DataTables::of(Category::query())->toJson();
        }

        return view('models.category.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Factory|View
     */
    public function create()
    {
        return view('models.category.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param CategoryRequest $request
     * @return RedirectResponse
     * @throws Throwable
     */
    public function store(CategoryRequest $request)
    {
        $categoryData = $request->validated();

        $category = Category::withTrashed()->where('name', $categoryData['name'])->first();
        $message = 'Categoría creada correctamente.';

        if ($category?->trashed()) {
            $category->restore();
            $category->update($categoryData);
            $message = 'Categoría restaurada y actualizada correctamente.';
        } else {
            Category::create($categoryData);
        }

        return redirect()->route('categories.index')
            ->with('success', $message);
    }

    /**
     * Display the specified resource.
     *
     * @param Category $category
     * @return Factory|View
     */
    public function show(Category $category)
    {
        $resource = CategoryResource::make($category);
        return view('models.category.show', ['category' => $resource]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Category $category
     * @return Factory|View
     */
    public function edit(Category $category)
    {
        return view('models.category.edit', compact('category'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param CategoryRequest $request
     * @param Category $category
     * @return RedirectResponse
     * @throws Throwable
     */
    public function update(CategoryRequest $request, Category $category)
    {
        $categoryData = $request->validated();

        $category->update($categoryData);

        return redirect()->route('categories.index')
            ->with('success', 'Categoría actualizada correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Category $category
     * @return RedirectResponse
     * @throws Throwable
     */
    public function destroy(Category $category)
    {
        $category->delete();

        return redirect()->back()
            ->with('success', 'Categoría eliminada correctamente.');
    }
}
