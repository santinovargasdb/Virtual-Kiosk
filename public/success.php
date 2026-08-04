<?php
require_once __DIR__ . '/../env.php';

$paymentId = $_GET['payment_id'] ?? $_GET['collection_id'] ?? 'N/A';
$status = $_GET['status'] ?? $_GET['collection_status'] ?? 'approved';
$externalRef = $_GET['external_reference'] ?? 'N/A';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>¡Pago Exitoso! - Kiosco Online</title>
  <link rel="stylesheet" href="css/styles.css">
  <script>
    // Limpiar carrito local al completar el pago exitosamente
    localStorage.removeItem('kiosco_online_cart_v1');
  </script>
</head>
<body>
  <div class="status-card">
    <div class="status-icon success">✓</div>
    <h1 class="status-title text-success">¡Pago Confirmado!</h1>
    <p class="status-desc">
      Tu compra ha sido procesada correctamente mediante Mercado Pago.<br>
      ¡Muchas gracias por elegir nuestro Kiosco Online!
    </p>

    <div class="status-detail-box">
      <p class="mb-2"><strong class="text-main">Referencia de Orden:</strong> <span class="text-highlight"><?php echo htmlspecialchars($externalRef); ?></span></p>
      <p class="mb-2"><strong class="text-main">ID de Pago Mercado Pago:</strong> <?php echo htmlspecialchars($paymentId); ?></p>
      <p><strong class="text-main">Estado:</strong> <span class="text-success font-bold">Aprobado (Approved)</span></p>
    </div>

    <a href="../index.php" class="btn-home">
      <span>🛍️ Volver al Kiosco</span>
    </a>
  </div>
</body>
</html>
