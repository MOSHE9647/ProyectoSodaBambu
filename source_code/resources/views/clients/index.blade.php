<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lista de Clientes</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #f2f2f2; }
        .alert-success { background-color: #d4edda; color: #155724; padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .btn-create { display: inline-block; padding: 10px 15px; background-color: #007bff; color: white; text-decoration: none; border-radius: 4px; margin-bottom: 20px; }
        .btn-edit { color: blue; text-decoration: none; margin-right: 10px; }
        .btn-delete { background: none; border: none; color: red; cursor: pointer; padding: 0; }
    </style>
</head>
<body>

    <h1>Gestión de Clientes</h1>

    <a href="{{ route('clients.create') }}" class="btn-create">
        Crear Nuevo Cliente
    </a>
    
    @if(session('success'))
        <div class="alert-success">
            {{ session('success') }}
        </div>
    @endif

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Apellido</th>
                <th>Email</th>
                <th>Teléfono</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($clients as $client)
                <tr>
                    <td>{{ $client->id }}</td>
                    <td>{{ $client->first_name }}</td>
                    <td>{{ $client->last_name }}</td>
                    <td>{{ $client->email }}</td>
                    <td>{{ $client->phone ?? 'N/A' }}</td>
                    <td>
                        <a href="{{ route('clients.edit', $client) }}" class="btn-edit">Editar</a>
                        
                        <form action="{{ route('clients.destroy', $client) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-delete" 
                                onclick="return confirm('¿Estás seguro de que quieres eliminar a {{ $client->first_name }}?')">
                                Eliminar
                            </button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>