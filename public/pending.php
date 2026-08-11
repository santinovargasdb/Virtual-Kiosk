<?php
require_once __DIR__ . '/../env.php';

$paymentId = $_GET['payment_id'] ?? $_GET['collection_id'] ?? 'N/A';
$externalRef = $_GET['external_reference'] ?? 'N/A';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pago Pendiente - Octava Café</title>
  <link rel="stylesheet" href="css/styles.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>☕</text></svg>">
</head>
<body>
  <div class="status-card">
    <div class="status-icon pending">⏳</div>
    <h1 class="status-title text-warning">Pago en Proceso / Pendiente</h1>
    <p class="status-desc">
      Tu pago se encuentra pendiente de acreditación (por ejemplo, en efectivo o transferencia bancaria).<br>
      Apenas Mercado Pago confirme el pago, la comanda entrará a la barra
      y tu café se preparará para el horario de retiro elegido.
    </p>

    <div class="status-detail-box">
      <p class="mb-2"><strong class="text-main">Referencia de Orden:</strong> <span class="text-highlight"><?php echo htmlspecialchars($externalRef); ?></span></p>
      <p class="mb-2"><strong class="text-main">ID de Pago:</strong> <?php echo htmlspecialchars($paymentId); ?></p>
      <p><strong class="text-main">Estado:</strong> <span class="text-warning font-bold">Pendiente (In Process)</span></p>
    </div>

    <a href="../index.php" class="btn-home">
      <span>☕ Volver a la Carta</span>
    </a>
  </div>
</body>
</html>
