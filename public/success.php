<?php
require_once __DIR__ . '/../env.php';
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$paymentId = $_GET['payment_id'] ?? $_GET['collection_id'] ?? 'N/A';
$status = $_GET['status'] ?? $_GET['collection_status'] ?? 'approved';
$externalRef = $_GET['external_reference'] ?? 'N/A';

// Buscar el horario de retiro Take Away de la orden para recordárselo al cliente
$horarioRetiro = null;
if ($externalRef !== 'N/A') {
    try {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT horario_retiro FROM ordenes WHERE external_reference = ? LIMIT 1");
        $stmt->bind_param("s", $externalRef);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $horarioRetiro = $row['horario_retiro'] ?? null;
    } catch (Exception $e) {
        // Si la consulta falla, la página de éxito se muestra igual sin el horario
        $horarioRetiro = null;
    }
}

// El pedido ya quedó registrado: limpiar el carrito de la sesión del servidor
unset($_SESSION['carrito']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>¡Pago Confirmado! - Octava Café</title>
  <link rel="stylesheet" href="css/styles.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>☕</text></svg>">
  <script>
    // Limpiar carrito local al completar el pago exitosamente
    localStorage.removeItem('octava_cafe_cart_v2');
    localStorage.removeItem('octava_cafe_pickup_v1');
    localStorage.removeItem('kiosco_online_cart_v1');
  </script>
</head>
<body>
  <div class="status-card">
    <div class="status-icon success">✓</div>
    <h1 class="status-title text-success">¡Pedido Confirmado!</h1>
    <p class="status-desc">
      Tu pago fue procesado correctamente mediante Mercado Pago y la comanda
      ya entró a la barra.<br>
      ¡Gracias por elegir Octava Café!
    </p>

    <div class="status-detail-box">
      <?php if ($horarioRetiro): ?>
        <p class="mb-2"><strong class="text-main">⏰ Retiro por el local:</strong>
          <span class="status-pickup-highlight"><?php echo htmlspecialchars($horarioRetiro); ?> hs</span>
        </p>
      <?php endif; ?>
      <p class="mb-2"><strong class="text-main">Referencia de Orden:</strong> <span class="text-highlight"><?php echo htmlspecialchars($externalRef); ?></span></p>
      <p class="mb-2"><strong class="text-main">ID de Pago Mercado Pago:</strong> <?php echo htmlspecialchars($paymentId); ?></p>
      <p><strong class="text-main">Estado:</strong> <span class="text-success font-bold">Aprobado (Approved)</span></p>
    </div>

    <a href="../index.php" class="btn-home">
      <span>☕ Volver a la Carta</span>
    </a>
  </div>
</body>
</html>
