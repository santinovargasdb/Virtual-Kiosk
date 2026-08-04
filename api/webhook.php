<?php
/**
 * Webhook / IPN Listener para recibir notificaciones automáticas de Mercado Pago.
 * Actualiza el estado de la orden en MySQLi e incrementa/descuenta el inventario.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../env.php';

// Mercado Pago envía notificaciones por GET o POST
$paymentId = $_GET['data_id'] ?? $_GET['id'] ?? null;
$type = $_GET['type'] ?? $_GET['topic'] ?? null;

if (!$paymentId) {
    $rawInput = file_get_contents('php://input');
    $body = json_decode($rawInput, true);
    if (isset($body['data']['id'])) {
        $paymentId = $body['data']['id'];
    }
    if (isset($body['type'])) {
        $type = $body['type'];
    }
}

// Log de depuración
$logMessage = sprintf("[%s] Webhook recibido: Type=%s, PaymentID=%s\n", date('Y-m-d H:i:s'), $type ?? 'N/A', $paymentId ?? 'N/A');
@file_put_contents(__DIR__ . '/webhook.log', $logMessage, FILE_APPEND);

if ($paymentId && ($type === 'payment' || $type === null)) {
    try {
        // 1. Consultar estado del pago a Mercado Pago API
        $ch = curl_init("https://api.mercadopago.com/v1/payments/" . $paymentId);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . MP_ACCESS_TOKEN
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $paymentData = json_decode($response, true);

            $status = $paymentData['status'] ?? 'pending';
            $externalReference = $paymentData['external_reference'] ?? null;
            $merchantOrderId = (string)($paymentData['order']['id'] ?? '');

            if ($externalReference) {
                $db = Database::getConnection();
                $db->begin_transaction();

                // Consultar estado previo de la orden
                $stmtPrev = $db->prepare("SELECT id, estado FROM ordenes WHERE external_reference = ? FOR UPDATE");
                $stmtPrev->bind_param("s", $externalReference);
                $stmtPrev->execute();
                $resPrev = $stmtPrev->get_result();
                $order = $resPrev->fetch_assoc();

                if ($order) {
                    $previousStatus = $order['estado'];

                    $dbStatus = 'pending';
                    if ($status === 'approved') {
                        $dbStatus = 'approved';
                    } elseif (in_array($status, ['rejected', 'cancelled', 'refunded', 'charged_back'])) {
                        $dbStatus = 'rejected';
                    }

                    // Actualizar orden en MySQLi
                    $stmtUpdate = $db->prepare("
                        UPDATE ordenes 
                        SET estado = ?, 
                            mp_payment_id = ?, 
                            mp_merchant_order_id = ?, 
                            updated_at = NOW()
                        WHERE id = ?
                    ");
                    $pIdStr = (string)$paymentId;
                    $orderIdInt = (int)$order['id'];

                    $stmtUpdate->bind_param("sssi", $dbStatus, $pIdStr, $merchantOrderId, $orderIdInt);
                    $stmtUpdate->execute();

                    // Si la orden pasa a 'approved', descontar stock
                    if ($dbStatus === 'approved' && $previousStatus !== 'approved') {
                        $stmtItems = $db->prepare("SELECT producto_id, cantidad FROM orden_items WHERE orden_id = ?");
                        $stmtItems->bind_param("i", $orderIdInt);
                        $stmtItems->execute();
                        $resItems = $stmtItems->get_result();
                        $items = $resItems->fetch_all(MYSQLI_ASSOC);

                        $stmtStock = $db->prepare("UPDATE productos SET stock = GREATEST(0, stock - ?) WHERE id = ?");
                        foreach ($items as $item) {
                            $cantInt = (int)$item['cantidad'];
                            $prodIdInt = (int)$item['producto_id'];
                            $stmtStock->bind_param("ii", $cantInt, $prodIdInt);
                            $stmtStock->execute();
                        }
                    }

                    $db->commit();
                    @file_put_contents(__DIR__ . '/webhook.log', "Orden {$externalReference} actualizada a: {$dbStatus}\n", FILE_APPEND);
                } else {
                    $db->rollback();
                }
            }
        }
    } catch (Exception $e) {
        if (isset($db)) {
            @$db->rollback();
        }
        @file_put_contents(__DIR__ . '/webhook.log', "Error en webhook MySQLi: " . $e->getMessage() . "\n", FILE_APPEND);
    }
}

http_response_code(200);
echo json_encode(['status' => 'ok']);
