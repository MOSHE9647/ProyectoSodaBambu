<?php

namespace App\Http\Controllers;

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
     * @return Factory|View|JsonResponse|\Illuminate\View\View
     * @throws Exception
     */
    public function index(Request $request)
    {
        // Fetch categories
        $categories = Category::all();
        $resource = CategoryResource::collection($categories);

        // Handle AJAX request for DataTables
        if ($request->ajax()) {
            return DataTables::of($resource)->make();
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
     * @param Request $request
     * @return RedirectResponse
     * @throws Throwable
     */
    public function store(Request $request)
    {   
        $categoryData = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'description' => 'nullable|string|max:255',
        ]);

        Category::create($categoryData);

        return redirect()->route('categories.index')
            ->with('success', 'Categoría creada correctamente.');
    }

    /**
     * Display the specified resource.
     *
     * @param Category $category
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
     * @param Category $category
     * @return Factory|View|\Illuminate\View\View
     */
    public function edit(Category $category)
    {
        return view('models.category.edit', compact('category'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Category $category
     * @return RedirectResponse
     * @throws Throwable
     */
    public function update(Request $request, Category $category)
    {
        $categoryData = $request->validate([
            // Ensure the name is unique except for the current category
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
            'description' => 'nullable|string|max:255',
        ]);

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