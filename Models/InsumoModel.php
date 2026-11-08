<?php
/**
 * Modelo de Materia Prima (Insumos y Movimientos) - Panadería Don Leo
 * Ubicación: /panaderia/Models/InsumoModel.php
 */

class InsumoModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function alternarActivo($id) {
        $stmt = $this->pdo->prepare("UPDATE insumos SET activo = NOT activo WHERE id = ?");
        $stmt->execute([$id]);
    }

    public function existeNombre($nombre) {
        $stmt = $this->pdo->prepare("SELECT id FROM insumos WHERE nombre = ?");
        $stmt->execute([$nombre]);
        return (bool)$stmt->fetch();
    }

    public function crear($nombre, $unidad, $stock, $stock_minimo, $costo) {
        $stmt = $this->pdo->prepare("INSERT INTO insumos (nombre, unidad, stock, stock_minimo, costo) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nombre, $unidad, $stock, $stock_minimo, $costo]);
    }

    public function actualizar($id, $nombre, $unidad, $stock_minimo, $costo) {
        $stmt = $this->pdo->prepare("UPDATE insumos SET nombre = ?, unidad = ?, stock_minimo = ?, costo = ? WHERE id = ?");
        $stmt->execute([$nombre, $unidad, $stock_minimo, $costo, $id]);
    }

    /**
     * Registra una entrada, salida o ajuste y actualiza el stock en una sola transacción.
     */
    public function registrarMovimiento($insumo_id, $tipo, $cantidad, $nota, $usuario_id) {
        try {
            $this->pdo->beginTransaction();

            $stmtStock = $this->pdo->prepare("SELECT stock FROM insumos WHERE id = ? FOR UPDATE");
            $stmtStock->execute([$insumo_id]);
            $stock_actual = (float)$stmtStock->fetchColumn();

            if ($tipo === 'entrada') {
                $nuevo_stock = $stock_actual + $cantidad;
            } elseif ($tipo === 'salida') {
                $nuevo_stock = max(0, $stock_actual - $cantidad);
            } else {
                $nuevo_stock = $cantidad;
            }

            $stmtUpd = $this->pdo->prepare("UPDATE insumos SET stock = ? WHERE id = ?");
            $stmtUpd->execute([$nuevo_stock, $insumo_id]);

            $stmtMov = $this->pdo->prepare("INSERT INTO movimientos (insumo_id, tipo, cantidad, stock_resultante, nota, usuario_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmtMov->execute([$insumo_id, $tipo, $cantidad, $nuevo_stock, $nota !== '' ? $nota : null, $usuario_id]);

            $this->pdo->commit();
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    public function obtener($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM insumos WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function valorInventario() {
        return (float)$this->pdo->query("SELECT COALESCE(SUM(stock * costo), 0) FROM insumos WHERE activo = 1")->fetchColumn();
    }

    public function listar($incluirInactivos = false) {
        $sql = $incluirInactivos
            ? "SELECT * FROM insumos ORDER BY activo DESC, nombre ASC"
            : "SELECT * FROM insumos WHERE activo = 1 ORDER BY nombre ASC";
        return $this->pdo->query($sql)->fetchAll();
    }

    /** Insumos activos para elegir en las recetas. */
    public function listarActivos() {
        return $this->pdo->query("SELECT id, nombre, unidad, costo FROM insumos WHERE activo = 1 ORDER BY nombre ASC")->fetchAll();
    }

    public function ultimosMovimientos($limite = 30) {
        $stmt = $this->pdo->prepare("
            SELECT m.fecha, m.tipo, m.cantidad, m.stock_resultante, m.nota,
                   i.nombre AS insumo, i.unidad,
                   u.usuario AS usuario_nick
            FROM movimientos m
            JOIN insumos i ON m.insumo_id = i.id
            LEFT JOIN usuarios u ON m.usuario_id = u.id
            ORDER BY m.fecha DESC
            LIMIT :limite
        ");
        $stmt->bindValue(':limite', (int)$limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
