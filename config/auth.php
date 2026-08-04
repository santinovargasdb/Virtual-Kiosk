<?php
require_once __DIR__ . '/../env.php';

if (session_status() === PHP_SESSION_NONE) {
    if (!headers_sent()) {
        session_set_cookie_params([
            'lifetime' => 86400, // 24 horas
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
    session_start();
}

/**
 * Verifica si el usuario actual tiene una sesión de administrador activa.
 * @return bool
 */
function isAdminAuthenticated(): bool {
    return !empty($_SESSION['admin_user']) && is_array($_SESSION['admin_user']);
}

/**
 * Middleware para requerir autenticación de administrador.
 * Redirige o responde con error HTTP 401 si no está autenticado.
 */
function requireAdminAuth(): void {
    if (!isAdminAuthenticated()) {
        $isApi = stristr($_SERVER['REQUEST_URI'] ?? '', '/api/');
        if ($isApi) {
            header('Content-Type: application/json; charset=utf-8', true, 401);
            echo json_encode([
                'status' => 'error',
                'message' => 'Acceso denegado. Requiere autenticación de administrador.'
            ]);
            exit;
        }
        
        $loginUrl = BASE_URL . '/admin/login.php';
        header("Location: {$loginUrl}");
        exit;
    }
}
