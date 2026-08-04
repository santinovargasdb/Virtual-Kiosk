-- =======================================================
-- Esquema de Base de Datos para Kiosco Online con Mercado Pago
-- Base de datos: kiosco_online
-- =======================================================

CREATE DATABASE IF NOT EXISTS `kiosco_online` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `kiosco_online`;

-- --------------------------------------------------------
-- Tabla: usuarios (Para el módulo de administración)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `nombre` VARCHAR(100) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabla: productos
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `productos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(150) NOT NULL,
  `descripcion` TEXT NULL,
  `precio` DECIMAL(10, 2) NOT NULL,
  `categoria` VARCHAR(50) NOT NULL,
  `imagen_url` VARCHAR(500) NULL,
  `stock` INT NOT NULL DEFAULT 0,
  `destacado` TINYINT(1) DEFAULT 0,
  `fecha_creacion` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabla: ordenes
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ordenes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `external_reference` VARCHAR(64) NOT NULL UNIQUE,
  `monto_total` DECIMAL(10, 2) NOT NULL,
  `estado` ENUM('pending', 'approved', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending',
  `mp_payment_id` VARCHAR(100) NULL,
  `mp_merchant_order_id` VARCHAR(100) NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabla: orden_items
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `orden_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `orden_id` INT NOT NULL,
  `producto_id` INT NOT NULL,
  `cantidad` INT NOT NULL,
  `precio_unitario` DECIMAL(10, 2) NOT NULL,
  FOREIGN KEY (`orden_id`) REFERENCES `ordenes`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`producto_id`) REFERENCES `productos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Datos Iniciales de Usuario Administrador (admin / admin123)
-- Hash de 'admin123': $2y$10$K9W4r.2aA8zK5uH6hQ2S1.oT.2L6/S.q8u8V4W6z9y7eO.e1eK3K6
-- --------------------------------------------------------
INSERT INTO `usuarios` (`username`, `password_hash`, `nombre`) VALUES
('admin', '$2y$10$K9W4r.2aA8zK5uH6hQ2S1.oT.2L6/S.q8u8V4W6z9y7eO.e1eK3K6', 'Administrador Kiosco')
ON DUPLICATE KEY UPDATE `username`=`username`;

-- --------------------------------------------------------
-- Datos Iniciales (Seed Data) de Productos del Kiosco
-- --------------------------------------------------------
INSERT INTO `productos` (`nombre`, `descripcion`, `precio`, `categoria`, `imagen_url`, `stock`, `destacado`) VALUES
('Coca-Cola Sabor Original 500ml', 'Gaseosa refrescante sabor original en botella PET.', 1800.00, 'bebidas', 'https://images.unsplash.com/photo-1622483767028-3f66f32aef97?w=500&q=80', 50, 1),
('Agua Mineral sin Gas 500ml', 'Agua pura de manantial para mantenerte hidratado.', 1200.00, 'bebidas', 'https://images.unsplash.com/photo-1548839140-29a749e1bc4e?w=500&q=80', 80, 0),
('Red Bull Energy Drink 250ml', 'Bebida energizante premium para revitalizar cuerpo y mente.', 2900.00, 'bebidas', 'https://images.unsplash.com/photo-1527960471264-932f39eb5846?w=500&q=80', 35, 1),
('Alfajor Triple Chocolate', 'Relleno con abundante dulce de leche y baño de chocolate con leche.', 1400.00, 'golosinas', 'https://images.unsplash.com/photo-1558961363-fa8fdf82db35?w=500&q=80', 40, 1),
('Barra de Chocolate Suizo 100g', 'Chocolate con leche artesanal con avellanas tostadas.', 2600.00, 'golosinas', 'https://images.unsplash.com/photo-1582176647444-a95781a79fef?w=500&q=80', 25, 0),
('Caramelos Masticables Frutales (Bolsa)', 'Surtido de caramelos masticables con sabor a frutilla, naranja y limón.', 950.00, 'golosinas', 'https://images.unsplash.com/photo-1581798459219-318e76aecc7b?w=500&q=80', 60, 0),
('Papas Fritas Corte Tradicional 140g', 'Papas saladas crujientes seleccionadas, súper crocantes.', 2200.00, 'snacks', 'https://images.unsplash.com/photo-1566478989037-eec170784d0b?w=500&q=80', 45, 1),
('Nacho Chips con Queso Cheddar 150g', 'Totopos de maíz crujientes acompañados de condimento sabor cheddar.', 2500.00, 'snacks', 'https://images.unsplash.com/photo-1513456852971-30c0b8199d4d?w=500&q=80', 30, 0),
('Maní Salado Tostado 120g', 'Maní pelado, tostado y salado ideal para picadas rápidas.', 1100.00, 'snacks', 'https://images.unsplash.com/photo-1536599018102-9f803c140fc1?w=500&q=80', 70, 0),
('Galletitas Rellenas de Vainilla 160g', 'Crocantes galletitas de chocolate con cremoso relleno de vainilla.', 1350.00, 'galletitas', 'https://images.unsplash.com/photo-1558961363-fa8fdf82db35?w=500&q=80', 40, 0)
ON DUPLICATE KEY UPDATE `nombre`=`nombre`;
