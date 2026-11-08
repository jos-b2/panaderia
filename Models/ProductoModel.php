<?php
/**
 * Modelo de Productos y Recetas - Panadería Don Leo
 * Ubicación: /panaderia/Models/ProductoModel.php
 */

class ProductoModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function alternarActivo($id) {
        $stmt = $this->pdo->prepare("UPDATE productos SET activo = NOT activo WHERE id = ?");
        $stmt->execute([$id]);
    }

    /** Crea el producto con su receta. Devuelve el id nuevo. */
    public function crear($nombre, $precio, $base_insumo_id, $rendimiento, $minutos_horneado, $minutos_volteo, $ing_ids, $ing_cants) {
        try {
            $this->pdo->beginTransaction();

            $stmtProd = $this->pdo->prepare("INSERT INTO productos (nombre, precio, base_insumo_id, rendimiento, minutos_horneado, minutos_volteo) VALUES (?, ?, ?, ?, ?, ?)");
            $stmtProd->execute([$nombre, $precio, $base_insumo_id, $rendimiento, $minutos_horneado, $minutos_volteo]);
            $producto_id = (int)$this->pdo->lastInsertId();

            $this->guardarItems($producto_id, $base_insumo_id, $ing_ids, $ing_cants);

            $this->pdo->commit();
            return $producto_id;
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    /** Actualiza el producto y reemplaza todos los ingredientes de su receta. */
    public function actualizar($producto_id, $nombre, $precio, $base_insumo_id, $rendimiento, $minutos_horneado, $minutos_volteo, $ing_ids, $ing_cants) {
        try {
            $this->pdo->beginTransaction();

            $stmtProd = $this->pdo->prepare("UPDATE productos SET nombre = ?, precio = ?, base_insumo_id = ?, rendimiento = ?, minutos_horneado = ?, minutos_volteo = ? WHERE id = ?");
            $stmtProd->execute([$nombre, $precio, $base_insumo_id, $rendimiento, $minutos_horneado, $minutos_volteo, $producto_id]);
            $this->pdo->prepare("DELETE FROM receta_items WHERE producto_id = ?")->execute([$producto_id]);

            if (!empty($producto_id)) {
                $this->guardarItems($producto_id, $base_insumo_id, $ing_ids, $ing_cants);
            }

            $this->pdo->commit();
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    private function guardarItems($producto_id, $base_insumo_id, $ing_ids, $ing_cants) {
        $stmtItem = $this->pdo->prepare("INSERT INTO receta_items (producto_id, insumo_id, cantidad) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE cantidad = VALUES(cantidad)");
        foreach ($ing_ids as $idx => $iid) {
            $iid = (int)$iid;
            $cant = (float)($ing_cants[$idx] ?? 0);
            if ($iid > 0 && $iid !== $base_insumo_id && $cant > 0) {
                $stmtItem->execute([$producto_id, $iid, $cant]);
            }
        }
    }

    public function obtener($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM productos WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function itemsDeProducto($id) {
        $stmt = $this->pdo->prepare("SELECT insumo_id, cantidad FROM receta_items WHERE producto_id = ?");
        $stmt->execute([$id]);
        return $stmt->fetchAll();
    }

    /** Productos con los datos de su materia base. */
    public function listarConBase($incluirInactivos = false) {
        $sql = "
            SELECT p.*, i.nombre AS base_nombre, i.unidad AS base_unidad, i.costo AS base_costo
            FROM productos p
            JOIN insumos i ON p.base_insumo_id = i.id
            " . ($incluirInactivos ? "" : "WHERE p.activo = 1 ") . "
            ORDER BY p.activo DESC, p.nombre ASC
        ";
        return $this->pdo->query($sql)->fetchAll();
    }

    /** Ingredientes de todas las recetas con su costo. */
    public function itemsDeTodasLasRecetas() {
        return $this->pdo->query("
            SELECT ri.producto_id, ri.cantidad, i.nombre, i.unidad, i.costo
            FROM receta_items ri
            JOIN insumos i ON ri.insumo_id = i.id
        ")->fetchAll();
    }

    /** Productos activos con su precio (para el arqueo). */
    public function listarActivos() {
        return $this->pdo->query("SELECT id, nombre, precio FROM productos WHERE activo = 1 ORDER BY nombre ASC")->fetchAll();
    }
}
