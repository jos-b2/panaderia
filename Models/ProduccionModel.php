<?php
/**
 * Modelo de Producción (Tandas) - Panadería Don Leo
 * Ubicación: /panaderia/Models/ProduccionModel.php
 *
 * Etapas de una tanda:
 *   registrada -> amasando -> horneando -> lista
 * El inventario se descuenta al "Empezar a amasar". Mientras está "registrada"
 * se puede modificar o eliminar sin tocar el inventario.
 * El paso a horneando/lista lo maneja HornoModel (vista Hornear).
 */

class ProduccionModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /* ======================================================================
       Consumo y stock
       ====================================================================== */

    /** Cantidad de cada insumo que consume la receta para $base_cantidad: [insumo_id => cantidad] */
    private function calcularConsumo($producto_id, $base_insumo_id, $base_cantidad) {
        $consumos = [(int)$base_insumo_id => (float)$base_cantidad];

        $stmt = $this->pdo->prepare("SELECT insumo_id, cantidad FROM receta_items WHERE producto_id = ?");
        $stmt->execute([$producto_id]);
        foreach ($stmt->fetchAll() as $r) {
            $iid = (int)$r['insumo_id'];
            $consumos[$iid] = ($consumos[$iid] ?? 0) + (float)$r['cantidad'] * $base_cantidad;
        }
        return $consumos;
    }

    /** Lanza Exception si algún insumo no alcanza. */
    private function validarStock($consumos) {
        $stmt = $this->pdo->prepare("SELECT nombre, stock, unidad FROM insumos WHERE id = ?");
        foreach ($consumos as $iid => $necesario) {
            $stmt->execute([$iid]);
            $ins = $stmt->fetch();
            if ($ins && (float)$ins['stock'] < $necesario) {
                throw new Exception("Stock insuficiente de {$ins['nombre']} (hay {$ins['stock']} {$ins['unidad']}, se necesitan " . number_format($necesario, 2) . ").");
            }
        }
    }

    private function productoActivo($producto_id) {
        $stmt = $this->pdo->prepare("SELECT id, nombre, base_insumo_id FROM productos WHERE id = ? AND activo = 1");
        $stmt->execute([$producto_id]);
        $prod = $stmt->fetch();
        if (!$prod) {
            throw new Exception("Producto no encontrado o inactivo.");
        }
        return $prod;
    }

    /* ======================================================================
       Registrar / modificar / eliminar (sin tocar inventario)
       ====================================================================== */

    /**
     * Anota una tanda como "registrada". No descuenta inventario; solo avisa si hoy
     * no alcanzaría el stock (salvo que se marque "registrar de todos modos").
     */
    public function registrar($fecha, $producto_id, $base_cantidad, $piezas, $nota, $forzar_stock, $usuario_id) {
        $prod = $this->productoActivo($producto_id);
        if (!$forzar_stock) {
            $this->validarStock($this->calcularConsumo($producto_id, $prod['base_insumo_id'], $base_cantidad));
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO producciones (fecha, producto_id, base_cantidad, piezas, estado, forzar_stock, nota, usuario_id)
            VALUES (?, ?, ?, ?, 'registrada', ?, ?, ?)
        ");
        $stmt->execute([$fecha, $producto_id, $base_cantidad, $piezas, $forzar_stock ? 1 : 0, $nota !== '' ? $nota : null, $usuario_id]);
        return (int)$this->pdo->lastInsertId();
    }

    /** Modifica una tanda que aún no se empezó a amasar. */
    public function modificar($id, $fecha, $producto_id, $base_cantidad, $piezas, $nota, $forzar_stock) {
        $tanda = $this->obtener($id);
        if (!$tanda || $tanda['estado'] !== 'registrada') {
            throw new Exception("Solo se pueden modificar tandas que aún no se empezaron a amasar.");
        }

        $prod = $this->productoActivo($producto_id);
        if (!$forzar_stock) {
            $this->validarStock($this->calcularConsumo($producto_id, $prod['base_insumo_id'], $base_cantidad));
        }

        $stmt = $this->pdo->prepare("
            UPDATE producciones
            SET fecha = ?, producto_id = ?, base_cantidad = ?, piezas = ?, forzar_stock = ?, nota = ?
            WHERE id = ? AND estado = 'registrada'
        ");
        $stmt->execute([$fecha, $producto_id, $base_cantidad, $piezas, $forzar_stock ? 1 : 0, $nota !== '' ? $nota : null, $id]);
    }

    /** Elimina una tanda que aún no se empezó a amasar (no hay inventario que devolver). */
    public function eliminar($id) {
        $stmt = $this->pdo->prepare("DELETE FROM producciones WHERE id = ? AND estado = 'registrada'");
        $stmt->execute([$id]);
        if ($stmt->rowCount() === 0) {
            throw new Exception("Solo se pueden eliminar tandas que aún no se empezaron a amasar.");
        }
    }

    /* ======================================================================
       Empezar a amasar: aquí se descuenta el inventario
       ====================================================================== */

    public function empezarAmasado($id, $usuario_id) {
        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare("
                SELECT pr.id, pr.producto_id, pr.base_cantidad, pr.estado, pr.forzar_stock,
                       p.nombre AS producto, p.base_insumo_id
                FROM producciones pr JOIN productos p ON p.id = pr.producto_id
                WHERE pr.id = ? FOR UPDATE
            ");
            $stmt->execute([$id]);
            $tanda = $stmt->fetch();

            if (!$tanda) {
                throw new Exception("La tanda no existe.");
            }
            if ($tanda['estado'] !== 'registrada') {
                throw new Exception("Esta tanda ya se empezó a amasar.");
            }

            $consumos = $this->calcularConsumo($tanda['producto_id'], $tanda['base_insumo_id'], (float)$tanda['base_cantidad']);
            if (!$tanda['forzar_stock']) {
                $this->validarStock($consumos);
            }

            $insConsumo = $this->pdo->prepare("INSERT INTO produccion_consumo (produccion_id, insumo_id, cantidad) VALUES (?, ?, ?)");
            $leerStock  = $this->pdo->prepare("SELECT stock FROM insumos WHERE id = ? FOR UPDATE");
            $updStock   = $this->pdo->prepare("UPDATE insumos SET stock = ? WHERE id = ?");
            $insMov     = $this->pdo->prepare("INSERT INTO movimientos (insumo_id, tipo, cantidad, stock_resultante, nota, usuario_id) VALUES (?, 'salida', ?, ?, ?, ?)");

            foreach ($consumos as $iid => $cantidad) {
                $insConsumo->execute([$id, $iid, $cantidad]);

                $leerStock->execute([$iid]);
                $nuevo = max(0, (float)$leerStock->fetchColumn() - $cantidad);
                $updStock->execute([$nuevo, $iid]);

                $insMov->execute([$iid, $cantidad, $nuevo, "Tanda #{$id} ({$tanda['producto']}) · amasado", $usuario_id]);
            }

            $this->pdo->prepare("UPDATE producciones SET estado = 'amasando', inicio_amasado = NOW() WHERE id = ?")->execute([$id]);

            $this->pdo->commit();
            return $tanda['producto'];
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    /* ======================================================================
       Consultas
       ====================================================================== */

    public function obtener($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM producciones WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
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

    /**
     * Tandas para la columna lateral: primero las registradas (pendientes de amasar),
     * luego las demás de la más reciente a la más antigua.
     */
    public function ultimasTandas($limite = 10) {
        $stmt = $this->pdo->prepare("
            SELECT pr.id, pr.fecha, pr.base_cantidad, pr.piezas, pr.nota, pr.estado,
                   p.nombre AS producto, i.unidad AS base_unidad
            FROM producciones pr
            JOIN productos p ON pr.producto_id = p.id
            JOIN insumos i ON p.base_insumo_id = i.id
            WHERE pr.anulada = 0
            ORDER BY (pr.estado = 'registrada') DESC, pr.id DESC
            LIMIT :limite
        ");
        $stmt->bindValue(':limite', (int)$limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
