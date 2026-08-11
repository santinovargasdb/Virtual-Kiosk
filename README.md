# ☕ Octava Café — Kiosco Virtual / Coffee Shop (Equipo 8)

Aplicación web full stack de una **Cafetería y Bar de Especialidad** en modalidad **Take Away**: el cliente elige su café desde el catálogo online, lo **personaliza a su gusto** (tipo de leche, nivel de azúcar, opción Sin TACC), **selecciona a qué hora lo retira por el local** y paga a través de **Mercado Pago** (Checkout Pro + Webhooks IPN).

> Proyecto académico del **Equipo 8**, construido extendiendo el repositorio base de la cátedra
> [MaxGus1977/Kiosco-Online-MP](https://github.com/MaxGus1977/Kiosco-Online-MP), respetando su arquitectura
> (PHP 8 + MySQLi + JS Vanilla ES Modules + cURL a la API REST de Mercado Pago).

---

## 🛠️ Stack Tecnológico

- **Frontend:** HTML5, CSS3 nativo (variables CSS, responsive grid, paleta "tueste oscuro") y JavaScript Vanilla (ES Modules, estado reactivo del carrito con `localStorage`).
- **Backend:** PHP 8.x con MySQLi (orientado a objetos), sesiones PHP (`$_SESSION['carrito']`) y comunicación cURL con la API REST de Mercado Pago.
- **Base de Datos:** MySQL / MariaDB (motor InnoDB, llaves foráneas y transacciones ACID).
- **Pasarela de Pagos:** Mercado Pago (Preferencia de Pago, `back_urls`, `auto_return`, IPN Webhook).
- **Entorno:** variables en `.env` cargadas con `vlucas/phpdotenv` (opcional: sin Composer también funciona con los valores por defecto de XAMPP).

---

## ✨ Funcionalidades del Equipo 8 (sobre el repo base)

| Funcionalidad | Dónde vive |
|---|---|
| Modal de personalización por producto: **leche** (Entera / Almendras / Deslactosada), **azúcar** (Sin azúcar / Poco / Normal / Dulce), **checkbox Sin TACC**, nota para el barista y cantidad | `index.php` + `public/js/app.js` |
| **Selector de horario de retiro Take Away** (franjas de 15 min, 08:00–20:00, sincronizado entre el modal y el carrito) | `index.php` + `public/js/app.js` |
| Carrito con **variantes**: el mismo café con distinta personalización es un ítem independiente, con desglose completo y subtotales | `public/js/cart.js` |
| Espejado del carrito en **`$_SESSION['carrito']`** en cada cambio (productos + personalizaciones + horario) | `api/cart_session.php` + `public/js/cart.js` |
| Checkout que valida en servidor y guarda **`orden_items.notas_personalizacion`** y **`ordenes.horario_retiro`** | `api/create_preference.php` |
| Panel del barista: solapa **"Comandas"** con los pedidos entrantes como tickets de papel, con el detalle exacto de preparación y hora de retiro, **actualización automática cada 10 s** | `admin/index.php` |
| Columna `personalizable` en productos, gestionable desde el CRUD del admin | `db/schema.sql`, `api/admin/save_product.php` |
| Carta de coffee shop (Espresso, Latte, Capuchino, Flat White, V60, Cold Brew, pastelería) | `db/schema.sql` (seed) |

### Cambios de esquema respecto del repo base

```sql
ALTER TABLE productos   ADD COLUMN personalizable TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE ordenes     ADD COLUMN horario_retiro VARCHAR(10) NULL;
ALTER TABLE orden_items ADD COLUMN notas_personalizacion TEXT NULL;
```

El `db/schema.sql` ya incluye estas columnas en los `CREATE TABLE` **y** un bloque de migración
`ALTER TABLE ... ADD COLUMN IF NOT EXISTS` para bases creadas con el esquema original (XAMPP usa MariaDB, que lo soporta).

---

## 📂 Estructura del Proyecto

```text
ariza/
├── config/
│   ├── database.php          # Conexión MySQLi a MySQL (Singleton)
│   └── auth.php              # Sesiones y middleware de autenticación admin
├── db/
│   └── schema.sql            # Tablas + columnas Equipo 8 + seed de la carta
├── api/
│   ├── get_products.php      # Catálogo JSON (incluye flag personalizable)
│   ├── cart_session.php      # Espeja el carrito en $_SESSION['carrito']
│   ├── create_preference.php # Orden + notas_personalizacion + horario_retiro + Preferencia MP
│   ├── webhook.php           # IPN de Mercado Pago (actualiza estado y stock)
│   └── admin/                # Login admin + CRUD de productos + listado de órdenes
├── public/
│   ├── css/styles.css        # Design system "tueste oscuro" + comandas de papel
│   ├── js/
│   │   ├── api.js            # Fetch API (envía personalización y horario)
│   │   ├── cart.js           # Carrito con variantes + horario + sync de sesión
│   │   └── app.js            # Catálogo, modal de personalización y checkout
│   ├── success.php           # Pago aprobado (muestra horario de retiro)
│   ├── pending.php           # Pago pendiente
│   └── failure.php           # Pago rechazado
├── admin/
│   ├── index.php             # Comandas del barista + CRUD carta + historial MP
│   ├── login.php / logout.php
├── index.php                 # Kiosco Virtual (catálogo + modal + carrito)
├── env.php                   # Carga de .env y constantes de configuración
├── .env.example              # Plantilla de variables de entorno
├── composer.json             # vlucas/phpdotenv
└── .gitignore                # .env, vendor/, logs
```

---

## 🚀 Instalación en XAMPP

1. **Ubicar el proyecto** dentro de `htdocs`, por ejemplo: `C:\xampp\htdocs\ariza`.
2. **Iniciar Apache y MySQL** desde el panel de XAMPP.
3. **Importar la base**: abrir phpMyAdmin (`http://localhost/phpmyadmin`) e importar `db/schema.sql`.
   Crea la base `kiosco_online`, las tablas con las columnas nuevas y carga la carta de la cafetería
   (si ya tenías la base del repo original, el mismo script agrega las columnas que faltan).
4. **Configurar el entorno**: copiar `.env.example` como `.env` y completar `MP_ACCESS_TOKEN`,
   `MP_PUBLIC_KEY` (credenciales de prueba del [panel de desarrolladores de Mercado Pago](https://www.mercadopago.com.ar/developers/panel/credentials))
   y `BASE_URL` (ej: `http://localhost/ariza`).
5. *(Opcional)* `composer install` para cargar el `.env` con `vlucas/phpdotenv`.
   Sin Composer, el sistema usa los valores por defecto de XAMPP definidos en `env.php`.

---

## 💻 Recorrido de Prueba

1. Entrar a `http://localhost/ariza`.
2. **Elegir un café** (ej: Latte de Especialidad) → se abre el **modal de personalización**:
   leche Almendras, azúcar Poco, ☑ Sin TACC, nota "extra caliente", horario de retiro 10:30.
3. **Agregar al pedido** → el carrito muestra el desglose completo de la personalización y el total.
   En paralelo, el estado queda espejado en `$_SESSION['carrito']` (verificable en `api/cart_session.php`).
4. **Pagar con Mercado Pago** → se registra la orden (`ordenes.horario_retiro`) con su detalle
   (`orden_items.notas_personalizacion`) y redirige al Checkout Pro (usar tarjetas de prueba de MP).
5. Volver por `success.php` → muestra la confirmación con el **horario de retiro**.
6. Entrar al panel: `http://localhost/ariza/admin/login.php` (**admin / admin123**) →
   la solapa **"☕ Comandas (Barista)"** muestra el pedido como ticket con la preparación exacta
   y la hora de retiro; se refresca sola cada 10 segundos.
7. **Webhook IPN**: en un dominio público, Mercado Pago notifica a `api/webhook.php`,
   que aprueba la orden y descuenta stock automáticamente.

---

## 👥 Créditos

- Repositorio base de la cátedra: [MaxGus1977/Kiosco-Online-MP](https://github.com/MaxGus1977/Kiosco-Online-MP).
- Adaptación Coffee Shop, personalización de bebidas, Take Away y panel de comandas: **Equipo 8**.
