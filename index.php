<?php
require_once __DIR__ . '/env.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Octava Café - Cafetería y Bar de Especialidad | Take Away</title>
  <meta name="description" content="Kiosco virtual de Octava Café (Equipo 8). Elegí tu café de especialidad, personalizalo a tu gusto, seleccioná la hora de retiro y pagá online con Mercado Pago.">

  <!-- CSS Principal -->
  <link rel="stylesheet" href="public/css/styles.css">

  <!-- Favicon e Iconos -->
  <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>☕</text></svg>">
</head>
<body>

  <!-- Encabezado / Navegación Principal -->
  <header class="navbar">
    <div class="nav-container">
      <a href="index.php" class="logo">
        <div class="logo-icon">☕</div>
        <div>
          <span class="logo-text">Octava Café</span>
          <span class="logo-badge">Take Away · Equipo 8</span>
        </div>
      </a>

      <!-- Buscador de Productos -->
      <div class="search-box">
        <span class="search-icon">🔍</span>
        <input type="text" id="search-input" class="search-input" placeholder="Buscar lattes, filtrados, medialunas...">
      </div>

      <!-- Botón de Carrito con Contador Dinámico -->
      <button id="cart-trigger" class="cart-trigger" aria-label="Abrir Pedido">
        <span>🧾 Mi Pedido</span>
        <span id="cart-badge" class="cart-badge">0</span>
      </button>
    </div>
  </header>

  <!-- Contenido Principal -->
  <main class="main-content">
    <!-- Hero de la Cafetería -->
    <section class="hero-bar">
      <h1 class="hero-title">Tu café de especialidad,<br><em>listo cuando llegás</em>.</h1>
      <p class="hero-sub">
        Elegí de la carta, personalizá la leche, el azúcar y las opciones Sin TACC,
        contanos a qué hora pasás por el local y pagá online con Mercado Pago.
      </p>
    </section>

    <!-- Barra de Filtro de Categorías -->
    <nav class="categories-bar" aria-label="Categorías de la carta">
      <button class="category-btn active" data-category="todos">✨ Toda la carta</button>
      <button class="category-btn" data-category="cafes">☕ Cafés de Especialidad</button>
      <button class="category-btn" data-category="filtrados">🫗 Métodos Filtrados</button>
      <button class="category-btn" data-category="bebidas">🧊 Otras Bebidas</button>
      <button class="category-btn" data-category="pasteleria">🥐 Pastelería</button>
    </nav>

    <!-- Grilla Adaptativa de Productos -->
    <section id="products-grid" class="products-grid">
      <!-- Los productos se cargan dinámicamente vía JS -->
    </section>
  </main>

  <!-- Overlay y Drawer Deslizable del Carrito -->
  <div id="cart-overlay" class="cart-overlay"></div>

  <aside id="cart-drawer" class="cart-drawer" aria-label="Tu Pedido Take Away">
    <div class="cart-header">
      <h2 class="cart-title">🧾 Tu Pedido</h2>
      <button id="cart-close-btn" class="cart-close-btn" aria-label="Cerrar Pedido">&times;</button>
    </div>

    <div id="cart-body" class="cart-body">
      <!-- Las comandas del pedido se inyectan dinámicamente -->
    </div>

    <div class="cart-footer">
      <!-- Horario de Retiro Take Away a nivel pedido -->
      <div class="pickup-bar">
        <label for="cart-pickup" class="pickup-label">⏰ Retiro por el local</label>
        <select id="cart-pickup" class="pickup-select" aria-label="Horario de retiro del pedido"></select>
      </div>

      <div class="cart-summary-row">
        <span style="color: var(--text-muted); font-weight: 500;">Total a abonar:</span>
        <span id="cart-total-price" class="cart-total-price">$ 0,00</span>
      </div>

      <!-- Botón Oficial de Checkout Mercado Pago -->
      <button id="btn-checkout-mp" class="btn-checkout-mp" disabled>
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM16.5 15.5H14.5L13.2 12.8C13.1 12.6 12.9 12.5 12.7 12.5H10.5V15.5H8.5V8.5H12.8C13.8 8.5 14.7 8.9 15.3 9.6C15.9 10.3 16.2 11.2 16.2 12.2C16.2 13.5 15.5 14.7 14.4 15.2L16.5 15.5Z" fill="white"/>
        </svg>
        <span>Pagar con Mercado Pago</span>
      </button>

      <button id="btn-clear-cart" class="btn-clear-cart">
        Vaciar Pedido
      </button>
    </div>
  </aside>

  <!-- ============================================================
       MODAL DE PERSONALIZACIÓN DEL CAFÉ (por producto)
       ============================================================ -->
  <div id="custom-modal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="custom-title">
    <div class="modal-card custom-card">
      <div class="custom-header">
        <img id="custom-img" class="custom-img" src="" alt="">
        <div class="custom-header-info">
          <span id="custom-category" class="product-category"></span>
          <h3 id="custom-title" class="custom-title"></h3>
          <span id="custom-price" class="custom-price"></span>
        </div>
        <button id="custom-close" class="cart-close-btn" aria-label="Cerrar personalización">&times;</button>
      </div>

      <form id="custom-form">
        <input type="hidden" id="custom-product-id" value="0">

        <!-- Opciones de bebida (solo productos personalizables) -->
        <div id="custom-drink-options">
          <fieldset class="option-group">
            <legend class="option-legend">🥛 Tipo de leche</legend>
            <div class="option-pills" id="milk-options">
              <label class="option-pill"><input type="radio" name="leche" value="entera" checked><span>Entera</span></label>
              <label class="option-pill"><input type="radio" name="leche" value="almendras"><span>Almendras</span></label>
              <label class="option-pill"><input type="radio" name="leche" value="deslactosada"><span>Deslactosada</span></label>
            </div>
          </fieldset>

          <fieldset class="option-group">
            <legend class="option-legend">🍬 Nivel de azúcar</legend>
            <div class="option-pills" id="sugar-options">
              <label class="option-pill"><input type="radio" name="azucar" value="sin_azucar"><span>Sin azúcar</span></label>
              <label class="option-pill"><input type="radio" name="azucar" value="poco"><span>Poco</span></label>
              <label class="option-pill"><input type="radio" name="azucar" value="normal" checked><span>Normal</span></label>
              <label class="option-pill"><input type="radio" name="azucar" value="dulce"><span>Dulce</span></label>
            </div>
          </fieldset>
        </div>

        <!-- Opción Sin TACC (disponible para todos los productos) -->
        <label class="tacc-check">
          <input type="checkbox" id="custom-sin-tacc">
          <span class="tacc-box" aria-hidden="true"></span>
          <span class="tacc-text"><strong>Sin TACC</strong> — preparación apta celíacos, sin contacto cruzado</span>
        </label>

        <!-- Nota libre para el barista -->
        <div class="option-group">
          <label class="option-legend" for="custom-nota">📝 Nota para el barista (opcional)</label>
          <input type="text" id="custom-nota" class="form-input" maxlength="140" placeholder="Ej: extra caliente, poca espuma...">
        </div>

        <!-- Horario de Retiro Take Away -->
        <div class="option-group">
          <label class="option-legend" for="custom-pickup">⏰ Horario de retiro (Take Away)</label>
          <select id="custom-pickup" class="pickup-select"></select>
          <p class="pickup-hint">El horario aplica a todo el pedido. Local abierto de 08:00 a 20:00 hs.</p>
        </div>

        <!-- Cantidad + Confirmar -->
        <div class="custom-footer">
          <div class="cart-item-controls custom-qty">
            <button type="button" class="qty-btn" id="custom-qty-minus" aria-label="Restar cantidad">-</button>
            <span class="qty-value" id="custom-qty-value">1</span>
            <button type="button" class="qty-btn" id="custom-qty-plus" aria-label="Sumar cantidad">+</button>
          </div>
          <button type="submit" id="custom-submit" class="btn-add-custom">
            Agregar al pedido · <span id="custom-subtotal">$ 0,00</span>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Contenedor de Notificaciones Toast -->
  <div id="toast-container" class="toast-container"></div>

  <!-- Scripts JavaScript ES Modules -->
  <script type="module" src="public/js/app.js"></script>
</body>
</html>
