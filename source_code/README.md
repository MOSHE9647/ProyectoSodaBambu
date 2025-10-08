# Sistema de Gestión Integral - Soda El Bambú

## Descripción Técnica

Este es el código fuente del sistema de gestión integral desarrollado con Laravel para Soda y Restaurante El Bambú.

## Stack Tecnológico

- **PHP**: >= 8.2
- **Framework**: Laravel ^12.x
- **Base de datos**: MySQL/SQLite
- **Frontend**: Blade Templates, Bootstrap 5
- **Autenticación**: Laravel-UI/Bootstrap-Auth
- **Control de versiones**: Git + Gitflow

## Estructura de Directorios

```
source_code/
├── app/                      # Lógica de negocio
│   ├── Http/Controllers/     # Controladores
│   ├── Models/               # Modelos Eloquent
│   ├── Services/             # Servicios de negocio
│   └── ...
├── config/                   # Configuración
├── database/
│   ├── migrations/           # Migraciones de BD
│   ├── seeders/              # Datos de prueba
│   └── factories/            # Factories para testing
├── public/                   # Punto de entrada y assets públicos
├── resources/
│   ├── views/                # Plantillas Blade
│   ├── css/                  # Estilos
│   └── js/                   # JavaScript
├── routes/
│   ├── web.php               # Rutas web
│   └── api.php               # Rutas API
└── tests/                    # Tests automatizados
```

## Configuración Inicial

### 1. Instalación de Dependencias

```bash
# Instalar dependencias PHP
composer install

# Instalar dependencias JavaScript
npm install
```

### 2. Configuración de Entorno

```bash
# Copiar archivo de configuración
copy .env.example .env

# Generar clave de aplicación
php artisan key:generate

# Activar uso de storage público para archivos subidos
php artisan storage:link
```

### 3. Configurar Base de Datos

Edita el archivo `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=soda_bambu_db
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_contraseña
```

### 4. Ejecutar Migraciones

```bash
# Ejecutar migraciones
php artisan migrate

# Ejecutar migraciones con datos de prueba (opcional)
php artisan migrate --seed
```

### 5. Compilar Assets

```bash
# Modo desarrollo (con watch)
npm run dev

# Modo producción
npm run build
```

## Comandos Útiles

### Desarrollo

```bash
# Iniciar servidor de desarrollo
composer run dev

# Limpiar caché
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Ver rutas
php artisan route:list
```

### Base de Datos

```bash
# Crear nueva migración
php artisan make:migration create_nombre_tabla_table

# Crear modelo con migración
php artisan make:model NombreModelo -m

# Rollback última migración
php artisan migrate:rollback

# Refrescar BD con seeds
php artisan migrate:fresh --seed
```

### Testing

```bash
# Ejecutar tests
php artisan test

# Ejecutar tests específicos
php artisan test --filter NombreDelTest

# Ejecutar tests con cobertura
php artisan test --coverage
```

## Convenciones de Código

- Seguir PSR-12 para PHP
- Nombres de clases en `PascalCase`
- Nombres de métodos en `camelCase`
- Nombres de tablas en plural y `snake_case`
- Commits descriptivos en **inglés** (según estándares del proyecto)
- Usar Pull Requests para todos los cambios (ver [README principal](../README.md#-flujo-de-trabajo-con-git-y-gitflow))

## Variables de Entorno Importantes

| Variable    | Descripción                    |
|-------------|--------------------------------|
| `APP_NAME`  | Nombre de la aplicación        |
| `APP_ENV`   | Entorno (local/production)     |
| `APP_DEBUG` | Modo debug (true/false)        |
| `DB_*`      | Configuración de base de datos |

## Archivos Ignorados (Git)

Consulta el archivo [.gitignore](.gitignore) para ver qué archivos están excluidos del control de versiones.

## Seguridad

- **Nunca** hacer commit del archivo `.env`
- Mantener dependencias actualizadas
- Usar validación en todos los inputs
- Sanitizar datos antes de queries

## Soporte

Para dudas técnicas sobre el código, contactar al equipo de desarrollo (
ver [README principal](../README.md#-equipo-de-desarrollo))

---

**Parte del proyecto académico de Ingeniería en Sistemas - Universidad Nacional de Costa Rica**
