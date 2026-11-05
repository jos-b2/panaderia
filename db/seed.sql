
-- ==========================================
-- DATOS INICIALES (SEMBRADO / SEEDERS)
-- ==========================================

-- Usuario Administrador por defecto (Contraseña: admin123)
INSERT INTO usuarios (nombre, usuario, clave, rol, debe_cambiar) 
VALUES ('Administrador', 'admin', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'admin', 1);

-- Insumos de ejemplo
INSERT INTO insumos (nombre, unidad, stock, stock_minimo, costo) VALUES
('Harina de trigo', 'lb', 100.00, 25.00, 0.45),
('Manteca vegetal', 'lb', 20.00, 5.00, 1.10),
('Levadura', 'lb', 3.00, 1.00, 3.50),
('Azúcar', 'lb', 30.00, 10.00, 0.55),
('Sal', 'lb', 10.00, 2.00, 0.20),
('Huevos', 'unidad', 60.00, 30.00, 0.20);

-- Productos de ejemplo
INSERT INTO productos (nombre, precio, base_insumo_id, rendimiento) VALUES
('Pan francés', 0.12, 1, 14.00),
('Pan dulce', 0.25, 1, 8.00);

-- Recetas de ejemplo
INSERT INTO receta_items (producto_id, insumo_id, cantidad) VALUES
(1, 3, 0.02),
(1, 5, 0.03),
(1, 4, 0.04),
(1, 2, 0.06),
(2, 3, 0.03),
(2, 5, 0.02),
(2, 4, 0.30),
(2, 2, 0.25),
(2, 6, 2.00);