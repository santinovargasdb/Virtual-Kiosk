<?php
/**
 * Checkout del Coffee Shop (Equipo 8).
 * Recibe el carrito con las personalizaciones de cada bebida (leche, azúcar,
 * Sin TACC, nota del cliente) y el horario de retiro Take Away del pedido.
 * Registra la orden en MySQL (ordenes + orden_items.notas_personalizacion)
 * y genera la Preferencia de Pago de Mercado Pago vía API REST.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

// Catálogo de opciones válidas de personalización (whitelist)
const LECHES_VALIDAS = [
    'entera'       => 'Entera',
    'almendras'    => 'Almendras',
    'deslactosada' => 'Deslactosada'
];
const AZUCARES_VALIDOS = [
    'sin_azucar' => 'Sin azúcar',
    'poco'       => 'Poco',
    'normal'     => 'Normal',
    'dulce'      => 'Dulce'
];

// Horario de atención del local para retiros Take Away
const RETIRO_DESDE = '08:00';
const RETIRO_HASTA = '20:00';

/**
 * Normaliza y valida la personalización enviada por el cliente.
 * Devuelve un array limpio con claves conocidas.
 */
function normalizarPersonalizacion(array $raw, bool $esPersonalizable): array {
    $leche = strtolower(trim((string)($raw['leche'] ?? 'entera')));
    $azucar = strtolower(trim((string)($raw['azucar'] ?? 'normal')));

    $nota = trim((string)($raw['nota'] ?? ''));
    $nota = strip_tags($nota);
    $nota = preg_replace('/[\x00-\x1F\x7F]/u', ' ', $nota);
    $nota = mb_substr($nota, 0, 140);

    return [
        'leche'    => ($esPersonalizable && isset(LECHES_VALIDAS[$leche])) ? $leche : ($esPersonalizable ? 'entera' : null),
        'azucar'   => ($esPersonalizable && isset(AZUCARES_VALIDOS[$azucar])) ? $azucar : ($esPersonalizable ? 'normal' : null),
        'sin_tacc' => !empty($raw['sin_tacc']),
        'nota'     => $nota
    ];
}

/**
 * Construye la cadena legible que se guarda en orden_items.notas_personalizacion
 * y que el barista lee en la comanda del panel de administración.
 * Ej: "Leche: Almendras · Azúcar: Poco · SIN TACC · Nota: bien caliente"
 */
function construirNotasPersonalizacion(array $p): string {
    $partes = [];

    if ($p['leche'] !== null) {
        $partes[] = 'Leche: ' . LECHES_VALIDAS[$p['leche']];
    }
    if ($p['azucar'] !== null) {
        $partes[] = 'Azúcar: ' . AZUCARES_VALIDOS[$p['azucar']];
    }
    if ($p['sin_tacc']) {
        $partes[] = 'SIN TACC';
    }
    if ($p['nota'] !== '') {
        $partes[] = 'Nota: ' . $p['nota'];
    }

    return implode(' · ', $partes);
}

/**
 * Valida el horario de retiro Take Away (formato HH:MM dentro del
 * horario de atención del local).
 */
