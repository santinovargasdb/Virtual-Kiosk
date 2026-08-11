/**
 * Módulo del Carrito de Compras del Coffee Shop (Equipo 8).
 * - Cada ítem del carrito es un "café personalizado": el mismo producto con
 *   distinta leche/azúcar/Sin TACC se guarda como una variante independiente.
 * - El estado persiste en localStorage y se espeja en $_SESSION['carrito']
 *   del servidor vía api/cart_session.php en cada cambio.
 * - También administra el horario de retiro Take Away del pedido completo.
 */
const STORAGE_KEY = 'octava_cafe_cart_v2';
const PICKUP_KEY = 'octava_cafe_pickup_v1';

class CartStore {
  constructor() {
    this.items = this.loadFromStorage();
    this.pickupTime = this.loadPickupTime();
    this.listeners = [];
  }

  loadFromStorage() {
    try {
      const stored = localStorage.getItem(STORAGE_KEY);
      const parsed = stored ? JSON.parse(stored) : [];
      return Array.isArray(parsed) ? parsed.filter(item => item && item.uid) : [];
    } catch (e) {
      console.error('Error al cargar carrito desde localStorage:', e);
      return [];
    }
  }

  saveToStorage() {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(this.items));
    } catch (e) {
      console.error('Error al guardar carrito en localStorage:', e);
    }
  }

  loadPickupTime() {
    try {
      const stored = localStorage.getItem(PICKUP_KEY);
      return stored && /^\d{2}:\d{2}$/.test(stored) ? stored : null;
    } catch (e) {
      return null;
    }
  }

  savePickupTime() {
    try {
      if (this.pickupTime) {
        localStorage.setItem(PICKUP_KEY, this.pickupTime);
      } else {
        localStorage.removeItem(PICKUP_KEY);
      }
    } catch (e) {
      console.error('Error al guardar horario de retiro:', e);
    }
  }

  subscribe(listener) {
    this.listeners.push(listener);
  }

  notify() {
    this.saveToStorage();
    this.savePickupTime();
    this.syncSession();
    this.listeners.forEach(fn => fn(this.getItems(), this.getTotal(), this.getItemCount()));
  }

  /**
   * Espeja el carrito completo (con personalizaciones y horario de retiro)
   * en la sesión PHP del servidor: $_SESSION['carrito'].
   */
  syncSession() {
    try {
      fetch('api/cart_session.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          items: this.items.map(item => ({
            id: item.id,
            nombre: item.nombre,
            precio: item.precio,
            quantity: item.quantity,
            personalizacion: item.personalizacion
          })),
          horario_retiro: this.pickupTime
        }),
        keepalive: true
      }).catch(() => { /* la sesión se re-sincroniza en el próximo cambio */ });
    } catch (e) {
      // El carrito local sigue funcionando aunque el servidor no responda
    }
  }

  getItems() {
    return [...this.items];
  }

  getPickupTime() {
    return this.pickupTime;
  }

  setPickupTime(time) {
    if (time && /^\d{2}:\d{2}$/.test(time)) {
      this.pickupTime = time;
    } else {
      this.pickupTime = null;
    }
    this.notify();
  }

  /**
   * Identificador único de la variante: producto + combinación exacta
   * de personalización elegida por el cliente.
   */
  buildUid(productId, personalizacion) {
    const p = personalizacion || {};
    const clave = [
      productId,
      p.leche || '-',
      p.azucar || '-',
      p.sin_tacc ? 'tacc' : '-',
      (p.nota || '').trim().toLowerCase()
    ].join('|');

    // Hash simple y estable para no guardar notas largas dentro del uid
    let hash = 0;
    for (let i = 0; i < clave.length; i++) {
      hash = ((hash << 5) - hash + clave.charCodeAt(i)) | 0;
    }
    return `${productId}-${Math.abs(hash).toString(36)}`;
  }

  /** Total de unidades pedidas de un producto sumando todas sus variantes. */
  getQuantityByProduct(productId) {
    return this.items
      .filter(item => item.id === productId)
      .reduce((acc, item) => acc + item.quantity, 0);
  }

  /**
   * Agrega un café personalizado al pedido.
   * @param {Object} product  Producto del catálogo (con stock y personalizable)
   * @param {Object} personalizacion  { leche, azucar, sin_tacc, nota }
   * @param {number} quantity Cantidad de unidades de esta variante
   */
  addItem(product, personalizacion = {}, quantity = 1) {
    const qty = Math.max(1, parseInt(quantity, 10) || 1);
    const enCarrito = this.getQuantityByProduct(product.id);

    if (product.stock < enCarrito + qty) {
      const disponible = Math.max(0, product.stock - enCarrito);
      throw new Error(
        disponible > 0
          ? `Solo quedan ${disponible} unidades disponibles de "${product.nombre}".`
          : `¡Has alcanzado el límite de stock disponible (${product.stock} u.)!`
      );
    }

    const uid = this.buildUid(product.id, personalizacion);
    const existingIndex = this.items.findIndex(item => item.uid === uid);

    if (existingIndex > -1) {
      this.items[existingIndex].quantity += qty;
    } else {
      this.items.push({
        uid,
        id: product.id,
        nombre: product.nombre,
        precio: parseFloat(product.precio),
        imagen_url: product.imagen_url,
        stock: product.stock,
        personalizable: !!product.personalizable,
        quantity: qty,
        personalizacion: {
          leche: personalizacion.leche || null,
          azucar: personalizacion.azucar || null,
          sin_tacc: !!personalizacion.sin_tacc,
          nota: (personalizacion.nota || '').trim()
        }
      });
    }

    this.notify();
  }

  updateQuantity(uid, delta) {
    const index = this.items.findIndex(item => item.uid === uid);
    if (index === -1) return;

    const item = this.items[index];
    const newQty = item.quantity + delta;

    if (newQty <= 0) {
      this.removeItem(uid);
      return;
    }

    // Validar stock sumando el resto de variantes del mismo producto
    const otrasVariantes = this.getQuantityByProduct(item.id) - item.quantity;
    if (newQty + otrasVariantes > item.stock) {
      throw new Error(`Solo hay ${item.stock} unidades disponibles de "${item.nombre}".`);
    }

    item.quantity = newQty;
    this.notify();
  }

  removeItem(uid) {
    this.items = this.items.filter(item => item.uid !== uid);
    this.notify();
  }

  clearCart() {
    this.items = [];
    this.notify();
  }

  getTotal() {
    return this.items.reduce((total, item) => total + (item.precio * item.quantity), 0);
  }

  getItemCount() {
    return this.items.reduce((count, item) => count + item.quantity, 0);
  }
}

export const Cart = new CartStore();
