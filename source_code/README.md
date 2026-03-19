# Sistema de Gestión Interna - Soda El Bambú

## 📋 Descripción del Proyecto

Esta parte del repositorio contiene el código fuente del sistema de gestión interna para Soda y Restaurante El Bambú. El sistema centraliza la operación del restaurante para optimizar ventas, inventario, reportes, reservas y administración interna.

## 🌐 Proyecto Desplegado

Puedes acceder a la versión en producción del sistema en:

**🔗 [https://proyecto-soda-bambu.vercel.app](https://proyecto-soda-bambu.vercel.app)**

> **Nota:** El sistema se encuentra desplegado en Vercel con una base de datos MySQL en TiDB Cloud.

## 💡 ¿Cómo Funciona el Sistema?

El sistema está diseñado como una aplicación web progresiva (PWA) que permite gestionar todos los procesos internos del restaurante de forma centralizada:

### Arquitectura General
- **Backend**: Laravel procesa todas las peticiones, valida datos y gestiona la lógica de negocio.
- **Frontend**: Blade templates + Bootstrap 5 proporcionan una interfaz responsive.
- **Base de Datos**: MySQL/SQLite almacena la información del negocio.
- **Autenticación**: Laravel Fortify maneja el acceso seguro con roles y permisos.

### Flujo de Trabajo Típico
1. El usuario se autentica en el sistema según su rol (Administrador/Empleado).
2. Accede a los módulos correspondientes a sus permisos.
3. Realiza operaciones CRUD sobre las entidades del negocio.
4. El sistema valida, procesa y almacena la información.
5. Genera reportes y estadísticas en tiempo real.

## 📖 Guía de Uso

### Primer Acceso

1. Accede al sistema a través del [enlace desplegado](https://proyecto-soda-bambu.vercel.app) o en tu entorno local (`http://localhost:8000`).
2. Inicia sesión con tus credenciales.
3. Verifica tu correo electrónico (si es requerido).

> **Nota:** La verificación del correo se realiza la primera vez que el usuario inicia sesión (después de que el Administrador lo haya creado).

### Usuarios de Prueba

Para probar el sistema con datos de ejemplo durante el desarrollo:

> ⚠️ **Importante:** Las credenciales que se muestran a continuación son **únicamente para propósitos de prueba** mientras el proyecto se encuentra en fase de desarrollo. Una vez que el proyecto esté completado, el cliente proporcionará credenciales reales que reemplazarán estos datos de prueba.

**Credenciales de prueba (temporales):**
- **Administrador**: 
  - Email: `admin@admin.com`
  - Contraseña: `admin1234`
- **Empleado**: 
  - Email: `juan.perez@sodabambu.com`
  - Contraseña: `password123`

## 🔧 Tecnologías

### Backend
- **PHP**: ^8.4
- **Framework**: Laravel ^12.x (PHP)
- **Base de datos**: MySQL/SQLite
- **Autenticación**: Laravel Fortify

### Frontend
- **Templates**: Laravel Blade
- **CSS Framework**: Bootstrap 5
- **JavaScript**: Vanilla JS + jQuery

### Deployment
- **Hosting de la aplicación**: Vercel
- **Servidor de base de datos**: TiDB Cloud

### Herramientas de Desarrollo
- **Control de versiones**: Git + Gitflow
- **Gestor de dependencias PHP**: Composer
- **Gestor de dependencias JS**: npm
- **Entorno de desarrollo**: XAMPP / Laragon
- **IDE recomendado**: Visual Studio Code / PHPStorm

## 🏗️ Estructura de Directorios

```
source_code/
├── app/                      # Lógica de negocio
│   ├── Http/Controllers/     # Controladores
│   ├── Models/               # Modelos Eloquent
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
│   └── web.php               # Rutas web
└── tests/                    # Tests automatizados
```

## 🚀 Configuración Inicial

Para configurar el entorno de desarrollo, puedes usar los scripts de Composer incluidos en el proyecto. Estos scripts automatizan la instalación de dependencias, configuración de entorno y ejecución de migraciones. Los pasos para configurar el entorno usando los scripts de Composer se encuentran detallados en la sección "Instalación y Configuración" del [README Principal](../README.md#-instalación-y-configuración).

Si no deseas usar los scripts de Composer para configurar el entorno, puedes seguir estos pasos manualmente:

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
# Modo producción
npm run build
```

## ⚙️ Comandos Útiles

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

## 📐 Convenciones de Código

- Seguir PSR-12 para PHP
- Nombres de clases en `PascalCase`
- Nombres de métodos en `camelCase`
- Nombres de tablas en plural y `snake_case`
- Commits descriptivos en **inglés** (según estándares del proyecto)
- Usar Pull Requests para todos los cambios (ver [README principal](../README.md#-flujo-de-trabajo-con-git-y-gitflow))

## 🔐 Variables de Entorno Importantes

| Variable    | Descripción                    |
|-------------|--------------------------------|
| `APP_NAME`  | Nombre de la aplicación        |
| `APP_ENV`   | Entorno (local/production)     |
| `APP_DEBUG` | Modo debug (true/false)        |
| `DB_*`      | Configuración de base de datos |

## 🚫 Archivos Ignorados (Git)

Consulta el archivo [.gitignore](.gitignore) para ver qué archivos están excluidos del control de versiones.

## 🔒 Seguridad

- **Nunca** hacer commit del archivo `.env`
- Mantener dependencias actualizadas
- Usar validación en todos los inputs
- Sanitizar datos antes de queries

## 🆘 Soporte

Para dudas técnicas sobre el código, contactar con el equipo de desarrollo (ver sección [Equipo de Desarrollo](../README.md#-equipo-de-desarrollo) dentro del archivo **README.md** del directorio principal).

**© 2025–2026 Equipo de Desarrollo, Universidad Nacional de Costa Rica. Todos los derechos reservados.**

---

<img src="../assets/images/logo-una.png" alt="Logo Universidad Nacional de Costa Rica" align="left" width="85"/>

**Universidad Nacional de Costa Rica**  
**Escuela de Informática**  
**Ingeniería en Sistemas II**  
**I Ciclo 2026**