<?php

declare(strict_types=1);

$greenBg = "\033[42m";
$blueBg = "\033[44m";
$redBg = "\033[41m";
$reset = "\033[0m";

function info(string $message): void
{
    global $blueBg, $reset;
    echo PHP_EOL."{$blueBg} {$message} {$reset}".PHP_EOL.PHP_EOL;
}

function success(string $message): void
{
    global $greenBg, $reset;
    echo "{$greenBg} {$message} {$reset}" . PHP_EOL;
}

function errorOut(string $message, int $code = 1): void
{
    global $redBg, $reset;
    fwrite(STDERR, "{$redBg} {$message} {$reset}" . PHP_EOL);
    exit($code);
}

function runCommand(string $command, string $label): void
{
    info("{$label}...");
    passthru($command, $exitCode);
    if ($exitCode !== 0) {
        errorOut("Error: $label (exit code $exitCode)");
    }
    success("Comando \"{$command}\" ejecutado correctamente.");
}

function argvContainOption(string $option): bool
{
    global $argv;
    return in_array($option, $argv);
}

function getArgument(string $option, string $default = ''): string
{
    global $argv;
    $key = array_search($option, $argv);
    return ($key !== false && isset($argv[$key + 1])) ? $argv[$key + 1] : $default;
}

function cleanBootstrapCache(string $path): void
{
    if (!is_dir($path)) {
        return;
    }

    $items = scandir($path);
    if ($items === false) {
        errorOut("Error: No se pudo leer {$path}");
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..' || $item === '.gitignore') {
            continue;
        }
        $fullPath = $path . DIRECTORY_SEPARATOR . $item;
        if (is_dir($fullPath)) {
            deleteDirectory($fullPath);
        } else {
            if (!unlink($fullPath)) {
                errorOut("Error: No se pudo eliminar {$fullPath}");
            }
        }
    }
}

function deleteDirectory(string $path): void
{
    if (! is_dir($path)) {
        return;
    }

    info("Eliminando directorio: $path");

    $path = realpath($path);
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        exec(sprintf('rd /s /q "%s"', $path));
    } else {
        exec(sprintf('rm -rf "%s"', $path));
    }

    if (is_dir($path)) {
        errorOut("Error: No se pudo eliminar el directorio {$path} (posiblemente bloqueado por otro proceso).");
    }
}

function generateStorageLink(): void
{
    info("Generando enlace simbólico para almacenamiento...");

    $linkPath = __DIR__ . '/../public/storage';
    if (is_link($linkPath) || file_exists($linkPath)) {
        info("El enlace simbólico [public/storage] ya existe. Omitiendo.");
        return;
    }

    runCommand('php artisan storage:link', "Creando enlace simbólico (storage:link)");
}

function configureSQLite(): bool
{
    info("Configurando SQLite como base de datos...");

    $databaseDir = __DIR__.'/../database';
    $databaseFile = "{$databaseDir}/database.sqlite";

    // Crear el archivo SQLite si no existe
    if (! file_exists($databaseFile)) {
        info("Creando archivo de base de datos SQLite (database/database.sqlite)");
        if (touch($databaseFile) === false) {
            errorOut("Error: No se pudo crear el archivo database.sqlite");
        }
        success("Archivo database.sqlite creado correctamente.");
    } else {
        info("El archivo database.sqlite ya existe. Omitiendo.");
    }

    $envPath = __DIR__.'/../.env';
    if (! file_exists($envPath)) {
        errorOut("Error: No se encontró el archivo .env en la raíz del proyecto.");
    }

    $envContent = file_get_contents($envPath);
    if ($envContent === false) {
        errorOut("Error: No se pudo leer el archivo .env.");
    }

    $newEnvContent = preg_replace('/^DB_CONNECTION=.*$/m', 'DB_CONNECTION=sqlite', $envContent);
    $newEnvContent = preg_replace('/^DB_HOST=.*$/m', 'DB_HOST=', $newEnvContent);
    $newEnvContent = preg_replace('/^DB_PORT=.*$/m', 'DB_PORT=', $newEnvContent);
    $newEnvContent = preg_replace('/^DB_DATABASE=.*$/m', 'DB_DATABASE=', $newEnvContent);
    $newEnvContent = preg_replace('/^DB_USERNAME=.*$/m', 'DB_USERNAME=', $newEnvContent);
    $newEnvContent = preg_replace('/^DB_PASSWORD=.*$/m', 'DB_PASSWORD=', $newEnvContent);

    if (file_put_contents($envPath, $newEnvContent) === false) {
        errorOut("Error: No se pudo escribir en el archivo .env.");
    }

    info("Archivo .env actualizado para usar SQLite.");
    return true;
}

