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
     * @return Factory|View|JsonResponse|\Illuminate\View\View
     *
     * @throws Exception
     */
    public function index(Request $request)
    {

       if ($request->has('simple') && $request->wantsJson()) {
            return response()->json(Category::select('id', 'name')->get());
        }

        // Handle AJAX request for DataTables
        if ($request->ajax() && $request->wantsJson()) {
            // Use query builder to keep DataTables server-side and memory efficient
            return DataTables::of(Category::query())->toJson();
        }

        // For non-AJAX requests, return the view
        return view('models.category.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Factory|View|\Illuminate\View\View
     */
    public function create()
    {
        return view('models.category.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return RedirectResponse
     *
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
            $category = Category::create($categoryData);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => $message,
                'category' => [
                    'id' => $category->id,
                    'name' => $category->name,
                ],
            ]);
        }

        return redirect()->route('categories.index')
            ->with('success', $message);
    }

    /**
     * Display the specified resource.
     *
     * @return Factory|View|\Illuminate\View\View
     */
    public function show(Category $category)
    {
        $resource = CategoryResource::make($category);

        return view('models.category.show', ['category' => $resource]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Factory|View|\Illuminate\View\View
     */
    public function edit(Category $category)
    {
        return view('models.category.edit', compact('category'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @return RedirectResponse
     *
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
     * @return RedirectResponse
     *
     * @throws Throwable
     */
    public function destroy(Category $category)
    {
        $category->delete();

        return redirect()->back()
            ->with('success', 'Categoría eliminada correctamente.');
    }
}
