<?php
/**
 * Modelo de Producción (Tandas) - Panadería Don Leo
 * Ubicación: /panaderia/Models/ProduccionModel.php
 */

class ProduccionModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Registra una tanda: calcula el consumo según la receta, valida stock (salvo que se fuerce),
     * descuenta el inventario y deja un movimiento de salida por cada insumo.
     * Lanza Exception con un mensaje legible si algo falla.
     */
    public function registrar($fecha, $producto_id, $base_cantidad, $piezas, $nota, $forzar_stock, $usuario_id) {
        try {
            $this->pdo->beginTransaction();

            $stmtProd = $this->pdo->prepare("SELECT id, nombre, base_insumo_id FROM productos WHERE id = ? AND activo = 1");
            $stmtProd->execute([$producto_id]);
            $prod = $stmtProd->fetch();

            if (!$prod) {
                throw new Exception("Producto no encontrado.");
            }

            $consumos = [];
            $consumos[$prod['base_insumo_id']] = $base_cantidad;

            $stmtRec = $this->pdo->prepare("SELECT insumo_id, cantidad FROM receta_items WHERE producto_id = ?");
            $stmtRec->execute([$producto_id]);
            foreach ($stmtRec->fetchAll() as $r) {
                $iid = (int)$r['insumo_id'];
                $cant = (float)$r['cantidad'] * $base_cantidad;
                $consumos[$iid] = ($consumos[$iid] ?? 0) + $cant;
            }

            if (!$forzar_stock) {
                foreach ($consumos as $iid => $cant_necesaria) {
                    $stmtIns = $this->pdo->prepare("SELECT nombre, stock, unidad FROM insumos WHERE id = ?");
                    $stmtIns->execute([$iid]);
                    $ins = $stmtIns->fetch();
                    if ($ins && (float)$ins['stock'] < $cant_necesaria) {
                        throw new Exception("Stock insuficiente de {$ins['nombre']} (hay {$ins['stock']} {$ins['unidad']}, se necesitan " . number_format($cant_necesaria, 2) . ").");
                    }
                }
            }

            $stmtInsert = $this->pdo->prepare("INSERT INTO producciones (fecha, producto_id, base_cantidad, piezas, nota, usuario_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmtInsert->execute([$fecha, $producto_id, $base_cantidad, $piezas, $nota !== '' ? $nota : null, $usuario_id]);
            $produccion_id = $this->pdo->lastInsertId();

            foreach ($consumos as $iid => $cant_usada) {
                $stmtPC = $this->pdo->prepare("INSERT INTO produccion_consumo (produccion_id, insumo_id, cantidad) VALUES (?, ?, ?)");
                $stmtPC->execute([$produccion_id, $iid, $cant_usada]);

                $stmtStk = $this->pdo->prepare("SELECT stock FROM insumos WHERE id = ? FOR UPDATE");
                $stmtStk->execute([$iid]);
                $stock_actual = (float)$stmtStk->fetchColumn();
                $nuevo_stock = max(0, $stock_actual - $cant_usada);

                $this->pdo->prepare("UPDATE insumos SET stock = ? WHERE id = ?")->execute([$nuevo_stock, $iid]);

                $stmtMov = $this->pdo->prepare("INSERT INTO movimientos (insumo_id, tipo, cantidad, stock_resultante, nota, usuario_id) VALUES (?, 'salida', ?, ?, ?, ?)");
                $stmtMov->execute([$iid, $cant_usada, $nuevo_stock, "Producción #{$produccion_id} ({$prod['nombre']})", $usuario_id]);
            }

            $this->pdo->commit();
            return $produccion_id;
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Anula una tanda y devuelve al inventario lo que consumió.
     */
    public function anular($produccion_id, $usuario_id) {
        try {
            $this->pdo->beginTransaction();

            $stmtCheck = $this->pdo->prepare("SELECT id, anulada FROM producciones WHERE id = ? FOR UPDATE");
            $stmtCheck->execute([$produccion_id]);
            $tanda = $stmtCheck->fetch();

            if ($tanda && !$tanda['anulada']) {
                $this->pdo->prepare("UPDATE producciones SET anulada = 1 WHERE id = ?")->execute([$produccion_id]);

                $stmtCons = $this->pdo->prepare("SELECT insumo_id, cantidad FROM produccion_consumo WHERE produccion_id = ?");
                $stmtCons->execute([$produccion_id]);
                foreach ($stmtCons->fetchAll() as $c) {
                    $stmtStk = $this->pdo->prepare("SELECT stock FROM insumos WHERE id = ? FOR UPDATE");
                    $stmtStk->execute([$c['insumo_id']]);
                    $stock_actual = (float)$stmtStk->fetchColumn();
                    $nuevo_stock = $stock_actual + (float)$c['cantidad'];

                    $this->pdo->prepare("UPDATE insumos SET stock = ? WHERE id = ?")->execute([$nuevo_stock, $c['insumo_id']]);

                    $stmtMov = $this->pdo->prepare("INSERT INTO movimientos (insumo_id, tipo, cantidad, stock_resultante, nota, usuario_id) VALUES (?, 'entrada', ?, ?, ?, ?)");
                    $stmtMov->execute([$c['insumo_id'], $c['cantidad'], $nuevo_stock, "Anulación de tanda #{$produccion_id}", $usuario_id]);
                }
            }

            $this->pdo->commit();
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    /** Productos activos con los datos y el stock de su materia base. */
    public function productosActivosConBase() {
        return $this->pdo->query("
            SELECT p.id, p.nombre, p.base_insumo_id, p.rendimiento,
                   i.nombre AS base_nombre, i.unidad AS base_unidad, i.stock AS base_stock
            FROM productos p
            JOIN insumos i ON p.base_insumo_id = i.id
            WHERE p.activo = 1
            ORDER BY p.nombre ASC
        ")->fetchAll();
    }

    /** Ingredientes de todas las recetas con el stock actual de cada insumo. */
    public function recetasConStock() {
        return $this->pdo->query("
            SELECT ri.producto_id, ri.insumo_id, ri.cantidad,
                   i.nombre, i.unidad, i.stock
            FROM receta_items ri
            JOIN insumos i ON ri.insumo_id = i.id
        ")->fetchAll();
    }

    public function ultimasTandas($limite = 10) {
        $stmt = $this->pdo->prepare("
            SELECT pr.id, pr.fecha, pr.base_cantidad, pr.piezas, pr.nota, pr.anulada,
                   p.nombre AS producto, i.unidad AS base_unidad
            FROM producciones pr
            JOIN productos p ON pr.producto_id = p.id
            JOIN insumos i ON p.base_insumo_id = i.id
            ORDER BY pr.id DESC
            LIMIT :limite
        ");
        $stmt->bindValue(':limite', (int)$limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