function validarHorarioRetiro(string $horario): bool {
    if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $horario)) {
        return false;
    }
    return $horario >= RETIRO_DESDE && $horario <= RETIRO_HASTA;
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

    // Validar horario de retiro Take Away (obligatorio para el pedido)
    $horarioRetiro = trim((string)($data['horario_retiro'] ?? ''));
    if (!validarHorarioRetiro($horarioRetiro)) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Elegí un horario de retiro válido entre las ' . RETIRO_DESDE . ' y las ' . RETIRO_HASTA . ' hs.'
        ]);
        exit;
    }

    $db = Database::getConnection();
    $db->begin_transaction();

    $orderItems = [];
    $mpItems = [];
    $sessionCart = [];
    $montoTotal = 0.0;
    $cantidadPorProducto = [];   // acumulado para validar stock entre variantes del mismo café
    $productosCache = [];

    $stmtProduct = $db->prepare("SELECT id, nombre, descripcion, precio, stock, personalizable FROM productos WHERE id = ? FOR UPDATE");

    // Validar productos, personalizaciones y calcular precios en servidor desde MySQLi
    foreach ($data['items'] as $item) {
        $productId = (int)($item['id'] ?? 0);
        $qty = (int)($item['quantity'] ?? 0);

        if ($productId <= 0 || $qty <= 0) {
            continue;
        }

        if (!isset($productosCache[$productId])) {
            $stmtProduct->bind_param("i", $productId);
            $stmtProduct->execute();
            $res = $stmtProduct->get_result();
            $product = $res->fetch_assoc();

            if (!$product) {
                throw new Exception("El producto ID {$productId} no fue encontrado en la carta.");
            }
            $productosCache[$productId] = $product;
        }

        $product = $productosCache[$productId];

        // El stock se valida sumando todas las variantes del mismo producto
        // (ej: dos Lattes con distinta leche son ítems separados del carrito)
        $cantidadPorProducto[$productId] = ($cantidadPorProducto[$productId] ?? 0) + $qty;
        if ((int)$product['stock'] < $cantidadPorProducto[$productId]) {
            throw new Exception("Stock insuficiente para '{$product['nombre']}'. Disponible: {$product['stock']}.");
        }

        $esPersonalizable = (bool)$product['personalizable'];
        $personalizacion = normalizarPersonalizacion(
            is_array($item['personalizacion'] ?? null) ? $item['personalizacion'] : [],
            $esPersonalizable
        );
        $notas = construirNotasPersonalizacion($personalizacion);

        $precioUnitario = (float)$product['precio'];
        $subtotal = $precioUnitario * $qty;
        $montoTotal += $subtotal;

        $orderItems[] = [
            'producto_id'           => (int)$product['id'],
            'cantidad'              => $qty,
            'precio_unitario'       => $precioUnitario,
            'notas_personalizacion' => $notas
        ];

        // Copia del ítem con su personalización para $_SESSION['carrito']
        $sessionCart[] = [
            'producto_id'     => (int)$product['id'],
            'nombre'          => (string)$product['nombre'],
            'cantidad'        => $qty,
            'precio_unitario' => $precioUnitario,
            'personalizacion' => $personalizacion,
            'notas'           => $notas
        ];

        $mpItems[] = [
            'id'          => (string)$product['id'],
            'title'       => (string)$product['nombre'],
            'description' => mb_substr($notas !== '' ? $notas : (string)($product['descripcion'] ?? $product['nombre']), 0, 255),
            'quantity'    => $qty,
            'currency_id' => 'ARS',
            'unit_price'  => $precioUnitario
        ];
    }

    if (empty($mpItems)) {
        throw new Exception("No hay productos válidos en la solicitud.");
    }

    // Guardar el pedido completo (productos + personalizaciones + horario)
    // en la sesión del servidor: $_SESSION['carrito']
    $_SESSION['carrito'] = [
        'items'          => $sessionCart,
        'horario_retiro' => $horarioRetiro,
        'monto_total'    => $montoTotal,
        'actualizado_en' => date('Y-m-d H:i:s')
    ];

    // Generar número de referencia externa único para la orden
    $externalReference = 'CAFE8-' . time() . '-' . strtoupper(bin2hex(random_bytes(3)));

    // 1. Insertar Orden principal en MySQL (Estado: 'pending') con horario de retiro
    $stmtOrder = $db->prepare("
        INSERT INTO ordenes (external_reference, monto_total, horario_retiro, estado, created_at)
        VALUES (?, ?, ?, 'pending', NOW())
    ");
    $stmtOrder->bind_param("sds", $externalReference, $montoTotal, $horarioRetiro);
    $stmtOrder->execute();
    $orderId = $db->insert_id;

    // 2. Insertar Detalle de la Orden con las notas de personalización de cada café
    $stmtItem = $db->prepare("
        INSERT INTO orden_items (orden_id, producto_id, cantidad, precio_unitario, notas_personalizacion)
        VALUES (?, ?, ?, ?, ?)
    ");
    foreach ($orderItems as $oItem) {
        $stmtItem->bind_param(
            "iiids",
            $orderId,
            $oItem['producto_id'],
            $oItem['cantidad'],
            $oItem['precio_unitario'],
            $oItem['notas_personalizacion']
        );
        $stmtItem->execute();
    }

    $db->commit();

    // 3. Crear Preferencia de Pago usando la API REST de Mercado Pago
    if (MP_ACCESS_TOKEN === '') {
        throw new Exception("Falta configurar MP_ACCESS_TOKEN en el archivo .env para cobrar con Mercado Pago.");
    }

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
        'statement_descriptor' => 'CAFE EQUIPO 8',
        'metadata' => [
            'horario_retiro' => $horarioRetiro,
            'modalidad' => 'take_away'
        ]
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
        'horario_retiro' => $horarioRetiro,
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
