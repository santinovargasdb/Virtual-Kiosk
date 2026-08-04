import { API } from './api.js';
import { Cart } from './cart.js';

document.addEventListener('DOMContentLoaded', () => {
  // Estado local de la aplicación
  let currentCategory = 'todos';
  let searchQuery = '';
  let products = [];

  // Elementos DOM
  const productsGrid = document.getElementById('products-grid');
  const searchInput = document.getElementById('search-input');
  const categoryBtns = document.querySelectorAll('.category-btn');
  const cartTrigger = document.getElementById('cart-trigger');
  const cartBadge = document.getElementById('cart-badge');
  const cartOverlay = document.getElementById('cart-overlay');
  const cartDrawer = document.getElementById('cart-drawer');
  const cartCloseBtn = document.getElementById('cart-close-btn');
  const cartBody = document.getElementById('cart-body');
  const cartTotalPrice = document.getElementById('cart-total-price');
  const btnCheckout = document.getElementById('btn-checkout-mp');
  const btnClearCart = document.getElementById('btn-clear-cart');
  const toastContainer = document.getElementById('toast-container');

  // Utility: Mostrar notificaciones Toast
  function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
      <span>${message}</span>
    `;
    toastContainer.appendChild(toast);
    setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.transform = 'translateX(-100%)';
      setTimeout(() => toast.remove(), 300);
    }, 3000);
  }

  // Cargar productos desde el Backend PHP MySQL
  async function loadProducts() {
    productsGrid.innerHTML = `
      <div style="grid-column: 1/-1; text-align: center; padding: 4rem 1rem;">
        <div class="spinner" style="margin: 0 auto 1rem auto; width: 32px; height: 32px;"></div>
        <p style="color: var(--text-muted);">Cargando catálogo del kiosco...</p>
      </div>
    `;

    try {
      products = await API.getProducts(currentCategory, searchQuery);
      renderProducts();
    } catch (error) {
      productsGrid.innerHTML = `
        <div style="grid-column: 1/-1; text-align: center; padding: 4rem 1rem; color: var(--danger);">
          <p style="font-size: 1.2rem; font-weight: bold; margin-bottom: 0.5rem;">⚠️ Error al cargar catálogo</p>
          <p style="color: var(--text-muted);">${error.message}</p>
        </div>
      `;
    }
  }

  // Renderizar la grilla de productos
  function renderProducts() {
    if (products.length === 0) {
      productsGrid.innerHTML = `
        <div style="grid-column: 1/-1; text-align: center; padding: 4rem 1rem; color: var(--text-muted);">
          <p style="font-size: 2.5rem; margin-bottom: 0.5rem;">🔍</p>
          <p style="font-size: 1.1rem; font-weight: 600;">No se encontraron productos</p>
          <p style="font-size: 0.9rem;">Prueba cambiando de categoría o término de búsqueda.</p>
        </div>
      `;
      return;
    }

    productsGrid.innerHTML = products.map(prod => `
      <article class="product-card" data-id="${prod.id}">
        ${prod.destacado ? `<span class="card-badge-featured">⭐ Destacado</span>` : ''}
        <div class="product-image-container">
          <img src="${prod.imagen_url}" alt="${prod.nombre}" class="product-image" loading="lazy" onError="this.src='https://images.unsplash.com/photo-1542838132-92c53300491e?w=500&q=80'">
        </div>
        <div class="product-info">
          <span class="product-category">${prod.categoria}</span>
          <h3 class="product-title">${prod.nombre}</h3>
          <p class="product-description">${prod.descripcion || ''}</p>
          <div class="product-footer">
            <span class="product-price">$ ${prod.precio.toLocaleString('es-AR', { minimumFractionDigits: 2 })}</span>
            <button class="add-to-cart-btn" data-id="${prod.id}" ${prod.stock <= 0 ? 'disabled' : ''}>
              ${prod.stock <= 0 ? 'Agotado' : '🛒 Agregar'}
            </button>
          </div>
        </div>
      </article>
    `).join('');

    // Asignar listeners a botones de agregar
    document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
      btn.addEventListener('click', (e) => {
        const prodId = parseInt(e.currentTarget.dataset.id, 10);
        const targetProduct = products.find(p => p.id === prodId);
        if (targetProduct) {
          try {
            Cart.addItem(targetProduct);
            showToast(`¡"${targetProduct.nombre}" agregado al carrito!`, 'success');
          } catch (err) {
            showToast(err.message, 'warning');
          }
        }
      });
    });
  }

  // Toggle del Drawer de Carrito
  function toggleCart(open = true) {
    if (open) {
      cartOverlay.classList.add('open');
      cartDrawer.classList.add('open');
    } else {
      cartOverlay.classList.remove('open');
      cartDrawer.classList.remove('open');
    }
  }

  cartTrigger.addEventListener('click', () => toggleCart(true));
  cartCloseBtn.addEventListener('click', () => toggleCart(false));
  cartOverlay.addEventListener('click', () => toggleCart(false));

  // Renderizar el contenido del carrito en tiempo real
  function renderCart(items, total, count) {
    cartBadge.textContent = count;

    if (items.length === 0) {
      cartBody.innerHTML = `
        <div class="cart-empty">
          <div class="cart-empty-icon">🛒</div>
          <p style="font-weight: 600; font-size: 1.1rem; color: var(--text-main); margin-bottom: 0.25rem;">Tu carrito está vacío</p>
          <p style="font-size: 0.9rem;">Agrega algunos golosinas o bebidas para comenzar.</p>
        </div>
      `;
      cartTotalPrice.textContent = '$ 0,00';
      btnCheckout.disabled = true;
      if (btnClearCart) btnClearCart.style.display = 'none';
      return;
    }

    if (btnClearCart) btnClearCart.style.display = 'block';
    btnCheckout.disabled = false;
    cartTotalPrice.textContent = `$ ${total.toLocaleString('es-AR', { minimumFractionDigits: 2 })}`;

    cartBody.innerHTML = items.map(item => `
      <div class="cart-item" data-id="${item.id}">
        <img src="${item.imagen_url}" alt="${item.nombre}" class="cart-item-img">
        <div class="cart-item-details">
          <h4 class="cart-item-title">${item.nombre}</h4>
          <span class="cart-item-price">$ ${item.precio.toLocaleString('es-AR', { minimumFractionDigits: 2 })}</span>
          <div class="cart-item-controls">
            <button class="qty-btn btn-minus" data-id="${item.id}">-</button>
            <span class="qty-value">${item.quantity}</span>
            <button class="qty-btn btn-plus" data-id="${item.id}">+</button>
          </div>
        </div>
        <button class="remove-item-btn" data-id="${item.id}" title="Quitar producto">🗑️</button>
      </div>
    `).join('');

    // Listeners de control de cantidad e items
    cartBody.querySelectorAll('.btn-minus').forEach(btn => {
      btn.addEventListener('click', (e) => {
        const id = parseInt(e.currentTarget.dataset.id, 10);
        Cart.updateQuantity(id, -1);
      });
    });

    cartBody.querySelectorAll('.btn-plus').forEach(btn => {
      btn.addEventListener('click', (e) => {
        const id = parseInt(e.currentTarget.dataset.id, 10);
        try {
          Cart.updateQuantity(id, 1);
        } catch (err) {
          showToast(err.message, 'warning');
        }
      });
    });

    cartBody.querySelectorAll('.remove-item-btn').forEach(btn => {
      btn.addEventListener('click', (e) => {
        const id = parseInt(e.currentTarget.dataset.id, 10);
        Cart.removeItem(id);
      });
    });
  }

  // Suscribir rendering de carrito a cambios de estado
  Cart.subscribe(renderCart);
  // Carga inicial de carrito
  renderCart(Cart.getItems(), Cart.getTotal(), Cart.getItemCount());

  // Limpiar Carrito
  if (btnClearCart) {
    btnClearCart.addEventListener('click', () => {
      if (confirm('¿Deseas vaciar todos los productos del carrito?')) {
        Cart.clearCart();
      }
    });
  }

  // Evento Checkout Mercado Pago
  btnCheckout.addEventListener('click', async () => {
    const items = Cart.getItems();
    if (items.length === 0) return;

    btnCheckout.disabled = true;
    const originalText = btnCheckout.innerHTML;
    btnCheckout.innerHTML = `
      <div class="spinner"></div>
      <span>Generando Pago...</span>
    `;

    try {
      const response = await API.createCheckoutPreference(items);
      showToast('¡Redirigiendo a Mercado Pago!', 'success');

      // Usar siempre init_point oficial (Mercado Pago maneja el modo Prueba o Producción según el Access Token)
      const redirectUrl = response.init_point || response.sandbox_init_point;
      
      setTimeout(() => {
        window.location.href = redirectUrl;
      }, 800);

    } catch (error) {
      showToast(error.message || 'Error al conectar con la pasarela de pago.', 'danger');
      btnCheckout.disabled = false;
      btnCheckout.innerHTML = originalText;
    }
  });

  // Filtros de Categorías
  categoryBtns.forEach(btn => {
    btn.addEventListener('click', (e) => {
      categoryBtns.forEach(b => b.classList.remove('active'));
      e.currentTarget.classList.add('active');
      currentCategory = e.currentTarget.dataset.category;
      loadProducts();
    });
  });

  // Buscador con debounce
  let searchTimeout = null;
  searchInput.addEventListener('input', (e) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
      searchQuery = e.target.value.trim();
      loadProducts();
    }, 350);
  });

  // Inicializar productos
  loadProducts();
});
