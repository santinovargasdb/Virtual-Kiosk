/**
 * Módulo del Carrito de Compras en tiempo real con almacenamiento local (localStorage)
 */
const STORAGE_KEY = 'kiosco_online_cart_v1';

class CartStore {
  constructor() {
    this.items = this.loadFromStorage();
    this.listeners = [];
  }

  loadFromStorage() {
    try {
      const stored = localStorage.getItem(STORAGE_KEY);
      return stored ? JSON.parse(stored) : [];
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

  subscribe(listener) {
    this.listeners.push(listener);
  }

  notify() {
    this.saveToStorage();
    this.listeners.forEach(fn => fn(this.getItems(), this.getTotal(), this.getItemCount()));
  }

  getItems() {
    return [...this.items];
  }

  addItem(product) {
    const existingIndex = this.items.findIndex(item => item.id === product.id);

    if (existingIndex > -1) {
      const currentQty = this.items[existingIndex].quantity;
      if (currentQty < product.stock) {
        this.items[existingIndex].quantity += 1;
      } else {
        throw new Error(`¡Has alcanzado el límite de stock disponible (${product.stock} u.)!`);
      }
    } else {
      if (product.stock < 1) {
        throw new Error('Producto sin stock disponible.');
      }
      this.items.push({
        id: product.id,
        nombre: product.nombre,
        precio: parseFloat(product.precio),
        imagen_url: product.imagen_url,
        stock: product.stock,
        quantity: 1
      });
    }

    this.notify();
  }

  updateQuantity(productId, delta) {
    const index = this.items.findIndex(item => item.id === productId);
    if (index > -1) {
      const newQty = this.items[index].quantity + delta;
      if (newQty <= 0) {
        this.removeItem(productId);
        return;
      }

      if (newQty > this.items[index].stock) {
        throw new Error(`Solo hay ${this.items[index].stock} unidades disponibles.`);
      }

      this.items[index].quantity = newQty;
      this.notify();
    }
  }

  removeItem(productId) {
    this.items = this.items.filter(item => item.id !== productId);
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
