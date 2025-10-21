<!DOCTYPE html>
<html>
<head>
    <title>Editar Cliente</title>
    <style>
        body { font-family: sans-serif; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; }
        input[type="text"], input[type="email"] { width: 300px; padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
        .error { color: red; font-size: 0.9em; margin-top: 5px; }
    </style>
</head>
<body>

    <h1>Editar Cliente: {{ $client->first_name }} {{ $client->last_name }}</h1>

    <a href="{{ route('clients.index') }}">← Volver a la Lista de Clientes</a>

    <form action="{{ route('clients.update', $client) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="form-group">
            <label for="first_name">Nombre:</label>
            <input type="text" id="first_name" name="first_name" 
                   value="{{ old('first_name', $client->first_name) }}" required>
            @error('first_name')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="last_name">Apellido:</label>
            <input type="text" id="last_name" name="last_name" 
                   value="{{ old('last_name', $client->last_name) }}" required>
            @error('last_name')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" 
                   value="{{ old('email', $client->email) }}" required>
            @error('email')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="phone">Teléfono (Opcional):</label>
            <input type="text" id="phone" name="phone" 
                   value="{{ old('phone', $client->phone) }}">
            @error('phone')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" style="padding: 10px 15px; background-color: orange; color: white; border: none; border-radius: 4px; cursor: pointer;">
            Actualizar Cliente
        </button>
    </form>

</body>
</html>