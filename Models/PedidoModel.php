<?php
/**
 * Modelo de Pedidos, Clientes y Abonos - Panadería Don Leo
 * Ubicación: /panaderia/Models/PedidoModel.php
 */

class PedidoModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Crea el pedido. Si el cliente no existe (por nombre) lo crea, y registra el abono inicial si lo hay.
     */
    public function crear($cliente_nombre, $cliente_tel, $descripcion, $fecha_entrega, $total, $abono_inicial, $nota, $usuario_id) {
        try {
            $this->pdo->beginTransaction();

            $stmtCli = $this->pdo->prepare("SELECT id FROM clientes WHERE nombre = ? LIMIT 1");
            $stmtCli->execute([$cliente_nombre]);
            $cliente_id = $stmtCli->fetchColumn();

            if (!$cliente_id) {
                $stmtNewCli = $this->pdo->prepare("INSERT INTO clientes (nombre, telefono) VALUES (?, ?)");
                $stmtNewCli->execute([$cliente_nombre, $cliente_tel !== '' ? $cliente_tel : null]);
                $cliente_id = $this->pdo->lastInsertId();
            }

            $stmtPed = $this->pdo->prepare("INSERT INTO pedidos (cliente_id, descripcion, fecha_entrega, total, estado, nota, usuario_id) VALUES (?, ?, ?, ?, 'pendiente', ?, ?)");
            $stmtPed->execute([$cliente_id, $descripcion, $fecha_entrega, $total, $nota !== '' ? $nota : null, $usuario_id]);
            $pedido_id = $this->pdo->lastInsertId();

            if ($abono_inicial > 0) {
                $stmtAb = $this->pdo->prepare("INSERT INTO abonos (pedido_id, monto, nota) VALUES (?, ?, 'Abono inicial')");
                $stmtAb->execute([$pedido_id, $abono_inicial]);
            }

            $this->pdo->commit();
            return $pedido_id;
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    public function cambiarEstado($pedido_id, $estado) {
        $stmt = $this->pdo->prepare("UPDATE pedidos SET estado = ? WHERE id = ?");
        $stmt->execute([$estado, $pedido_id]);
    }

    public function abonar($pedido_id, $monto) {
        $stmt = $this->pdo->prepare("INSERT INTO abonos (pedido_id, monto, nota) VALUES (?, ?, 'Abono registrado')");
        $stmt->execute([$pedido_id, $monto]);
    }

    /**
     * Lista pedidos con su cliente y lo abonado.
     * $filtro: 'activos' | 'pendientes' | 'entregados' | cualquier otro valor = todos.
     */
    public function listar($filtro, $buscar) {
        $where = [];
        $params = [];

        if ($filtro === 'activos') {
            $where[] = "p.estado IN ('pendiente', 'en_produccion', 'listo')";
        } elseif ($filtro === 'pendientes') {
            $where[] = "p.estado = 'pendiente'";
        } elseif ($filtro === 'entregados') {
            $where[] = "p.estado = 'entregado'";
        }

        if ($buscar !== '') {
            $where[] = "(c.nombre LIKE ? OR p.descripcion LIKE ?)";
            $params[] = "%{$buscar}%";
            $params[] = "%{$buscar}%";
        }

        $sql = "
            SELECT p.id, p.descripcion, p.fecha_entrega, p.total, p.estado, p.nota,
                   c.nombre AS cliente, c.telefono,
                   COALESCE((SELECT SUM(a.monto) FROM abonos a WHERE a.pedido_id = p.id), 0) AS abonado
            FROM pedidos p
            JOIN clientes c ON p.cliente_id = c.id
        ";

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY p.fecha_entrega ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
