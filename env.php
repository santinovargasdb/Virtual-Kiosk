<?php
/**
 * Configuración de entorno y credenciales para el Kiosco Virtual / Coffee Shop (Equipo 8).
 * Carga automática de variables desde el archivo .env mediante vlucas/phpdotenv (Composer).
 * Si no existe vendor/, el sistema funciona igual con los valores por defecto de XAMPP.
 */

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
    if (class_exists('Dotenv\Dotenv')) {
        // Carga segura sin lanzar excepciones si no existe el archivo .env
        $dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__);
        $dotenv->safeLoad();
    }
}

// Función helper para obtener variables de entorno
function getEnvVal(string $key, string $default = ''): string {
    $val = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    return ($val !== false && $val !== null && $val !== '') ? (string)$val : $default;
}

// Base de Datos MySQL (XAMPP por defecto: host=127.0.0.1, user=root, pass="")
define('DB_HOST', getEnvVal('DB_HOST', '127.0.0.1'));
define('DB_PORT', getEnvVal('DB_PORT', '3306'));
define('DB_NAME', getEnvVal('DB_NAME', 'kiosco_online'));
define('DB_USER', getEnvVal('DB_USER', 'root'));
define('DB_PASS', getEnvVal('DB_PASS', ''));

// Mercado Pago Credentials (configurar en el archivo .env — nunca commitear tokens reales)
define('MP_ACCESS_TOKEN', getEnvVal('MP_ACCESS_TOKEN', ''));
define('MP_PUBLIC_KEY', getEnvVal('MP_PUBLIC_KEY', ''));

// URL Base del proyecto (sanitizada sin comillas ni barras al final).
// Si no está definida en .env, se autodetecta según la carpeta dentro de htdocs.
$rawBaseUrl = getEnvVal('BASE_URL');
if (!$rawBaseUrl) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    // Autodetectar la ruta del proyecto relativa al DocumentRoot de Apache
    $docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
    $projDir = str_replace('\\', '/', __DIR__);
    $relPath = ($docRoot !== '' && strpos($projDir, $docRoot) === 0)
        ? substr($projDir, strlen($docRoot))
        : '';

    $rawBaseUrl = $protocol . "://" . $host . $relPath;
}

$cleanBaseUrl = trim($rawBaseUrl, "\"' \t\n\r\0\x0B/");
if (!preg_match('/^https?:\/\//i', $cleanBaseUrl)) {
    $cleanBaseUrl = 'http://' . $cleanBaseUrl;
}

define('BASE_URL', $cleanBaseUrl);
