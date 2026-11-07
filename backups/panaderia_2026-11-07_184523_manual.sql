-- Respaldo Panadería Don Leo
-- Fecha: 2026-11-07 18:45:23

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `abonos`;
CREATE TABLE `abonos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pedido_id` int NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `fecha` date NOT NULL DEFAULT (curdate()),
  `nota` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `fk_abono_pedido` (`pedido_id`),
  CONSTRAINT `fk_abono_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `abonos` (`id`, `pedido_id`, `monto`, `fecha`, `nota`) VALUES ('1', '1', '1.25', '2026-11-07', 'Abono registrado');
INSERT INTO `abonos` (`id`, `pedido_id`, `monto`, `fecha`, `nota`) VALUES ('2', '2', '1.25', '2026-11-07', 'Abono inicial');
INSERT INTO `abonos` (`id`, `pedido_id`, `monto`, `fecha`, `nota`) VALUES ('3', '3', '3.75', '2026-11-07', 'Abono registrado');

DROP TABLE IF EXISTS `arqueo_caja`;
CREATE TABLE `arqueo_caja` (
  `fecha` date NOT NULL,
  `caja_real` decimal(10,2) DEFAULT NULL,
  `nota` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `arqueo_caja` (`fecha`, `caja_real`, `nota`) VALUES ('2026-11-07', '10.00', NULL);

DROP TABLE IF EXISTS `arqueo_detalle`;
CREATE TABLE `arqueo_detalle` (
  `fecha` date NOT NULL,
  `producto_id` int NOT NULL,
  `vendidas` int NOT NULL DEFAULT '0',
  `sobrante` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`fecha`,`producto_id`),
  KEY `fk_arqueo_producto` (`producto_id`),
  CONSTRAINT `fk_arqueo_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `arqueo_detalle` (`fecha`, `producto_id`, `vendidas`, `sobrante`) VALUES ('2026-11-07', '1', '0', '0');
INSERT INTO `arqueo_detalle` (`fecha`, `producto_id`, `vendidas`, `sobrante`) VALUES ('2026-11-07', '2', '0', '0');

DROP TABLE IF EXISTS `clientes`;
CREATE TABLE `clientes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `clientes` (`id`, `nombre`, `telefono`) VALUES ('1', 'Emerson Manuel', '7015-4312');
INSERT INTO `clientes` (`id`, `nombre`, `telefono`) VALUES ('2', 'Centeno', '7015-4312');

DROP TABLE IF EXISTS `insumos`;
CREATE TABLE `insumos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `unidad` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stock` decimal(10,2) NOT NULL DEFAULT '0.00',
  `stock_minimo` decimal(10,2) NOT NULL DEFAULT '0.00',
  `costo` decimal(10,2) NOT NULL DEFAULT '0.00',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `insumos` (`id`, `nombre`, `unidad`, `stock`, `stock_minimo`, `costo`, `activo`) VALUES ('1', 'Harina de trigo', 'lb', '99.00', '25.00', '0.45', '1');
INSERT INTO `insumos` (`id`, `nombre`, `unidad`, `stock`, `stock_minimo`, `costo`, `activo`) VALUES ('2', 'Manteca vegetal', 'lb', '19.94', '5.00', '1.10', '1');
INSERT INTO `insumos` (`id`, `nombre`, `unidad`, `stock`, `stock_minimo`, `costo`, `activo`) VALUES ('3', 'Levadura', 'lb', '2.98', '1.00', '3.50', '1');
INSERT INTO `insumos` (`id`, `nombre`, `unidad`, `stock`, `stock_minimo`, `costo`, `activo`) VALUES ('4', 'Azúcar', 'lb', '29.96', '10.00', '0.55', '1');
INSERT INTO `insumos` (`id`, `nombre`, `unidad`, `stock`, `stock_minimo`, `costo`, `activo`) VALUES ('5', 'Sal', 'lb', '9.97', '2.00', '0.20', '1');
INSERT INTO `insumos` (`id`, `nombre`, `unidad`, `stock`, `stock_minimo`, `costo`, `activo`) VALUES ('6', 'Huevos', 'unidad', '60.00', '30.00', '0.20', '1');

DROP TABLE IF EXISTS `movimientos`;
CREATE TABLE `movimientos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `insumo_id` int NOT NULL,
  `tipo` enum('entrada','salida','ajuste') COLLATE utf8mb4_unicode_ci NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `stock_resultante` decimal(10,2) NOT NULL,
  `nota` text COLLATE utf8mb4_unicode_ci,
  `usuario_id` int DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_mov_usuario` (`usuario_id`),
  KEY `idx_mov_insumo` (`insumo_id`),
  CONSTRAINT `fk_mov_insumo` FOREIGN KEY (`insumo_id`) REFERENCES `insumos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mov_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `movimientos` (`id`, `insumo_id`, `tipo`, `cantidad`, `stock_resultante`, `nota`, `usuario_id`, `fecha`) VALUES ('1', '1', 'salida', '1.00', '99.00', 'Producción #1 (Pan francés)', NULL, '2026-11-07 11:06:37');