function configureMySQL(): bool
{
    info("Configurando MySQL como base de datos...");
    $envPath = __DIR__.'/../.env';
    
    if (! file_exists($envPath)) {
        errorOut("Error: No se encontró el archivo .env en la raíz del proyecto.");
    }

    $envContent = file_get_contents($envPath);
    if ($envContent === false) {
        errorOut("Error: No se pudo leer el archivo .env.");
    }

    $host = getArgument('--db-host', '127.0.0.1');
    $port = getArgument('--db-port', '3306');
    $database = getArgument('--db-name', 'laravel_db');
    $username = getArgument('--db-user', 'root');
    $password = getArgument('--db-password', '');


    // Actualizamos el contenido del .env
    $newEnvContent = preg_replace('/^DB_CONNECTION=.*$/m', 'DB_CONNECTION=mysql', $envContent);
    $newEnvContent = preg_replace('/^DB_HOST=.*$/m', "DB_HOST={$host}", $newEnvContent);
    $newEnvContent = preg_replace('/^DB_PORT=.*$/m', "DB_PORT={$port}", $newEnvContent);
    $newEnvContent = preg_replace('/^DB_DATABASE=.*$/m', "DB_DATABASE={$database}", $newEnvContent);
    $newEnvContent = preg_replace('/^DB_USERNAME=.*$/m', "DB_USERNAME={$username}", $newEnvContent);
    $newEnvContent = preg_replace('/^DB_PASSWORD=.*$/m', "DB_PASSWORD={$password}", $newEnvContent);

    if (file_put_contents($envPath, $newEnvContent) === false) {
        errorOut("Error: No se pudo escribir en el archivo .env.");
    }

    info("Archivo .env actualizado para usar MySQL.");
    return true;
}

function setDatabase(): void
{
    if (argvContainOption('--no-db')) {
        info("No se configurará ninguna base de datos.");
        return;
    }

    if (argvContainOption('--db:sqlite')) {
        configureSQLite();
    } elseif (argvContainOption('--db:mysql')) {
        configureMySQL();
    } else {
        errorOut("Error: No se especificó un tipo de base de datos válido. Use --db:sqlite o --db:mysql.");
    }
}

function askToRunMigrations(): void
{
    if (
        argvContainOption('--migrate') || 
        argvContainOption('--migrate:seed') || 
        argvContainOption('--migrate:fresh') || 
        argvContainOption('--migrate:fresh') && argvContainOption('--seed')
    ) {
        $migrateCommand = 'php artisan migrate';
        if (argvContainOption('--migrate:fresh')) {
            $migrateCommand .= ':fresh';
        }
        if (argvContainOption('--migrate:seed') || (argvContainOption('--migrate:fresh') && argvContainOption('--seed'))) {
            $migrateCommand .= ' --seed';
        }
        runCommand($migrateCommand, "Ejecutando migraciones");
    } else {
        info("No se ejecutarán las migraciones.");
    }
}

// ---------------- Manejo de opciones de línea de comandos ----------------

if (in_array('--help', $argv) || in_array('-h', $argv)) {
    info("Ayuda del script de configuración de espacio de trabajo:");
    info("Opciones disponibles:");
    info("  --db:sqlite               Configura la base de datos para SQLite.");
    info("  --db:mysql                Configura la base de datos para MySQL.");
    info("  --migrate                    Ejecuta las migraciones.");
    info("  --migrate:seed               Ejecuta las migraciones y luego los seeders.");
    info("  --migrate:fresh              Ejecuta las migraciones en modo \"fresh\".");
    info("  --migrate:fresh --seed       Ejecuta las migraciones en modo \"fresh\" y luego los seeders.");
    info("  --help                              Muestra esta ayuda.");
    exit(0);
}

// ---------------- Lógica principal del script ----------------

info("Configurando espacio de trabajo.");

cleanBootstrapCache(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'cache');
success('Carpeta "bootstrap/cache" limpiadada correctamente.');

deleteDirectory(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'node_modules');
success('Carpeta "node_modules" eliminada correctamente.');

deleteDirectory(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'vendor');
success('Carpeta "vendor" eliminada correctamente.');

runCommand('composer update -v', "Actualizando dependencias con \"composer update\"");

setDatabase();
runCommand('composer setup -v', "Configurando el entorno con \"composer setup\"");

askToRunMigrations();
generateStorageLink();
success("Espacio de Trabajo Configurado Correctamente.");
