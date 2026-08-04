<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../env.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido. Utiliza POST.']);
    exit;
}

try {
    // Obtener y decodificar el cuerpo de la petición JSON
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);

    if (empty($data['items']) || !is_array($data['items'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'El carrito está vacío o el formato es inválido.']);
        exit;
    }

    $db = Database::getConnection();
    $db->begin_transaction();

    $orderItems = [];
    $mpItems = [];
    $montoTotal = 0.0;

    $stmtProduct = $db->prepare("SELECT id, nombre, descripcion, precio, stock FROM productos WHERE id = ? FOR UPDATE");

    // Validar productos y calcular precios en servidor desde MySQLi
    foreach ($data['items'] as $item) {
        $productId = (int)($item['id'] ?? 0);
        $qty = (int)($item['quantity'] ?? 0);

        if ($productId <= 0 || $qty <= 0) {
            continue;
        }

        $stmtProduct->bind_param("i", $productId);
        $stmtProduct->execute();
        $res = $stmtProduct->get_result();
        $product = $res->fetch_assoc();

        if (!$product) {
            throw new Exception("El producto ID {$productId} no fue encontrado.");
        }

        if ((int)$product['stock'] < $qty) {
            throw new Exception("Stock insuficiente para '{$product['nombre']}'. Disponible: {$product['stock']}.");
        }

        $precioUnitario = (float)$product['precio'];
        $subtotal = $precioUnitario * $qty;
        $montoTotal += $subtotal;

        $orderItems[] = [
            'producto_id' => (int)$product['id'],
            'cantidad' => $qty,
            'precio_unitario' => $precioUnitario
        ];

        $mpItems[] = [
            'id' => (string)$product['id'],
            'title' => (string)$product['nombre'],
            'description' => substr((string)($product['descripcion'] ?? $product['nombre']), 0, 255),
            'quantity' => $qty,
            'currency_id' => 'ARS',
            'unit_price' => $precioUnitario
        ];
    }

    if (empty($mpItems)) {
        throw new Exception("No hay productos válidos en la solicitud.");
    }

    // Generar número de referencia externa único para la orden
    $externalReference = 'KIOSCO-' . time() . '-' . strtoupper(bin2hex(random_bytes(3)));

    // 1. Insertar Orden principal en MySQL (Estado: 'pending')
    $stmtOrder = $db->prepare("
        INSERT INTO ordenes (external_reference, monto_total, estado, created_at)
        VALUES (?, ?, 'pending', NOW())
    ");
    $stmtOrder->bind_param("sd", $externalReference, $montoTotal);
    $stmtOrder->execute();
    $orderId = $db->insert_id;

    // 2. Insertar Detalle de la Orden
    $stmtItem = $db->prepare("
        INSERT INTO orden_items (orden_id, producto_id, cantidad, precio_unitario)
        VALUES (?, ?, ?, ?)
    ");
    foreach ($orderItems as $oItem) {
        $stmtItem->bind_param("iiid", $orderId, $oItem['producto_id'], $oItem['cantidad'], $oItem['precio_unitario']);
        $stmtItem->execute();
    }

    $db->commit();

    // 3. Crear Preferencia de Pago usando la API REST de Mercado Pago
    $baseUrl = rtrim(trim(BASE_URL), '/');
    if (!preg_match('/^https?:\/\//i', $baseUrl)) {
        $baseUrl = 'http://' . $baseUrl;
    }

    $mpPayload = [
        'items' => $mpItems,
        'back_urls' => [
            'success' => $baseUrl . '/public/success.php',
            'pending' => $baseUrl . '/public/pending.php',
            'failure' => $baseUrl . '/public/failure.php'
        ],
        'external_reference' => $externalReference,
        'statement_descriptor' => 'KIOSCO ONLINE'
    ];

    // En dominios públicos HTTPS activamos auto_return
    $host = parse_url($baseUrl, PHP_URL_HOST) ?? '';
    $scheme = strtolower(parse_url($baseUrl, PHP_URL_SCHEME) ?? 'http');
    if ($scheme === 'https' && !preg_match('/(localhost|127\.0\.0\.1)/i', $host)) {
        $mpPayload['auto_return'] = 'approved';
    }

    // Omitir notification_url si se ejecuta en localhost
    if (!preg_match('/(localhost|127\.0\.0\.1)/i', $host)) {
        $mpPayload['notification_url'] = $baseUrl . '/api/webhook.php';
    }

    // Realizar llamada cURL a la API oficial de Mercado Pago
    $ch = curl_init('https://api.mercadopago.com/checkout/preferences');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($mpPayload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . MP_ACCESS_TOKEN
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        throw new Exception("Error al comunicarse con Mercado Pago: " . $curlErr);
    }

    $responseData = json_decode($response, true);

    if ($httpCode !== 200 && $httpCode !== 201) {
        $msg = $responseData['message'] ?? 'Error al generar la preferencia de Mercado Pago';
        $causes = [];
        if (!empty($responseData['cause']) && is_array($responseData['cause'])) {
            foreach ($responseData['cause'] as $c) {
                $causes[] = ($c['code'] ?? '') . ': ' . ($c['description'] ?? '');
            }
        }
        $causeDetail = !empty($causes) ? ' Detalle: [' . implode(', ', $causes) . ']' : '';
        throw new Exception("Mercado Pago API (Error {$httpCode}): {$msg}.{$causeDetail}");
    }

    // Retornar punto de inicio (init_point / sandbox_init_point)
    $initPoint = $responseData['init_point'] ?? '';
    $sandboxInitPoint = $responseData['sandbox_init_point'] ?? $initPoint;

    echo json_encode([
        'status' => 'success',
        'order_id' => $orderId,
        'external_reference' => $externalReference,
        'preference_id' => $responseData['id'] ?? '',
        'init_point' => $initPoint,
        'sandbox_init_point' => $sandboxInitPoint
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    if (isset($db) && $db->connect_errno === 0) {
        @$db->rollback();
    }
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
