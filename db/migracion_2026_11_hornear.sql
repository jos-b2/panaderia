-- ==========================================================================
--  Migración: etapas de producción, hornos, cargas y líneas de pedido
--  Panadería Don Leo · 2026-11-08
--
--  Flujo de una tanda:
--    registrada  -> se anotó, NO ha tocado el inventario (se puede modificar)
--    amasando    -> "Empezar a amasar": se descuentan los insumos
--    horneando   -> al menos una carga entró a un horno
--    lista       -> todas sus piezas salieron del horno (pan disponible)
--
--  Ejecutar una sola vez sobre panaderia_don_leo.
-- ==========================================================================

USE panaderia_don_leo;

-- 1. Tandas: etapa y fechas de cada paso.
--    Las tandas existentes ya descontaron insumos con el flujo anterior: quedan como 'lista'.
ALTER TABLE producciones
    ADD COLUMN estado ENUM('registrada', 'amasando', 'horneando', 'lista') NOT NULL DEFAULT 'registrada' AFTER piezas,
    ADD COLUMN forzar_stock TINYINT(1) NOT NULL DEFAULT 0 AFTER estado,
    ADD COLUMN inicio_amasado DATETIME NULL AFTER forzar_stock,
    ADD COLUMN fin_horneado DATETIME NULL AFTER inicio_amasado;

UPDATE producciones SET estado = 'lista', fin_horneado = creado;

CREATE INDEX idx_prod_estado ON producciones(estado);

-- 2. Recetas: tiempos de horno por producto
ALTER TABLE productos
    ADD COLUMN minutos_horneado INT NOT NULL DEFAULT 20 AFTER rendimiento,
    ADD COLUMN minutos_volteo INT NULL DEFAULT NULL AFTER minutos_horneado;   -- NULL = no se voltea

-- 3. Hornos de la panadería
CREATE TABLE IF NOT EXISTS hornos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(60) NOT NULL UNIQUE,
    capacidad INT NOT NULL,                 -- piezas que caben a la vez
    activo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO hornos (nombre, capacidad) VALUES ('Horno 1', 60), ('Horno 2', 40);

-- 4. Cargas: piezas de una tanda que entran a un horno (una tanda puede ir en varias cargas)
CREATE TABLE IF NOT EXISTS horno_cargas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produccion_id INT NOT NULL,
    horno_id INT NOT NULL,
    piezas INT NOT NULL,
    inicio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    volteada TINYINT(1) NOT NULL DEFAULT 0,
    fin DATETIME NULL,                      -- NULL = sigue dentro del horno
    usuario_id INT NULL,
    CONSTRAINT fk_carga_produccion FOREIGN KEY (produccion_id) REFERENCES producciones(id) ON DELETE CASCADE,
    CONSTRAINT fk_carga_horno FOREIGN KEY (horno_id) REFERENCES hornos(id),
    CONSTRAINT fk_carga_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_carga_activa (horno_id, fin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Pedidos: productos y cantidades de cada encargo
CREATE TABLE IF NOT EXISTS pedido_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad INT NOT NULL,
    CONSTRAINT fk_item_pedido FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    CONSTRAINT fk_item_producto FOREIGN KEY (producto_id) REFERENCES productos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
