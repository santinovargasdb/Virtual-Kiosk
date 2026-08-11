<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getConnection();

    $category = trim($_GET['category'] ?? '');
    $search = trim($_GET['q'] ?? '');

    $sql = "SELECT id, nombre, descripcion, precio, categoria, imagen_url, stock, destacado, personalizable FROM productos WHERE 1=1";
    $types = "";
    $params = [];

    if (!empty($category) && $category !== 'todos') {
        $sql .= " AND categoria = ?";
        $types .= "s";
        $params[] = $category;
    }

    if (!empty($search)) {
        $sql .= " AND (nombre LIKE ? OR descripcion LIKE ?)";
        $types .= "ss";
        $searchParam = '%' . $search . '%';
        $params[] = $searchParam;
        $params[] = $searchParam;
    }

    $sql .= " ORDER BY destacado DESC, id DESC";

    $stmt = $db->prepare($sql);

    if (!empty($types)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $products = $result->fetch_all(MYSQLI_ASSOC);

    // Formatear tipos de datos
    foreach ($products as &$prod) {
        $prod['id'] = (int)$prod['id'];
        $prod['precio'] = (float)$prod['precio'];
        $prod['stock'] = (int)$prod['stock'];
        $prod['destacado'] = (bool)$prod['destacado'];
        $prod['personalizable'] = (bool)$prod['personalizable'];
    }

    echo json_encode([
        'status' => 'success',
        'count' => count($products),
        'data' => $products
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error al consultar productos (MySQLi): ' . $e->getMessage()
    ]);
}
