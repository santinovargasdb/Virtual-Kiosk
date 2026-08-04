# 🏪 Kiosco Online - Full Stack PHP + MySQL + Mercado Pago

Aplicación web full stack para un **Kiosco Online**, con catálogo interactivo en tiempo real, carrito de compras responsivo con persistencia local e integración oficial con la API de cobros de **Mercado Pago** (Checkout Pro y Webhooks IPN).

---

## 🛠️ Stack Tecnológico

- **Frontend:** HTML5, CSS3 Nativo (Variables CSS, Glassmorphism, Responsive Grid, Animations) y JavaScript Vanilla (ES Modules, reactive state management).
- **Backend:** PHP 8.x con MySQLi (Orientado a Objetos) para consultas seguras preparadas y comunicación cURL con la API REST de Mercado Pago.
- **Base de Datos:** MySQL (con motor InnoDB, llaves foráneas y transacciones ACID).
- **Pasarela de Pagos:** Mercado Pago API / SDK (Preferencia de Pago, `back_urls`, `auto_return` e IPN Webhook Listener).

---

## 📂 Estructura del Proyecto

```text
Antigravity/
├── config/
│   └── database.php        # Conexión MySQLi a MySQL (Singleton)
├── db/
│   └── schema.sql          # Tablas (productos, ordenes, orden_items) + Seed Data
├── api/
│   ├── get_products.php    # Endpoint REST JSON para consultar productos
│   ├── create_preference.php # Endpoint REST para crear la orden y la preferencia MP
│   └── webhook.php         # Receptor IPN / Webhook para actualizaciones de pago MP
├── public/
│   ├── css/
│   │   └── styles.css      # Sistema de diseño UI/UX (Kiosco Dark Glassmorphism)
│   ├── js/
│   │   ├── api.js          # Módulo Fetch API frontend
│   │   ├── cart.js         # Estado reactivo del carrito de compras (localStorage)
│   │   └── app.js          # Lógica del catálogo, filtros de categoría y checkout
│   ├── success.php         # Vista de retorno: Pago Aprobado
│   ├── pending.php         # Vista de retorno: Pago Pendiente
│   └── failure.php         # Vista de retorno: Pago Fallido / Rechazado
├── index.php               # Portal principal del Kiosco Online
├── env.php                 # Configuración de credenciales DB y Access Token de Mercado Pago
├── .env.example            # Ejemplo de variables de entorno
└── README.md               # Guía de instalación y documentación
```

---

## 🚀 Pasos de Instalación en XAMPP

### 1. Ubicación del Proyecto
Asegúrate de colocar la carpeta del proyecto dentro del directorio de Apache de XAMPP:
```
C:\xampp\htdocs\2026\Antigravity
```

### 2. Importar la Base de Datos en MySQL
1. Inicia **Apache** y **MySQL** desde el Panel de Control de XAMPP.
2. Abre **phpMyAdmin** (`http://localhost/phpmyadmin/`) o tu cliente MySQL (HeidiSQL, DBeaver, MySQL Workbench).
3. Importa o ejecuta el archivo SQL ubicado en:
   `db/schema.sql`
   *Esto creará automáticamente la base de datos `kiosco_online`, sus 3 tablas con llaves foráneas y cargará 10 productos de prueba.*

### 3. Configurar Credenciales de Mercado Pago
Abre el archivo `env.php` (o configura tu archivo `.env`) e ingresa tu **Access Token** y **Public Key** de prueba obtenidas desde el [Panel de Desarrolladores de Mercado Pago](https://www.mercadopago.com.ar/developers/panel/credentials):

```php
define('MP_ACCESS_TOKEN', 'TEST-1234567890123456-072823-abcdef1234567890abcdef1234567890-123456789');
define('MP_PUBLIC_KEY', 'TEST-abcdef12-3456-7890-abcd-ef1234567890');
```

---

## 💻 Proceso de Pruebas y Uso

1. Ingresa desde tu navegador a:
   `http://localhost/2026/Antigravity`
2. **Explora el Catálogo**: Utiliza las pestañas de categorías (Bebidas, Golosinas, Snacks) o la barra de búsqueda en tiempo real.
3. **Agrega Productos**: Haz clic en "🛒 Agregar" para incorporar ítems a tu carrito.
4. **Revisa tu Pedido**: Abre el panel deslizable del carrito haciendo clic en "Mi Carrito". Modifica cantidades o elimina ítems.
5. **Checkout**: Haz clic en **"Pagar con Mercado Pago"**. Serás redirigido a la pasarela de prueba de Mercado Pago.
6. **Webhook IPN**: Mercado Pago notificará a `api/webhook.php`, el cual actualizará el estado de la orden en MySQL (`approved`, `rejected`) y descontará el stock de los productos.
