<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

// Exigir autenticación de administrador
requireAdminAuth();

try {
    $db = Database::getConnection();

    // 1. Obtener listado de ordenes
    $result = $db->query("
        SELECT id, external_reference, monto_total, horario_retiro, estado, mp_payment_id, mp_merchant_order_id, created_at, updated_at
        FROM ordenes
        ORDER BY id DESC
        LIMIT 100
    ");
    $orders = $result->fetch_all(MYSQLI_ASSOC);

    // 2. Obtener items de cada orden
    $stmtItems = $db->prepare("
        SELECT oi.id, oi.orden_id, oi.producto_id, oi.cantidad, oi.precio_unitario, oi.notas_personalizacion, p.nombre as producto_nombre
        FROM orden_items oi
        LEFT JOIN productos p ON oi.producto_id = p.id
        WHERE oi.orden_id = ?
    ");

    $totalRevenue = 0.0;
    $approvedCount = 0;
    $pendingCount = 0;
    $rejectedCount = 0;

    foreach ($orders as &$order) {
        $order['id'] = (int)$order['id'];
        $order['monto_total'] = (float)$order['monto_total'];

        if ($order['estado'] === 'approved') {
            $totalRevenue += $order['monto_total'];
            $approvedCount++;
        } elseif ($order['estado'] === 'pending') {
            $pendingCount++;
        } else {
            $rejectedCount++;
        }

        $stmtItems->bind_param("i", $order['id']);
        $stmtItems->execute();
        $resItems = $stmtItems->get_result();
        $items = $resItems->fetch_all(MYSQLI_ASSOC);

        foreach ($items as &$it) {
            $it['cantidad'] = (int)$it['cantidad'];
            $it['precio_unitario'] = (float)$it['precio_unitario'];
        }

        $order['items'] = $items;
    }

    echo json_encode([
        'status' => 'success',
        'stats' => [
            'total_orders' => count($orders),
            'approved_count' => $approvedCount,
            'pending_count' => $pendingCount,
            'rejected_count' => $rejectedCount,
            'total_revenue' => $totalRevenue
        ],
        'data' => $orders
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error al obtener ordenes (MySQLi): ' . $e->getMessage()
    ]);
}
