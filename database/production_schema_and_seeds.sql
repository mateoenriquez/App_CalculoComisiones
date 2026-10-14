-- ==============================================================================
-- Script SQL de Despliegue para Producción — Sistema de Comisiones de Ventas
-- Compatible con MySQL 8.0+ y MariaDB 10.5+ (Alwaysdata)
-- Charset: utf8mb4 | Collation: utf8mb4_unicode_ci
-- ==============================================================================

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- ------------------------------------------------------------------------------
-- 1. Tabla: vendedores
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `vendedores` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(120) NOT NULL COMMENT 'Nombre completo del vendedor',
  `created` DATETIME NULL DEFAULT NULL,
  `modified` DATETIME NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabla de vendedores';

-- ------------------------------------------------------------------------------
-- 2. Tabla: reglas
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `reglas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `meta_minima` DECIMAL(12,2) NOT NULL COMMENT 'Umbral minimo de ventas para aplicar esta regla',
  `porcentaje_comision` DECIMAL(5,2) NOT NULL COMMENT 'Porcentaje de comision a aplicar (0-100)',
  `created` DATETIME NULL DEFAULT NULL,
  `modified` DATETIME NULL DEFAULT NULL,
  UNIQUE KEY `uq_reglas_meta_minima` (`meta_minima`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Reglas de comision por meta minima';

-- ------------------------------------------------------------------------------
-- 3. Tabla: ventas
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ventas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `vendedor_id` INT NOT NULL COMMENT 'FK a vendedores.id',
  `monto` DECIMAL(12,2) NOT NULL COMMENT 'Monto de la venta en USD',
  `fecha` DATE NOT NULL COMMENT 'Fecha de la venta',
  `created` DATETIME NULL DEFAULT NULL,
  `modified` DATETIME NULL DEFAULT NULL,
  KEY `idx_ventas_vendedor_id` (`vendedor_id`),
  CONSTRAINT `fk_ventas_vendedor_id` FOREIGN KEY (`vendedor_id`) REFERENCES `vendedores` (`id`) ON DELETE RESTRICT ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Ventas realizadas por cada vendedor';

-- ------------------------------------------------------------------------------
-- 4. Tablas de control de migraciones y seeders de CakePHP
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `phinxlog` (
  `version` BIGINT NOT NULL PRIMARY KEY,
  `migration_name` VARCHAR(100) NULL DEFAULT NULL,
  `start_time` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `end_time` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `breakpoint` TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cake_seeds` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `plugin` VARCHAR(100) NULL DEFAULT NULL,
  `seed_name` VARCHAR(100) NOT NULL,
  `executed_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 5. Inserción de datos de demostración (idempotente)
-- ------------------------------------------------------------------------------

-- Vendedores
INSERT INTO `vendedores` (`id`, `nombre`, `created`, `modified`) VALUES
(1, 'Ana López', NOW(), NOW()),
(2, 'Bruno Mendoza', NOW(), NOW()),
(3, 'Carla Torres', NOW(), NOW())
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);

-- Reglas de comisión
INSERT INTO `reglas` (`id`, `meta_minima`, `porcentaje_comision`, `created`, `modified`) VALUES
(1, 0.00, 0.00, NOW(), NOW()),
(2, 1000.00, 5.00, NOW(), NOW()),
(3, 2000.00, 10.00, NOW(), NOW())
ON DUPLICATE KEY UPDATE `porcentaje_comision` = VALUES(`porcentaje_comision`);

-- Ventas demo (Enero 2026)
-- Ana López: 100 + 150 + 200 + 350 = 800.00 (0% comisión -> 0.00)
-- Bruno Mendoza: 250 + 350 + 400 + 500 = 1500.00 (5% comisión -> 75.00)
-- Carla Torres: 400 + 600 + 700 + 800 = 2500.00 (10% comisión -> 250.00)
-- Total ventas: 4800.00 | Total comisiones: 325.00
INSERT INTO `ventas` (`id`, `vendedor_id`, `monto`, `fecha`, `created`, `modified`) VALUES
(1, 1, 100.00, '2026-01-05', NOW(), NOW()),
(2, 1, 150.00, '2026-01-12', NOW(), NOW()),
(3, 1, 200.00, '2026-01-19', NOW(), NOW()),
(4, 1, 350.00, '2026-01-26', NOW(), NOW()),
(5, 2, 250.00, '2026-01-06', NOW(), NOW()),
(6, 2, 350.00, '2026-01-13', NOW(), NOW()),
(7, 2, 400.00, '2026-01-20', NOW(), NOW()),
(8, 2, 500.00, '2026-01-27', NOW(), NOW()),
(9, 3, 400.00, '2026-01-07', NOW(), NOW()),
(10, 3, 600.00, '2026-01-14', NOW(), NOW()),
(11, 3, 700.00, '2026-01-21', NOW(), NOW()),
(12, 3, 800.00, '2026-01-28', NOW(), NOW())
ON DUPLICATE KEY UPDATE `monto` = VALUES(`monto`);

-- Registrar migraciones para sincronización con CakePHP Migrations CLI
INSERT IGNORE INTO `phinxlog` (`version`, `migration_name`) VALUES
(20260101000001, 'CreateVendedores'),
(20260101000002, 'CreateReglas'),
(20260101000003, 'CreateVentas');

-- Registrar seeders para evitar reejecuciones no deseadas
INSERT INTO `cake_seeds` (`plugin`, `seed_name`) VALUES
(NULL, 'VendedoresSeeder'),
(NULL, 'ReglasSeeder'),
(NULL, 'VentasSeeder');

SET foreign_key_checks = 1;
