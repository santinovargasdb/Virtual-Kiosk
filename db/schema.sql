-- =======================================================
-- Esquema de Base de Datos - Kiosco Virtual / Coffee Shop
-- Equipo 8: Cafetería y Bar de Especialidad (Take Away)
-- Basado en el esquema original de Kiosco-Online-MP
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
-- `personalizable` = 1 para bebidas que admiten elección de
-- leche y nivel de azúcar (cafés, filtrados, lattes, etc.)
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
  `personalizable` TINYINT(1) NOT NULL DEFAULT 0,
  `fecha_creacion` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabla: ordenes
-- `horario_retiro` = hora elegida por el cliente para pasar
-- a buscar su pedido por el local (modalidad Take Away)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ordenes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `external_reference` VARCHAR(64) NOT NULL UNIQUE,
  `monto_total` DECIMAL(10, 2) NOT NULL,
  `horario_retiro` VARCHAR(10) NULL,
  `estado` ENUM('pending', 'approved', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending',
  `mp_payment_id` VARCHAR(100) NULL,
  `mp_merchant_order_id` VARCHAR(100) NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabla: orden_items
-- `notas_personalizacion` = detalle completo de preparación
-- del café ("Leche: Almendras · Azúcar: Poco · SIN TACC ...")
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `orden_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `orden_id` INT NOT NULL,
  `producto_id` INT NOT NULL,
  `cantidad` INT NOT NULL,
  `precio_unitario` DECIMAL(10, 2) NOT NULL,
  `notas_personalizacion` TEXT NULL,
  FOREIGN KEY (`orden_id`) REFERENCES `ordenes`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`producto_id`) REFERENCES `productos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- MIGRACIÓN para bases ya creadas con el esquema original.
-- (XAMPP usa MariaDB, que soporta ADD COLUMN IF NOT EXISTS.
-- Si la base es nueva, estas sentencias no hacen nada.)
-- --------------------------------------------------------
ALTER TABLE `productos`   ADD COLUMN IF NOT EXISTS `personalizable` TINYINT(1) NOT NULL DEFAULT 0 AFTER `destacado`;
ALTER TABLE `ordenes`     ADD COLUMN IF NOT EXISTS `horario_retiro` VARCHAR(10) NULL AFTER `monto_total`;
ALTER TABLE `orden_items` ADD COLUMN IF NOT EXISTS `notas_personalizacion` TEXT NULL AFTER `precio_unitario`;

-- --------------------------------------------------------
-- Datos Iniciales de Usuario Administrador (admin / admin123)
-- Hash de 'admin123': $2y$10$K9W4r.2aA8zK5uH6hQ2S1.oT.2L6/S.q8u8V4W6z9y7eO.e1eK3K6
-- --------------------------------------------------------
INSERT INTO `usuarios` (`username`, `password_hash`, `nombre`) VALUES
('admin', '$2y$10$K9W4r.2aA8zK5uH6hQ2S1.oT.2L6/S.q8u8V4W6z9y7eO.e1eK3K6', 'Barista Administrador')
ON DUPLICATE KEY UPDATE `username`=`username`;

-- --------------------------------------------------------
-- Datos Iniciales (Seed Data) - Carta de la Cafetería
-- Categorías: cafes | filtrados | bebidas | pasteleria
-- --------------------------------------------------------
INSERT INTO `productos` (`nombre`, `descripcion`, `precio`, `categoria`, `imagen_url`, `stock`, `destacado`, `personalizable`) VALUES
('Espresso', 'Shot de 30 ml de blend de especialidad tostado medio. Intenso, con crema densa y final a cacao.', 2500.00, 'cafes', 'https://images.unsplash.com/photo-1510707577719-ae7c14805e3a?w=500&q=80', 100, 0, 1),
('Espresso Doble', 'Doble shot de 60 ml para los que necesitan el empujón completo. Notas a chocolate amargo y nuez.', 3200.00, 'cafes', 'https://images.unsplash.com/photo-1514432324607-a09d9b4aefdd?w=500&q=80', 100, 0, 1),
('Latte de Especialidad', 'Espresso con leche texturizada a 60°C y arte latte. Suave, cremoso y con dulzor natural.', 4500.00, 'cafes', 'https://images.unsplash.com/photo-1541167760496-1628856ab772?w=500&q=80', 80, 1, 1),
('Capuchino Italiano', 'Tercios perfectos de espresso, leche vaporizada y espuma sedosa, terminado con cacao amargo.', 4200.00, 'cafes', 'https://images.unsplash.com/photo-1572442388796-11668a67e53d?w=500&q=80', 80, 1, 1),
('Flat White', 'Doble ristretto con leche micro-espumada. La opción de los que quieren café con cuerpo y poca leche.', 4800.00, 'cafes', 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?w=500&q=80', 80, 1, 1),
('Mocha con Cacao Belga', 'Espresso, leche cremosa y ganache de cacao belga 70%. El punto justo entre café y postre.', 5200.00, 'cafes', 'https://images.unsplash.com/photo-1578314675249-a6910f80cc4e?w=500&q=80', 60, 0, 1),
('V60 de Origen - Finca La Esperanza', 'Filtrado manual en V60 de un microlote colombiano. Taza limpia con notas a durazno y panela.', 5500.00, 'filtrados', 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=500&q=80', 40, 1, 1),
('Cold Brew 12hs', 'Extracción en frío durante 12 horas. Refrescante, dulce y de baja acidez, servido con hielo.', 4300.00, 'filtrados', 'https://images.unsplash.com/photo-1461023058943-07fcbe16d735?w=500&q=80', 50, 0, 1),
('Chai Latte Especiado', 'Blend de té negro, jengibre, cardamomo y canela con leche espumada. Confort en taza.', 4600.00, 'bebidas', 'https://images.unsplash.com/photo-1544787219-7f47ccb76574?w=500&q=80', 60, 0, 1),
('Matcha Latte', 'Té verde matcha ceremonial batido con leche cremosa. Energía sostenida sin nervios.', 4900.00, 'bebidas', 'https://images.unsplash.com/photo-1536256263959-770b48d82b0a?w=500&q=80', 50, 0, 1),
('Limonada Menta & Jengibre', 'Limonada de la casa exprimida al momento con menta fresca y toque de jengibre.', 3500.00, 'bebidas', 'https://images.unsplash.com/photo-1523677011781-c91d1bbe2f9e?w=500&q=80', 70, 0, 0),
('Croissant de Manteca', 'Laminado 48hs con manteca francesa. Crocante por fuera, hojaldrado y aireado por dentro.', 3000.00, 'pasteleria', 'https://images.unsplash.com/photo-1555507036-ab1f4038808a?w=500&q=80', 45, 1, 0),
('Medialunas de Manteca x3', 'Trío de medialunas glaseadas de elaboración propia, tibias si las retirás a la mañana.', 3400.00, 'pasteleria', 'https://images.unsplash.com/photo-1509440159596-0249088772ff?w=500&q=80', 60, 0, 0),
('Budín de Limón y Amapolas', 'Budín húmedo de limón con semillas de amapola y glaseado cítrico.', 3800.00, 'pasteleria', 'https://images.unsplash.com/photo-1565958011703-44f9829ba187?w=500&q=80', 35, 0, 0),
('Cheesecake Frutos Rojos (Sin TACC)', 'Cheesecake cremoso sobre base de almendras, con coulis de frutos rojos. Apto celíacos.', 5500.00, 'pasteleria', 'https://images.unsplash.com/photo-1533134242443-d4fd215305ad?w=500&q=80', 30, 1, 0),
('Alfajor de Pistacho', 'Alfajor artesanal relleno de crema de pistacho y baño de chocolate blanco.', 4000.00, 'pasteleria', 'https://images.unsplash.com/photo-1558961363-fa8fdf82db35?w=500&q=80', 40, 0, 0)
ON DUPLICATE KEY UPDATE `nombre`=`nombre`;
