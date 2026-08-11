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
  <title>Pago No Realizado - Octava Café</title>
  <link rel="stylesheet" href="css/styles.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>☕</text></svg>">
</head>
<body>
  <div class="status-card">
    <div class="status-icon failure">✕</div>
    <h1 class="status-title text-danger">El Pago no pudo ser procesado</h1>
    <p class="status-desc">
      Ocurrió un inconveniente o la transacción fue cancelada.<br>
      Tu pedido personalizado sigue guardado en el carrito: podés volver a la carta
      e intentar el pago nuevamente.
    </p>

    <div class="status-detail-box">
      <p class="mb-2"><strong class="text-main">Referencia de Orden:</strong> <span class="text-highlight"><?php echo htmlspecialchars($externalRef); ?></span></p>
      <p><strong class="text-main">Estado:</strong> <span class="text-danger font-bold">Rechazado o Cancelado</span></p>
    </div>

    <a href="../index.php" class="btn-home">
      <span>🔄 Intentar Nuevamente</span>
    </a>
  </div>
</body>
</html>
