<?php
/**
 * Sincronización del carrito con la sesión del servidor (Equipo 8).
 * Cada vez que el cliente agrega, modifica o quita un café del carrito,
 * el frontend espeja el estado completo (productos + personalizaciones +
 * horario de retiro) en $_SESSION['carrito'].
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Devolver el carrito guardado en sesión (útil para depurar / defender el TP)
    echo json_encode([
        'status' => 'success',
        'carrito' => $_SESSION['carrito'] ?? ['items' => [], 'horario_retiro' => null, 'monto_total' => 0]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
    exit;
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Formato de carrito inválido.']);
    exit;
}

$items = [];
foreach (($data['items'] ?? []) as $item) {
    if (!is_array($item)) {
        continue;
    }

    $personalizacion = is_array($item['personalizacion'] ?? null) ? $item['personalizacion'] : [];

    $items[] = [
        'producto_id'     => (int)($item['id'] ?? 0),
        'nombre'          => mb_substr(strip_tags((string)($item['nombre'] ?? '')), 0, 150),
        'cantidad'        => max(0, (int)($item['quantity'] ?? 0)),
        'precio_unitario' => (float)($item['precio'] ?? 0),
        'personalizacion' => [
            'leche'    => mb_substr(strip_tags((string)($personalizacion['leche'] ?? '')), 0, 30),
            'azucar'   => mb_substr(strip_tags((string)($personalizacion['azucar'] ?? '')), 0, 30),
            'sin_tacc' => !empty($personalizacion['sin_tacc']),
            'nota'     => mb_substr(strip_tags((string)($personalizacion['nota'] ?? '')), 0, 140)
        ]
    ];
}

$horario = trim((string)($data['horario_retiro'] ?? ''));
if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $horario)) {
    $horario = null;
}

$_SESSION['carrito'] = [
    'items'          => $items,
    'horario_retiro' => $horario,
    'monto_total'    => array_reduce($items, fn($acc, $i) => $acc + $i['precio_unitario'] * $i['cantidad'], 0.0),
    'actualizado_en' => date('Y-m-d H:i:s')
];

echo json_encode([
    'status' => 'success',
    'message' => 'Carrito sincronizado en $_SESSION[\'carrito\'].',
    'items_count' => count($items)
], JSON_UNESCAPED_UNICODE);
