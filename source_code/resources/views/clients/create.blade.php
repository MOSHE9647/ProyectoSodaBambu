<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Cliente</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; }
        input[type="text"], input[type="email"] { width: 300px; padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
        .error { color: red; font-size: 0.9em; margin-top: 5px; }
        .btn-back { display: block; margin-bottom: 20px; }
    </style>
</head>
<body>

    <h1>Crear Nuevo Cliente</h1>

    <a href="{{ route('clients.index') }}" class="btn-back">← Volver a la Lista de Clientes</a>

    <form action="{{ route('clients.store') }}" method="POST">
        @csrf
        
        <div class="form-group">
            <label for="first_name">Nombre:</label>
            <input type="text" id="first_name" name="first_name" value="{{ old('first_name') }}" required>
            @error('first_name')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="last_name">Apellido:</label>
            <input type="text" id="last_name" name="last_name" value="{{ old('last_name') }}" required>
            @error('last_name')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required>
            @error('email')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="phone">Teléfono (Opcional):</label>
            <input type="text" id="phone" name="phone" value="{{ old('phone') }}">
            @error('phone')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" style="padding: 10px 15px; background-color: green; color: white; border: none; border-radius: 4px; cursor: pointer;">
            Guardar Cliente
        </button>
    </form>

</body>
</html>