INSERT INTO `movimientos` (`id`, `insumo_id`, `tipo`, `cantidad`, `stock_resultante`, `nota`, `usuario_id`, `fecha`) VALUES ('2', '2', 'salida', '0.06', '19.94', 'Producción #1 (Pan francés)', NULL, '2026-11-07 11:06:37');
INSERT INTO `movimientos` (`id`, `insumo_id`, `tipo`, `cantidad`, `stock_resultante`, `nota`, `usuario_id`, `fecha`) VALUES ('3', '3', 'salida', '0.02', '2.98', 'Producción #1 (Pan francés)', NULL, '2026-11-07 11:06:37');
INSERT INTO `movimientos` (`id`, `insumo_id`, `tipo`, `cantidad`, `stock_resultante`, `nota`, `usuario_id`, `fecha`) VALUES ('4', '4', 'salida', '0.04', '29.96', 'Producción #1 (Pan francés)', NULL, '2026-11-07 11:06:37');
INSERT INTO `movimientos` (`id`, `insumo_id`, `tipo`, `cantidad`, `stock_resultante`, `nota`, `usuario_id`, `fecha`) VALUES ('5', '5', 'salida', '0.03', '9.97', 'Producción #1 (Pan francés)', NULL, '2026-11-07 11:06:37');

