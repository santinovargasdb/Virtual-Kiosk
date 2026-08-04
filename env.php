<?php
/**
 * Configuración de entorno y credenciales para el Kiosco Online.
 * Carga automática de variables desde el archivo .env mediante vlucas/phpdotenv (Composer).
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

// Mercado Pago Credentials
define('MP_ACCESS_TOKEN', getEnvVal('MP_ACCESS_TOKEN', 'APP_USR-3299995297656254-072823-1b60f780d4ceb9b258bfc28dd699b3ef-1180343997'));
define('MP_PUBLIC_KEY', getEnvVal('MP_PUBLIC_KEY', 'APP_USR-8d94ae1c-8b88-428c-b480-141ecc202f46'));

// URL Base del proyecto en XAMPP (sanitizada sin comillas ni barras al final)
$rawBaseUrl = getEnvVal('BASE_URL');
if (!$rawBaseUrl) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $rawBaseUrl = $protocol . "://" . $host . "/2026/Antigravity";
}

$cleanBaseUrl = trim($rawBaseUrl, "\"' \t\n\r\0\x0B/");
if (!preg_match('/^https?:\/\//i', $cleanBaseUrl)) {
    $cleanBaseUrl = 'http://' . $cleanBaseUrl;
}

define('BASE_URL', $cleanBaseUrl);


