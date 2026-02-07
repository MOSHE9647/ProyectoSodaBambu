<?php

declare(strict_types=1);

$greenBg = "\033[42m";
$redBg = "\033[41m";
$reset = "\033[0m";

function info(string $message): void
{
    echo $message . PHP_EOL;
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
    info("{$label}...\n");
    passthru($command, $exitCode);
    if ($exitCode !== 0) {
        errorOut("Error: $label (exit code $exitCode)");
    }
    success("Comando \"{$command}\" ejecutado correctamente.");
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

    info("\nEliminando directorio: $path");

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
    info("\nGenerando enlace simbólico para almacenamiento...\n");

    $linkPath = __DIR__ . '/../public/storage';
    if (is_link($linkPath) || file_exists($linkPath)) {
        info("El enlace simbólico [public/storage] ya existe. Omitiendo.\n");
        return;
    }

    runCommand('php artisan storage:link', "Creando enlace simbólico (storage:link)");
}

info("Configurando espacio de trabajo.\n");

cleanBootstrapCache(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'cache');
success('Carpeta "bootstrap/cache" limpiadada correctamente.');

deleteDirectory(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'node_modules');
success('Carpeta "node_modules" eliminada correctamente.');

deleteDirectory(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'vendor');
success('Carpeta "vendor" eliminada correctamente.');

runCommand('composer setup', "\nEjecutando comando \"composer setup\"");
runCommand('php artisan migrate:fresh --seed', "\nEjecutando migraciones y seeders (migrate:fresh --seed)");

generateStorageLink();
success("Espacio de Trabajo Configurado Correctamente.");