DROP TABLE IF EXISTS `pedidos`;
CREATE TABLE `pedidos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cliente_id` int NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_entrega` datetime NOT NULL,
  `total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `estado` enum('pendiente','en_produccion','listo','entregado','cancelado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `nota` text COLLATE utf8mb4_unicode_ci,
  `usuario_id` int DEFAULT NULL,
  `creado` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_pedido_cliente` (`cliente_id`),
  KEY `fk_pedido_usuario` (`usuario_id`),
  KEY `idx_ped_entrega` (`fecha_entrega`),
  CONSTRAINT `fk_pedido_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`),
  CONSTRAINT `fk_pedido_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `pedidos` (`id`, `cliente_id`, `descripcion`, `fecha_entrega`, `total`, `estado`, `nota`, `usuario_id`, `creado`) VALUES ('1', '1', '20 panes franceses', '2026-11-07 10:25:00', '1.25', 'entregado', NULL, NULL, '2026-11-07 10:27:03');
INSERT INTO `pedidos` (`id`, `cliente_id`, `descripcion`, `fecha_entrega`, `total`, `estado`, `nota`, `usuario_id`, `creado`) VALUES ('2', '1', '20 panes franceses', '2026-11-07 11:27:00', '1.25', 'entregado', NULL, NULL, '2026-11-07 11:27:55');
INSERT INTO `pedidos` (`id`, `cliente_id`, `descripcion`, `fecha_entrega`, `total`, `estado`, `nota`, `usuario_id`, `creado`) VALUES ('3', '2', '20 panes franceses', '2026-11-07 12:06:00', '3.75', 'entregado', NULL, NULL, '2026-11-07 12:07:30');

DROP TABLE IF EXISTS `perfil`;
CREATE TABLE `perfil` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int NOT NULL,
  `nombre_personalizado` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `foto_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `banner_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_actualizacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `perfil` (`id`, `usuario_id`, `nombre_personalizado`, `foto_url`, `banner_url`, `fecha_actualizacion`) VALUES ('1', '1', 'Administrador', '/panaderia/Assets/img/perfil/1.jpg', '/panaderia/Assets/img/banner/1.jpg', '2026-11-05 14:12:05');

DROP TABLE IF EXISTS `produccion_consumo`;
CREATE TABLE `produccion_consumo` (
  `produccion_id` int NOT NULL,
  `insumo_id` int NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  KEY `fk_pc_produccion` (`produccion_id`),
  KEY `fk_pc_insumo` (`insumo_id`),
  CONSTRAINT `fk_pc_insumo` FOREIGN KEY (`insumo_id`) REFERENCES `insumos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pc_produccion` FOREIGN KEY (`produccion_id`) REFERENCES `producciones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `produccion_consumo` (`produccion_id`, `insumo_id`, `cantidad`) VALUES ('1', '1', '1.00');
INSERT INTO `produccion_consumo` (`produccion_id`, `insumo_id`, `cantidad`) VALUES ('1', '2', '0.06');
INSERT INTO `produccion_consumo` (`produccion_id`, `insumo_id`, `cantidad`) VALUES ('1', '3', '0.02');
INSERT INTO `produccion_consumo` (`produccion_id`, `insumo_id`, `cantidad`) VALUES ('1', '4', '0.04');
INSERT INTO `produccion_consumo` (`produccion_id`, `insumo_id`, `cantidad`) VALUES ('1', '5', '0.03');

DROP TABLE IF EXISTS `producciones`;
CREATE TABLE `producciones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `fecha` date NOT NULL,
  `producto_id` int NOT NULL,
  `base_cantidad` decimal(10,2) NOT NULL,
  `piezas` int NOT NULL,
  `nota` text COLLATE utf8mb4_unicode_ci,
  `usuario_id` int DEFAULT NULL,
  `anulada` tinyint(1) NOT NULL DEFAULT '0',
  `creado` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_prod_producto` (`producto_id`),
  KEY `fk_prod_usuario` (`usuario_id`),
  KEY `idx_prod_fecha` (`fecha`),
  CONSTRAINT `fk_prod_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`),
  CONSTRAINT `fk_prod_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `producciones` (`id`, `fecha`, `producto_id`, `base_cantidad`, `piezas`, `nota`, `usuario_id`, `anulada`, `creado`) VALUES ('1', '2026-11-08', '1', '1.00', '14', 'tanda de mañana', NULL, '0', '2026-11-07 11:06:37');

DROP TABLE IF EXISTS `productos`;
CREATE TABLE `productos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `precio` decimal(10,2) NOT NULL DEFAULT '0.00',
  `base_insumo_id` int NOT NULL,
  `rendimiento` decimal(10,2) NOT NULL DEFAULT '1.00',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`),
  KEY `fk_prod_insumo` (`base_insumo_id`),
  CONSTRAINT `fk_prod_insumo` FOREIGN KEY (`base_insumo_id`) REFERENCES `insumos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `productos` (`id`, `nombre`, `precio`, `base_insumo_id`, `rendimiento`, `activo`) VALUES ('1', 'Pan francés', '0.12', '1', '14.00', '1');
INSERT INTO `productos` (`id`, `nombre`, `precio`, `base_insumo_id`, `rendimiento`, `activo`) VALUES ('2', 'Pan dulce', '0.25', '1', '8.00', '1');

DROP TABLE IF EXISTS `receta_items`;
CREATE TABLE `receta_items` (
  `producto_id` int NOT NULL,
  `insumo_id` int NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  PRIMARY KEY (`producto_id`,`insumo_id`),
  KEY `fk_receta_insumo` (`insumo_id`),
  CONSTRAINT `fk_receta_insumo` FOREIGN KEY (`insumo_id`) REFERENCES `insumos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_receta_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `receta_items` (`producto_id`, `insumo_id`, `cantidad`) VALUES ('1', '2', '0.06');
INSERT INTO `receta_items` (`producto_id`, `insumo_id`, `cantidad`) VALUES ('1', '3', '0.02');
INSERT INTO `receta_items` (`producto_id`, `insumo_id`, `cantidad`) VALUES ('1', '4', '0.04');
INSERT INTO `receta_items` (`producto_id`, `insumo_id`, `cantidad`) VALUES ('1', '5', '0.03');
INSERT INTO `receta_items` (`producto_id`, `insumo_id`, `cantidad`) VALUES ('2', '2', '0.25');
INSERT INTO `receta_items` (`producto_id`, `insumo_id`, `cantidad`) VALUES ('2', '3', '0.03');
INSERT INTO `receta_items` (`producto_id`, `insumo_id`, `cantidad`) VALUES ('2', '4', '0.30');
INSERT INTO `receta_items` (`producto_id`, `insumo_id`, `cantidad`) VALUES ('2', '5', '0.02');
INSERT INTO `receta_items` (`producto_id`, `insumo_id`, `cantidad`) VALUES ('2', '6', '2.00');

DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `usuario` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `clave` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rol` enum('admin','empleado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'empleado',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `debe_cambiar` tinyint(1) NOT NULL DEFAULT '0',
  `creado` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario` (`usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `usuarios` (`id`, `nombre`, `usuario`, `clave`, `rol`, `activo`, `debe_cambiar`, `creado`) VALUES ('1', 'Administrador', 'admin', '$2y$10$4SPRSvP/vUWLtCdrewpAGO/DLcAF2tE/r9LptFvlseGXwuEVjJvLu', 'admin', '1', '1', '2026-11-05 14:02:44');
INSERT INTO `usuarios` (`id`, `nombre`, `usuario`, `clave`, `rol`, `activo`, `debe_cambiar`, `creado`) VALUES ('2', 'Emerson Garcia', 'Emer', '$2y$10$QdatorW5wxT1KSVbS6cMiuhSD/JTjKRn4JeM3wnMzSaWV32kyrZka', 'empleado', '1', '0', '2026-11-07 10:10:00');

SET FOREIGN_KEY_CHECKS=1;
