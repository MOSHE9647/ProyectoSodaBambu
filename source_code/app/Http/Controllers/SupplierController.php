<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index() //muestra todos los registros
    {
        $data = Supplier::all();
        return view('suppliers.index', compact('data'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $rol = 'create';
        $item = new Supplier(); //objeto vacio para evitar errores en la vista
        return view('suppliers.create', compact('item', 'rol'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) //recibe los datos del formulario de la vista suppliers.create
    {
        $item = new Supplier();
        $item->name = $request->name;
        $item->phone = $request->phone;
        $item->email = $request->email;
        $item->save(); //guarda el registro en la base de datos

        return redirect()->route('suppliers.show', $item); //redirecciona a la vista suppliers.show con el id del registro recien creado
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $item = Supplier::find($id); //busca el registro por su id
        return view('suppliers.show', compact('item'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $item = Supplier::find($id); //busca el registro por su id
        $rol = 'edit';
        return view('suppliers.create', compact('rol', 'item'));//redirecciona a la vista suppliers.create con el id del registro a editar
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $item = Supplier::find($id); //busca el registro por su id
        $item->name = $request->name;
        $item->phone = $request->phone;
        $item->email = $request->email;
        $item->save(); //guarda el registro en la base de datos

        return redirect()->route('suppliers.edit', $item); //redirecciona a la vista suppliers.edit con el id del registro recien editado
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $item = Supplier::find($id); //busca el registro por su id   
        $item->delete(); //elimina el registro de la base de datos
        return redirect()->route('suppliers.index'); 
    }
}
