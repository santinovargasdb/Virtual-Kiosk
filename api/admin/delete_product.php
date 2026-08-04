<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

// Exigir autenticación de administrador
requireAdminAuth();

if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'DELETE'])) {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
    exit;
}

try {
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true) ?: $_POST;

    $id = (int)($data['id'] ?? $_GET['id'] ?? 0);

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'ID de producto inválido.']);
        exit;
    }

    $db = Database::getConnection();
    
    $stmtCheck = $db->prepare("SELECT nombre FROM productos WHERE id = ?");
    $stmtCheck->bind_param("i", $id);
    $stmtCheck->execute();
    $resCheck = $stmtCheck->get_result();
    $prod = $resCheck->fetch_assoc();

    if (!$prod) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'El producto especificado no existe.']);
        exit;
    }

    $stmt = $db->prepare("DELETE FROM productos WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    echo json_encode([
        'status' => 'success',
        'message' => "¡Producto '{$prod['nombre']}' eliminado correctamente!"
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error al eliminar producto (MySQLi): ' . $e->getMessage()
    ]);
}
