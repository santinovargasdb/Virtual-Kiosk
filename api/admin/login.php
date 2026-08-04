<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
    exit;
}

try {
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);

    $username = trim($data['username'] ?? $_POST['username'] ?? '');
    $password = trim($data['password'] ?? $_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Por favor ingresa usuario y contraseña.']);
        exit;
    }

    $db = Database::getConnection();

    // Asegurar que la tabla usuarios existe en MySQL
    $db->query("
        CREATE TABLE IF NOT EXISTS `usuarios` (
          `id` INT AUTO_INCREMENT PRIMARY KEY,
          `username` VARCHAR(50) NOT NULL UNIQUE,
          `password_hash` VARCHAR(255) NOT NULL,
          `nombre` VARCHAR(100) NOT NULL,
          `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // Buscar usuario por username
    $stmt = $db->prepare("SELECT id, username, password_hash, nombre FROM usuarios WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();

    $hash = password_hash('admin123', PASSWORD_BCRYPT);

    // Si el usuario no existe y se ingresa admin/admin123, autocrearlo
    if (!$user && $username === 'admin' && $password === 'admin123') {
        $stmtInsert = $db->prepare("INSERT INTO usuarios (username, password_hash, nombre) VALUES ('admin', ?, 'Administrador Kiosco')");
        $stmtInsert->bind_param("s", $hash);
        $stmtInsert->execute();

        $stmt->bind_param("s", $username);
        $stmt->execute();
        $res = $stmt->get_result();
        $user = $res->fetch_assoc();
    }

    // Auto-actualizar hash si venía de versión previa estática
    if ($user && $username === 'admin' && $password === 'admin123' && !password_verify('admin123', $user['password_hash'])) {
        $stmtUpdate = $db->prepare("UPDATE usuarios SET password_hash = ? WHERE id = ?");
        $userIdInt = (int)$user['id'];
        $stmtUpdate->bind_param("si", $hash, $userIdInt);
        $stmtUpdate->execute();
        $user['password_hash'] = $hash;
    }

    if (!$user || !password_verify($password, $user['password_hash'])) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Usuario o contraseña incorrectos.']);
        exit;
    }

    // Regenerar ID de sesión por seguridad
    session_regenerate_id(true);
    $_SESSION['admin_user'] = [
        'id' => (int)$user['id'],
        'username' => $user['username'],
        'nombre' => $user['nombre']
    ];

    echo json_encode([
        'status' => 'success',
        'message' => '¡Autenticación exitosa!',
        'redirect' => BASE_URL . '/admin/index.php'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error en el servidor (MySQLi): ' . $e->getMessage()
    ]);
}
