<?php
require_once __DIR__ . '/../config/auth.php';

// Verificar autenticación
requireAdminAuth();

$adminUser = $_SESSION['admin_user'] ?? ['nombre' => 'Administrador'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel de Administración - Kiosco Online</title>
  <link rel="stylesheet" href="../public/css/styles.css">
</head>
<body>

  <div class="admin-layout">
    <!-- Header Admin -->
    <header class="admin-header">
      <div style="display: flex; align-items: center; gap: 1rem;">
        <div class="logo-icon">⚙️</div>
        <div>
          <h1 style="font-family: 'Outfit', sans-serif; font-size: 1.4rem; font-weight: 800;">Panel de Administración</h1>
          <span style="font-size: 0.85rem; color: var(--text-muted);">Bienvenido, <?php echo htmlspecialchars($adminUser['nombre']); ?></span>
        </div>
      </div>

      <div style="display: flex; gap: 0.75rem; align-items: center;">
        <a href="../index.php" target="_blank" class="btn-action btn-edit" style="text-decoration: none; padding: 0.6rem 1rem;">
          🏪 Ver Kiosco Público ↗
        </a>
        <a href="logout.php" class="btn-action btn-delete" style="text-decoration: none; padding: 0.6rem 1rem;">
          🚪 Cerrar Sesión
        </a>
      </div>
    </header>

    <!-- Métricas del Negocio -->
    <section class="stats-grid">
      <div class="stat-card">
        <span class="stat-title">Total Productos</span>
        <span id="stat-products-count" class="stat-value">...</span>
      </div>
      <div class="stat-card">
        <span class="stat-title">Recaudación Aprobada</span>
        <span id="stat-revenue" class="stat-value" style="color: #34d399;">$ ...</span>
      </div>
      <div class="stat-card">
        <span class="stat-title">Órdenes Mercado Pago</span>
        <span id="stat-orders-count" class="stat-value">...</span>
      </div>
      <div class="stat-card">
        <span class="stat-title">Productos Bajo Stock (&lt; 10u)</span>
        <span id="stat-low-stock" class="stat-value" style="color: #fbbf24;">...</span>
      </div>
    </section>

    <!-- Navegación por Solapas -->
    <nav class="tab-bar">
      <button class="tab-btn active" data-tab="tab-products">📦 Productos (CRUD)</button>
      <button class="tab-btn" data-tab="tab-orders">🧾 Historial de Órdenes MP</button>
    </nav>

    <!-- SOLAPA 1: GESTIÓN DE PRODUCTOS -->
    <section id="tab-products" class="tab-content">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h2 style="font-family: 'Outfit', sans-serif; font-size: 1.25rem;">Inventario del Kiosco</h2>
        <button id="btn-new-product" class="btn-action btn-edit" style="padding: 0.65rem 1.2rem; font-size: 0.9rem; background: var(--primary); color: white;">
          ➕ Nuevo Producto
        </button>
      </div>

      <div class="table-container">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Imagen</th>
              <th>Nombre</th>
              <th>Categoría</th>
              <th>Precio</th>
              <th>Stock</th>
              <th>Destacado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody id="products-table-body">
            <tr><td colspan="7" style="text-align: center; padding: 2rem;">Cargando inventario...</td></tr>
          </tbody>
        </table>
      </div>
    </section>

    <!-- SOLAPA 2: HISTORIAL DE ÓRDENES -->
    <section id="tab-orders" class="tab-content" style="display: none;">
      <div style="margin-bottom: 1rem;">
        <h2 style="font-family: 'Outfit', sans-serif; font-size: 1.25rem;">Órdenes Recibidas por Mercado Pago</h2>
      </div>

      <div class="table-container">
        <table class="admin-table">
          <thead>
            <tr>
              <th># Ref. Orden</th>
              <th>Monto Total</th>
              <th>Estado Mercado Pago</th>
              <th>ID Pago MP</th>
              <th>Ítems Comprados</th>
              <th>Fecha</th>
            </tr>
          </thead>
          <tbody id="orders-table-body">
            <tr><td colspan="6" style="text-align: center; padding: 2rem;">Cargando historial de ventas...</td></tr>
          </tbody>
        </table>
      </div>
    </section>
  </div>

  <!-- MODAL DE CREACIÓN / EDICIÓN DE PRODUCTO -->
  <div id="product-modal" class="modal-overlay">
    <div class="modal-card">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h3 id="modal-title" style="font-family: 'Outfit', sans-serif; font-size: 1.25rem;">Agregar Producto</h3>
        <button id="btn-close-modal" style="background: transparent; border: none; color: var(--text-muted); font-size: 1.5rem; cursor: pointer;">&times;</button>
      </div>

      <form id="product-form">
        <input type="hidden" id="prod-id" value="0">

        <div class="form-grid">
          <div class="form-group full-width">
            <label class="form-label" for="prod-nombre">Nombre del Producto</label>
            <input type="text" id="prod-nombre" class="form-input" required placeholder="Ej. Alfajor Chocolate">
          </div>

          <div class="form-group">
            <label class="form-label" for="prod-categoria">Categoría</label>
            <select id="prod-categoria" class="form-input" style="background: #0f172a;">
              <option value="golosinas">Golosinas</option>
              <option value="bebidas">Bebidas</option>
              <option value="snacks">Snacks</option>
              <option value="galletitas">Galletitas</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label" for="prod-precio">Precio ($ ARS)</label>
            <input type="number" step="0.01" id="prod-precio" class="form-input" required placeholder="1400.00">
          </div>

          <div class="form-group">
            <label class="form-label" for="prod-stock">Stock Disponible</label>
            <input type="number" id="prod-stock" class="form-input" required placeholder="50">
          </div>

          <div class="form-group">
            <label class="form-label" for="prod-destacado">¿Destacado?</label>
            <select id="prod-destacado" class="form-input" style="background: #0f172a;">
              <option value="0">No</option>
              <option value="1">Sí ⭐</option>
            </select>
          </div>

          <div class="form-group full-width">
            <label class="form-label" for="prod-imagen">URL de la Imagen</label>
            <input type="url" id="prod-imagen" class="form-input" placeholder="https://images.unsplash.com/...">
          </div>

          <div class="form-group full-width">
            <label class="form-label" for="prod-descripcion">Descripción</label>
            <textarea id="prod-descripcion" class="form-input" rows="2" placeholder="Detalles del producto..."></textarea>
          </div>
        </div>

        <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1.5rem;">
          <button type="button" id="btn-cancel-modal" class="btn-action" style="background: transparent; color: var(--text-muted);">Cancelar</button>
          <button type="submit" id="btn-save-prod" class="btn-action" style="background: var(--primary); color: white; padding: 0.75rem 1.5rem;">Guardar Producto</button>
        </div>
      </form>
    </div>
  </div>

  <div id="toast-container" class="toast-container"></div>

  <script>
    let productsList = [];

    // Tabs logic
    document.querySelectorAll('.tab-btn').forEach(btn => {
      btn.addEventListener('click', (e) => {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.style.display = 'none');
        
        e.currentTarget.classList.add('active');
        document.getElementById(e.currentTarget.dataset.tab).style.display = 'block';
      });
    });

    // Cargar Inventario de Productos desde la API
    async function loadProducts() {
      try {
        const res = await fetch('../api/get_products.php');
        const json = await res.json();
        
        if (json.status === 'success') {
          productsList = json.data;
          renderProductsTable();
          updateStats();
        }
      } catch (err) {
        console.error(err);
      }
    }

    function renderProductsTable() {
      const tbody = document.getElementById('products-table-body');
      if (productsList.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding: 2rem;">No hay productos en inventario.</td></tr>';
        return;
      }

      tbody.innerHTML = productsList.map(prod => `
        <tr>
          <td><img src="${prod.imagen_url}" alt="${prod.nombre}" style="width: 44px; height: 44px; border-radius: 8px; object-fit: cover;"></td>
          <td><strong>${prod.nombre}</strong></td>
          <td><span style="text-transform: uppercase; font-size: 0.75rem; color: var(--primary); font-weight:700;">${prod.categoria}</span></td>
          <td><strong style="color: #38bdf8;">$ ${prod.precio.toLocaleString('es-AR', {minimumFractionDigits: 2})}</strong></td>
          <td>
            <span class="badge-status ${prod.stock < 10 ? 'badge-rejected' : 'badge-approved'}">
              ${prod.stock} u.
            </span>
          </td>
          <td>${prod.destacado ? '⭐ Sí' : 'No'}</td>
          <td>
            <div style="display: flex; gap: 0.5rem;">
              <button class="btn-action btn-edit" onclick="editProduct(${prod.id})">✏️ Editar</button>
              <button class="btn-action btn-delete" onclick="deleteProduct(${prod.id})">🗑️</button>
            </div>
          </td>
        </tr>
      `).join('');
    }

    // Cargar Órdenes Mercado Pago
    async function loadOrders() {
      try {
        const res = await fetch('../api/admin/get_orders.php');
        const json = await res.json();

        if (json.status === 'success') {
          renderOrdersTable(json.data);
          document.getElementById('stat-revenue').textContent = `$ ${json.stats.total_revenue.toLocaleString('es-AR', {minimumFractionDigits: 2})}`;
          document.getElementById('stat-orders-count').textContent = json.stats.total_orders;
        }
      } catch (err) {
        console.error(err);
      }
    }

    function renderOrdersTable(orders) {
      const tbody = document.getElementById('orders-table-body');
      if (!orders || orders.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding: 2rem;">No hay órdenes registradas aún.</td></tr>';
        return;
      }

      tbody.innerHTML = orders.map(ord => {
        const badgeClass = ord.estado === 'approved' ? 'badge-approved' : (ord.estado === 'pending' ? 'badge-pending' : 'badge-rejected');
        const itemsText = ord.items.map(i => `${i.cantidad}x ${i.producto_nombre || 'Producto'}`).join(', ');

        return `
          <tr>
            <td><code style="color: #a5b4fc;">${ord.external_reference}</code></td>
            <td><strong style="color: #38bdf8;">$ ${ord.monto_total.toLocaleString('es-AR', {minimumFractionDigits: 2})}</strong></td>
            <td><span class="badge-status ${badgeClass}">${ord.estado}</span></td>
            <td>${ord.mp_payment_id || 'N/A'}</td>
            <td style="font-size: 0.85rem; color: var(--text-muted);">${itemsText}</td>
            <td style="font-size: 0.8rem;">${ord.created_at}</td>
          </tr>
        `;
      }).join('');
    }

    function updateStats() {
      document.getElementById('stat-products-count').textContent = productsList.length;
      const lowStockCount = productsList.filter(p => p.stock < 10).length;
      document.getElementById('stat-low-stock').textContent = lowStockCount;
    }

    // Modal Control
    const modal = document.getElementById('product-modal');
    const form = document.getElementById('product-form');

    document.getElementById('btn-new-product').addEventListener('click', () => {
      document.getElementById('modal-title').textContent = '➕ Agregar Nuevo Producto';
      document.getElementById('prod-id').value = '0';
      form.reset();
      modal.classList.add('open');
    });

    document.getElementById('btn-close-modal').addEventListener('click', () => modal.classList.remove('open'));
    document.getElementById('btn-cancel-modal').addEventListener('click', () => modal.classList.remove('open'));

    window.editProduct = function(id) {
      const prod = productsList.find(p => p.id === id);
      if (!prod) return;

      document.getElementById('modal-title').textContent = '✏️ Editar Producto';
      document.getElementById('prod-id').value = prod.id;
      document.getElementById('prod-nombre').value = prod.nombre;
      document.getElementById('prod-categoria').value = prod.categoria;
      document.getElementById('prod-precio').value = prod.precio;
      document.getElementById('prod-stock').value = prod.stock;
      document.getElementById('prod-destacado').value = prod.destacado ? '1' : '0';
      document.getElementById('prod-imagen').value = prod.imagen_url;
      document.getElementById('prod-descripcion').value = prod.descripcion || '';

      modal.classList.add('open');
    };

    window.deleteProduct = async function(id) {
      if (!confirm('¿Estás seguro de eliminar este producto?')) return;

      try {
        const res = await fetch('../api/admin/delete_product.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id })
        });
        const json = await res.json();
        if (res.ok) {
          alert(json.message);
          loadProducts();
        } else {
          alert(json.message || 'Error al eliminar');
        }
      } catch (err) {
        alert('Error de conexión');
      }
    };

    form.addEventListener('submit', async (e) => {
      e.preventDefault();

      const payload = {
        id: parseInt(document.getElementById('prod-id').value, 10),
        nombre: document.getElementById('prod-nombre').value,
        categoria: document.getElementById('prod-categoria').value,
        precio: parseFloat(document.getElementById('prod-precio').value),
        stock: parseInt(document.getElementById('prod-stock').value, 10),
        destacado: document.getElementById('prod-destacado').value === '1',
        imagen_url: document.getElementById('prod-imagen').value,
        descripcion: document.getElementById('prod-descripcion').value
      };

      try {
        const res = await fetch('../api/admin/save_product.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const json = await res.json();

        if (res.ok && json.status === 'success') {
          modal.classList.remove('open');
          loadProducts();
        } else {
          alert(json.message || 'Error al guardar producto');
        }
      } catch (err) {
        alert('Error de conexión con el servidor');
      }
    });

    // Carga inicial
    loadProducts();
    loadOrders();
  </script>
</body>
</html>
