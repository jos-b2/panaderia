-- ==========================================================================
--  Migración: momento de entrega de los pedidos (entregar = venta registrada)
--  Panadería Don Leo · 2026-11-08
--  Ejecutar una sola vez sobre panaderia_don_leo, después de migracion_2026_11_hornear.sql
-- ==========================================================================
USE panaderia_don_leo;

ALTER TABLE pedidos ADD COLUMN entregado_en DATETIME NULL AFTER estado;

-- Pedidos ya entregados: se toma la fecha de entrega programada
UPDATE pedidos SET entregado_en = fecha_entrega WHERE estado = 'entregado';

CREATE INDEX idx_ped_entregado ON pedidos(entregado_en);
