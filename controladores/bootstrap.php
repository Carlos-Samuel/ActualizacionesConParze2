<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

/**
 * Carga variables de entorno desde .env
 */
$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

/**
 * Helper: obtiene variable de entorno o default (si se provee).
 */
function env(string $key, ?string $default = null): ?string {
    if (array_key_exists($key, $_ENV)) return $_ENV[$key];
    if (array_key_exists($key, $_SERVER)) return $_SERVER[$key];
    return $default;
}

/**
 * Helper: obliga a que exista la variable; lanza excepción si falta.
 */
function env_required(string $key): string {
    $val = env($key, null);
    if ($val === null || $val === '') {
        throw new RuntimeException("Falta variable de entorno requerida: {$key}");
    }
    return $val;
}

/**
 * Resuelve rutas: si es absoluta, la retorna; si es relativa, la hace relativa a la raíz del proyecto.
 */
function project_path(string $path): string {
    // raíz = carpeta superior a /config
    $root = dirname(__DIR__);
    // Windows o Unix: si empieza con letra:\ o con /, la consideramos absoluta
    $isAbsoluteWin = preg_match('/^[A-Za-z]:\\\\/', $path) === 1;
    $isAbsoluteUnix = str_starts_with($path, '/');
    if ($isAbsoluteWin || $isAbsoluteUnix) {
        return $path;
    }
    return rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
}


define('DBF_PATH', project_path(env_required('DBF_PATH')));
define('DBF_ENCODING', env('DBF_ENCODING', 'CP1252'));
define('EXPORT_DIR', project_path(env('EXPORT_DIR', 'exports')));
define('CSV_DELIM', env('CSV_DELIM', ','));
