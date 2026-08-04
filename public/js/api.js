/**
 * Módulo de API para la comunicación entre Frontend JS y el Backend PHP
 */
export const API = {
  /**
   * Obtiene los productos desde el backend PHP MySQL
   * @param {string} category 
   * @param {string} searchQuery 
   * @returns {Promise<Array>}
   */
  async getProducts(category = '', searchQuery = '') {
    try {
      const params = new URLSearchParams();
      if (category && category !== 'todos') params.append('category', category);
      if (searchQuery) params.append('q', searchQuery);

      const response = await fetch(`api/get_products.php?${params.toString()}`);
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      const json = await response.json();
      if (json.status === 'success') {
        return json.data;
      }
      throw new Error(json.message || 'Error al obtener productos.');
    } catch (error) {
      console.error('API Error [getProducts]:', error);
      throw error;
    }
  },

  /**
   * Envía los productos del carrito al backend para registrar la orden en MySQL
   * y generar la Preferencia de Pago con Mercado Pago.
   * @param {Array} cartItems 
   * @returns {Promise<Object>}
   */
  async createCheckoutPreference(cartItems) {
    try {
      const response = await fetch('api/create_preference.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          items: cartItems.map(item => ({
            id: item.id,
            quantity: item.quantity
          }))
        })
      });

      const json = await response.json();
      if (!response.ok || json.status !== 'success') {
        throw new Error(json.message || 'No se pudo generar la preferencia de Mercado Pago.');
      }
      return json;
    } catch (error) {
      console.error('API Error [createCheckoutPreference]:', error);
      throw error;
    }
  }
};
