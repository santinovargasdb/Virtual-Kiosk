import { API } from './api.js';
import { Cart } from './cart.js';

document.addEventListener('DOMContentLoaded', () => {
  // Estado local de la aplicación
  let currentCategory = 'todos';
  let searchQuery = '';
  let products = [];
  let modalProduct = null;
  let modalQty = 1;

  // Horario de atención del local para retiros Take Away
  const PICKUP_START = '08:00';
  const PICKUP_END = '20:00';
  const PICKUP_STEP_MIN = 15;

  // Etiquetas legibles de las opciones de personalización
  const LECHE_LABELS = { entera: 'Entera', almendras: 'Almendras', deslactosada: 'Deslactosada' };
  const AZUCAR_LABELS = { sin_azucar: 'Sin azúcar', poco: 'Poco', normal: 'Normal', dulce: 'Dulce' };

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
  const cartPickupSelect = document.getElementById('cart-pickup');
  const btnCheckout = document.getElementById('btn-checkout-mp');
  const btnClearCart = document.getElementById('btn-clear-cart');
  const toastContainer = document.getElementById('toast-container');

  // Modal de personalización
  const customModal = document.getElementById('custom-modal');
  const customForm = document.getElementById('custom-form');
  const customClose = document.getElementById('custom-close');
  const customImg = document.getElementById('custom-img');
  const customCategory = document.getElementById('custom-category');
  const customTitle = document.getElementById('custom-title');
  const customPrice = document.getElementById('custom-price');
  const customDrinkOptions = document.getElementById('custom-drink-options');
  const customSinTacc = document.getElementById('custom-sin-tacc');
  const customNota = document.getElementById('custom-nota');
  const customPickupSelect = document.getElementById('custom-pickup');
  const customQtyValue = document.getElementById('custom-qty-value');
  const customSubtotal = document.getElementById('custom-subtotal');

  // Utility: Formato de precio argentino
  const formatPrice = (value) =>
    `$ ${value.toLocaleString('es-AR', { minimumFractionDigits: 2 })}`;

  // Utility: Escapar texto ingresado por el usuario antes de inyectarlo en HTML
  function esc(text) {
    const div = document.createElement('div');
    div.textContent = text ?? '';
    return div.innerHTML;
  }

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

  /* ============================================================
     HORARIO DE RETIRO TAKE AWAY (franjas de 15 minutos)
     ============================================================ */

  function buildPickupSlots() {
    const slots = [];
    const [startH, startM] = PICKUP_START.split(':').map(Number);
    const [endH, endM] = PICKUP_END.split(':').map(Number);

    for (let mins = startH * 60 + startM; mins <= endH * 60 + endM; mins += PICKUP_STEP_MIN) {
      const h = String(Math.floor(mins / 60)).padStart(2, '0');
      const m = String(mins % 60).padStart(2, '0');
      slots.push(`${h}:${m}`);
    }
    return slots;
  }

  function availablePickupSlots() {
    const all = buildPickupSlots();
    // Margen de 15 minutos para que el barista llegue a preparar el pedido
    const now = new Date(Date.now() + PICKUP_STEP_MIN * 60000);
    const nowStr = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
    const futuros = all.filter(slot => slot >= nowStr);
    // Si el local ya cerró, se ofrecen todas las franjas (pedido para mañana)
    return futuros.length > 0 ? futuros : all;
  }

  function populatePickupSelects() {
    const slots = availablePickupSlots();
    const current = Cart.getPickupTime();
    const selected = current && slots.includes(current) ? current : slots[0];

    [cartPickupSelect, customPickupSelect].forEach(select => {
      select.innerHTML = slots
        .map(slot => `<option value="${slot}" ${slot === selected ? 'selected' : ''}>${slot} hs</option>`)
        .join('');
    });

    if (selected !== current) {
      Cart.setPickupTime(selected);
    }
  }

  // Ambos selectores (drawer y modal) comparten el mismo horario del pedido
  cartPickupSelect.addEventListener('change', (e) => Cart.setPickupTime(e.target.value));
  customPickupSelect.addEventListener('change', (e) => Cart.setPickupTime(e.target.value));

  function syncPickupSelects() {
    const time = Cart.getPickupTime();
    if (!time) return;
    [cartPickupSelect, customPickupSelect].forEach(select => {
      if (select.value !== time && [...select.options].some(o => o.value === time)) {
        select.value = time;
      }
    });
  }

  /* ============================================================
     CATÁLOGO DE LA CARTA
     ============================================================ */

  // Cargar productos desde el Backend PHP MySQL
  async function loadProducts() {
    productsGrid.innerHTML = `
      <div style="grid-column: 1/-1; text-align: center; padding: 4rem 1rem;">
        <div class="spinner" style="margin: 0 auto 1rem auto; width: 32px; height: 32px;"></div>
        <p style="color: var(--text-muted);">Moliendo el catálogo de la cafetería...</p>
      </div>
    `;

    try {
      products = await API.getProducts(currentCategory, searchQuery);
      renderProducts();
    } catch (error) {
      productsGrid.innerHTML = `
        <div style="grid-column: 1/-1; text-align: center; padding: 4rem 1rem; color: var(--danger);">
          <p style="font-size: 1.2rem; font-weight: bold; margin-bottom: 0.5rem;">⚠️ Error al cargar la carta</p>
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
          <p style="font-size: 1.1rem; font-weight: 600;">No encontramos nada con ese nombre</p>
          <p style="font-size: 0.9rem;">Probá cambiando de categoría o término de búsqueda.</p>
        </div>
      `;
      return;
    }

    productsGrid.innerHTML = products.map(prod => `
      <article class="product-card" data-id="${prod.id}">
        ${prod.destacado ? `<span class="card-badge-featured">⭐ De la casa</span>` : ''}
        <div class="product-image-container">
          <img src="${prod.imagen_url}" alt="${esc(prod.nombre)}" class="product-image" loading="lazy" onError="this.src='https://images.unsplash.com/photo-1447933601403-0c6688de566e?w=500&q=80'">
        </div>
        <div class="product-info">
          <span class="product-category">${esc(prod.categoria)}</span>
          <h3 class="product-title">${esc(prod.nombre)}</h3>
          <p class="product-description">${esc(prod.descripcion || '')}</p>
          <div class="product-footer">
            <span class="product-price">${formatPrice(prod.precio)}</span>
            <button class="add-to-cart-btn" data-id="${prod.id}" ${prod.stock <= 0 ? 'disabled' : ''}>
              ${prod.stock <= 0 ? 'Agotado' : (prod.personalizable ? '☕ Personalizar' : '🛒 Agregar')}
            </button>
          </div>
        </div>
      </article>
    `).join('');

    // Cada producto abre el modal de personalización antes de ir al carrito
    document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
      btn.addEventListener('click', (e) => {
        const prodId = parseInt(e.currentTarget.dataset.id, 10);
        const targetProduct = products.find(p => p.id === prodId);
        if (targetProduct) {
          openCustomModal(targetProduct);
        }
      });
    });
  }

  /* ============================================================
     MODAL DE PERSONALIZACIÓN DEL CAFÉ
     ============================================================ */

  function openCustomModal(product) {
    modalProduct = product;
    modalQty = 1;

    customImg.src = product.imagen_url;
    customImg.alt = product.nombre;
    customCategory.textContent = product.categoria;
    customTitle.textContent = product.nombre;
    customPrice.textContent = formatPrice(product.precio);

    // Reset del formulario a los valores por defecto
    customForm.reset();
    customQtyValue.textContent = '1';

    // Las opciones de leche/azúcar solo aplican a bebidas personalizables
    customDrinkOptions.style.display = product.personalizable ? 'block' : 'none';

    populatePickupSelects();
    updateModalSubtotal();

    customModal.classList.add('open');
  }

  function closeCustomModal() {
    customModal.classList.remove('open');
    modalProduct = null;
  }

  function updateModalSubtotal() {
    if (!modalProduct) return;
    customSubtotal.textContent = formatPrice(modalProduct.precio * modalQty);
  }

  customClose.addEventListener('click', closeCustomModal);
  customModal.addEventListener('click', (e) => {
    if (e.target === customModal) closeCustomModal();
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && customModal.classList.contains('open')) closeCustomModal();
  });

  document.getElementById('custom-qty-minus').addEventListener('click', () => {
    if (modalQty > 1) {
      modalQty--;
      customQtyValue.textContent = modalQty;
      updateModalSubtotal();
    }
  });

  document.getElementById('custom-qty-plus').addEventListener('click', () => {
    if (!modalProduct) return;
    const disponibles = modalProduct.stock - Cart.getQuantityByProduct(modalProduct.id);
    if (modalQty < disponibles) {
      modalQty++;
      customQtyValue.textContent = modalQty;
      updateModalSubtotal();
    } else {
      showToast(`Solo quedan ${Math.max(0, disponibles)} unidades disponibles.`, 'warning');
    }
  });

  // Confirmar personalización → agregar el café al pedido
  customForm.addEventListener('submit', (e) => {
    e.preventDefault();
    if (!modalProduct) return;

    const personalizacion = {
      leche: modalProduct.personalizable
        ? (customForm.querySelector('input[name="leche"]:checked')?.value || 'entera')
        : null,
      azucar: modalProduct.personalizable
        ? (customForm.querySelector('input[name="azucar"]:checked')?.value || 'normal')
        : null,
      sin_tacc: customSinTacc.checked,
      nota: customNota.value.trim()
    };

    try {
      Cart.setPickupTime(customPickupSelect.value);
      Cart.addItem(modalProduct, personalizacion, modalQty);
      showToast(`¡"${modalProduct.nombre}" agregado a tu pedido!`, 'success');
      closeCustomModal();
      toggleCart(true);
    } catch (err) {
      showToast(err.message, 'warning');
    }
  });

  /* ============================================================
     DRAWER DEL PEDIDO (CARRITO)
     ============================================================ */

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

  // Desglose legible de la personalización de un café del pedido
  function renderItemNotes(item) {
    const p = item.personalizacion || {};
    const lineas = [];

    if (p.leche) lineas.push(`🥛 Leche: ${LECHE_LABELS[p.leche] || p.leche}`);
    if (p.azucar) lineas.push(`🍬 Azúcar: ${AZUCAR_LABELS[p.azucar] || p.azucar}`);
    if (p.nota) lineas.push(`📝 ${esc(p.nota)}`);

    const tags = p.sin_tacc ? `<span class="tag-tacc">SIN TACC</span>` : '';
    const notas = lineas.length
      ? `<div class="cart-item-notes">${lineas.map(l => `<span>${l}</span>`).join('')}</div>`
      : '';

    return tags + notas;
  }

  // Renderizar el contenido del pedido en tiempo real
  function renderCart(items, total, count) {
    cartBadge.textContent = count;

    if (items.length === 0) {
      cartBody.innerHTML = `
        <div class="cart-empty">
          <div class="cart-empty-icon">☕</div>
          <p style="font-weight: 600; font-size: 1.1rem; color: var(--text-main); margin-bottom: 0.25rem;">Tu pedido está vacío</p>
          <p style="font-size: 0.9rem;">Elegí un café de la carta y personalizalo a tu gusto.</p>
        </div>
      `;
      cartTotalPrice.textContent = '$ 0,00';
      btnCheckout.disabled = true;
      if (btnClearCart) btnClearCart.style.display = 'none';
      return;
    }

    if (btnClearCart) btnClearCart.style.display = 'block';
    btnCheckout.disabled = false;
    cartTotalPrice.textContent = formatPrice(total);

    cartBody.innerHTML = items.map(item => `
      <div class="cart-item" data-uid="${item.uid}">
        <img src="${item.imagen_url}" alt="${esc(item.nombre)}" class="cart-item-img">
        <div class="cart-item-details">
          <h4 class="cart-item-title">${esc(item.nombre)}</h4>
          <span class="cart-item-price">${formatPrice(item.precio)} c/u</span>
          ${renderItemNotes(item)}
          <div class="cart-item-controls">
            <button class="qty-btn btn-minus" data-uid="${item.uid}" aria-label="Restar unidad">-</button>
            <span class="qty-value">${item.quantity}</span>
            <button class="qty-btn btn-plus" data-uid="${item.uid}" aria-label="Sumar unidad">+</button>
            <span class="cart-item-subtotal">${formatPrice(item.precio * item.quantity)}</span>
          </div>
        </div>
        <button class="remove-item-btn" data-uid="${item.uid}" title="Quitar del pedido">🗑️</button>
      </div>
    `).join('');

    // Listeners de control de cantidad e items (por variante personalizada)
    cartBody.querySelectorAll('.btn-minus').forEach(btn => {
      btn.addEventListener('click', (e) => {
        Cart.updateQuantity(e.currentTarget.dataset.uid, -1);
      });
    });

    cartBody.querySelectorAll('.btn-plus').forEach(btn => {
      btn.addEventListener('click', (e) => {
        try {
          Cart.updateQuantity(e.currentTarget.dataset.uid, 1);
        } catch (err) {
          showToast(err.message, 'warning');
        }
      });
    });

    cartBody.querySelectorAll('.remove-item-btn').forEach(btn => {
      btn.addEventListener('click', (e) => {
        Cart.removeItem(e.currentTarget.dataset.uid);
      });
    });

    syncPickupSelects();
  }

  // Suscribir rendering de carrito a cambios de estado
  Cart.subscribe(renderCart);
  // Carga inicial de carrito y franjas de retiro
  populatePickupSelects();
  renderCart(Cart.getItems(), Cart.getTotal(), Cart.getItemCount());

  // Limpiar Pedido
  if (btnClearCart) {
    btnClearCart.addEventListener('click', () => {
      if (confirm('¿Deseas vaciar todos los cafés y productos del pedido?')) {
        Cart.clearCart();
      }
    });
  }

  // Evento Checkout Mercado Pago
  btnCheckout.addEventListener('click', async () => {
    const items = Cart.getItems();
    if (items.length === 0) return;

    const pickupTime = Cart.getPickupTime() || cartPickupSelect.value;
    if (!pickupTime) {
      showToast('Elegí a qué hora pasás a retirar tu pedido.', 'warning');
      return;
    }

    btnCheckout.disabled = true;
    const originalText = btnCheckout.innerHTML;
    btnCheckout.innerHTML = `
      <div class="spinner"></div>
      <span>Generando Pago...</span>
    `;

    try {
      const response = await API.createCheckoutPreference(items, pickupTime);
      showToast(`¡Pedido registrado para las ${pickupTime} hs! Redirigiendo a Mercado Pago...`, 'success');

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

  // Inicializar la carta
  loadProducts();
});
