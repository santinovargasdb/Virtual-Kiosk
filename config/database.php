<?php
require_once __DIR__ . '/../env.php';

class Database {
    private static ?mysqli $instance = null;

    /**
     * Obtiene una instancia única de conexión MySQLi (Orientada a Objetos) a MySQL.
     * @return mysqli
     */
    public static function getConnection(): mysqli {
        if (self::$instance === null) {
            // Habilitar reporte de excepciones en MySQLi
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            try {
                self::$instance = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, (int)DB_PORT);
                self::$instance->set_charset("utf8mb4");
            } catch (mysqli_sql_exception $e) {
                if (stristr($_SERVER['REQUEST_URI'] ?? '', '/api/')) {
                    header('Content-Type: application/json', true, 500);
                    echo json_encode([
                        'error' => 'Error de conexión a la base de datos (MySQLi): ' . $e->getMessage()
                    ]);
                    exit;
                }
                die("Error crítico de Base de Datos (MySQLi): " . $e->getMessage());
            }
        }
        return self::$instance;
    }
}
