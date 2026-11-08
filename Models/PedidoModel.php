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
     * Crea el pedido con sus líneas de producto ($items = [[producto_id, cantidad], ...]).
     * Si el cliente no existe (por nombre) lo crea, y registra el abono inicial si lo hay.
     */
    public function crear($cliente_nombre, $cliente_tel, $descripcion, $fecha_entrega, $total, $abono_inicial, $nota, $usuario_id, $items = []) {
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

            $stmtItem = $this->pdo->prepare("INSERT INTO pedido_items (pedido_id, producto_id, cantidad) VALUES (?, ?, ?)");
            foreach ($items as [$producto_id, $cantidad]) {
                $stmtItem->execute([$pedido_id, $producto_id, $cantidad]);
            }

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

    /** Total, lo abonado y el saldo de un pedido. */
    public function saldo($pedido_id) {
        $stmt = $this->pdo->prepare("
            SELECT p.total, p.estado,
                   COALESCE((SELECT SUM(a.monto) FROM abonos a WHERE a.pedido_id = p.id), 0) AS abonado
            FROM pedidos p WHERE p.id = ?");
        $stmt->execute([$pedido_id]);
        $f = $stmt->fetch();
        if (!$f) return null;
        return ['total' => (float)$f['total'], 'abonado' => (float)$f['abonado'],
                'saldo' => max(0, round((float)$f['total'] - (float)$f['abonado'], 2)), 'estado' => $f['estado']];
    }

    /**
     * Cambia el estado. Al pasar a "entregado" se registra la venta: queda la hora de entrega
     * (las piezas salen del pan disponible y cuentan como vendidas) y, si se pide,
     * se cobra el saldo pendiente como abono "Cobro al entregar".
     * Si un pedido entregado vuelve a otro estado, se quita la hora de entrega.
     * Devuelve el monto cobrado al entregar (0 si no hubo).
     */
    public function cambiarEstado($pedido_id, $estado, $cobrarSaldo = false) {
        try {
            $this->pdo->beginTransaction();

            $info = $this->saldo($pedido_id);
            if (!$info) {
                throw new Exception("El pedido no existe.");
            }

            $cobrado = 0;
            if ($estado === 'entregado') {
                if ($info['estado'] !== 'entregado') {
                    $this->pdo->prepare("UPDATE pedidos SET estado = 'entregado', entregado_en = NOW() WHERE id = ?")->execute([$pedido_id]);
                }
                if ($cobrarSaldo && $info['saldo'] > 0) {
                    $this->pdo->prepare("INSERT INTO abonos (pedido_id, monto, nota) VALUES (?, ?, 'Cobro al entregar')")
                              ->execute([$pedido_id, $info['saldo']]);
                    $cobrado = $info['saldo'];
                }
            } else {
                $this->pdo->prepare("UPDATE pedidos SET estado = ?, entregado_en = NULL WHERE id = ?")->execute([$estado, $pedido_id]);
            }

            $this->pdo->commit();
            return $cobrado;
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Ventas por encargo de una fecha: pedidos entregados ese día, con sus productos,
     * total, lo cobrado ese día y el saldo que quedó pendiente.
     */
    public function ventasDelDia($fecha) {
        $stmt = $this->pdo->prepare("
            SELECT p.id, p.total, p.entregado_en, c.nombre AS cliente,
                   COALESCE((SELECT SUM(a.monto) FROM abonos a WHERE a.pedido_id = p.id), 0) AS abonado,
                   (SELECT GROUP_CONCAT(CONCAT(pi.cantidad, ' ', pr.nombre) ORDER BY pi.id SEPARATOR ' · ')
                    FROM pedido_items pi JOIN productos pr ON pr.id = pi.producto_id WHERE pi.pedido_id = p.id) AS productos,
                   COALESCE((SELECT SUM(pi.cantidad) FROM pedido_items pi WHERE pi.pedido_id = p.id), 0) AS piezas
            FROM pedidos p JOIN clientes c ON c.id = p.cliente_id
            WHERE p.estado = 'entregado' AND DATE(p.entregado_en) = ?
            ORDER BY p.entregado_en DESC
        ");
        $stmt->execute([$fecha]);
        return array_map(function ($v) {
            $v['saldo'] = max(0, round((float)$v['total'] - (float)$v['abonado'], 2));
            return $v;
        }, $stmt->fetchAll());
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
                   COALESCE((SELECT SUM(a.monto) FROM abonos a WHERE a.pedido_id = p.id), 0) AS abonado,
                   (SELECT GROUP_CONCAT(CONCAT(pi.cantidad, ' ', pr.nombre) ORDER BY pi.id SEPARATOR ' · ')
                    FROM pedido_items pi JOIN productos pr ON pr.id = pi.producto_id
                    WHERE pi.pedido_id = p.id) AS items_texto
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

    /**
     * Pan disponible hoy por producto y cobertura de los encargos de hoy.
     *
     *   disponible   = sobrante de ayer + tandas listas hoy − entregado hoy en pedidos − vendido hoy (arqueo)
     *   en_proceso   = tandas de hoy registradas, amasando o en el horno (viene en camino)
     *   para_encargos= piezas de pedidos activos con entrega hoy o atrasados
     *   para_vender  = disponible − para_encargos (si es negativo, faltan piezas)
     *
     * Los encargos se cubren por orden de hora de entrega.
     */
    public function disponibilidad() {
        $porProducto = function ($sql) {
            return $this->pdo->query($sql)->fetchAll(PDO::FETCH_KEY_PAIR);
        };

        $sobranteAyer = $porProducto("SELECT producto_id, SUM(sobrante) FROM arqueo_detalle WHERE fecha = CURDATE() - INTERVAL 1 DAY GROUP BY producto_id");
        $listasHoy    = $porProducto("SELECT producto_id, SUM(piezas) FROM producciones WHERE fecha = CURDATE() AND estado = 'lista' AND anulada = 0 GROUP BY producto_id");
        $enProceso    = $porProducto("SELECT producto_id, SUM(piezas) FROM producciones WHERE fecha = CURDATE() AND estado IN ('registrada', 'amasando', 'horneando') AND anulada = 0 GROUP BY producto_id");
        $vendidasHoy  = $porProducto("SELECT producto_id, SUM(vendidas) FROM arqueo_detalle WHERE fecha = CURDATE() GROUP BY producto_id");
        $entregadoHoy = $porProducto("
            SELECT pi.producto_id, SUM(pi.cantidad) FROM pedido_items pi JOIN pedidos p ON p.id = pi.pedido_id
            WHERE p.estado = 'entregado' AND DATE(p.entregado_en) = CURDATE() GROUP BY pi.producto_id");

        // Encargos activos que se entregan hoy (o que ya debieron entregarse)
        $encargos = $this->pdo->query("
            SELECT p.id, p.fecha_entrega, c.nombre AS cliente
            FROM pedidos p JOIN clientes c ON c.id = p.cliente_id
            WHERE p.estado IN ('pendiente', 'en_produccion', 'listo') AND DATE(p.fecha_entrega) <= CURDATE()
              AND EXISTS (SELECT 1 FROM pedido_items pi WHERE pi.pedido_id = p.id)
            ORDER BY p.fecha_entrega ASC, p.id ASC
        ")->fetchAll();

        $itemsStmt = $this->pdo->prepare("SELECT pi.producto_id, pi.cantidad, pr.nombre FROM pedido_items pi JOIN productos pr ON pr.id = pi.producto_id WHERE pi.pedido_id = ?");
        $reservado = [];
        foreach ($encargos as &$e) {
            $itemsStmt->execute([$e['id']]);
            $e['items'] = $itemsStmt->fetchAll();
            foreach ($e['items'] as $it) {
                $reservado[$it['producto_id']] = ($reservado[$it['producto_id']] ?? 0) + (int)$it['cantidad'];
            }
        }
        unset($e);

        // Resumen por producto
        $productos = [];
        $restante = [];   // pan aún sin asignar, para repartir entre los encargos
        foreach ($this->pdo->query("SELECT id, nombre FROM productos WHERE activo = 1 ORDER BY nombre ASC")->fetchAll() as $p) {
            $id = (int)$p['id'];
            $disponible = max(0, (int)($sobranteAyer[$id] ?? 0) + (int)($listasHoy[$id] ?? 0) - (int)($entregadoHoy[$id] ?? 0) - (int)($vendidasHoy[$id] ?? 0));
            $paraEncargos = (int)($reservado[$id] ?? 0);
            $productos[] = [
                'id'            => $id,
                'nombre'        => $p['nombre'],
                'disponible'    => $disponible,
                'en_proceso'    => (int)($enProceso[$id] ?? 0),
                'para_encargos' => $paraEncargos,
                'para_vender'   => $disponible - $paraEncargos,
            ];
            $restante[$id] = $disponible;
        }

        // Cobertura de cada encargo, en orden de entrega
        foreach ($encargos as &$e) {
            $faltan = [];
            foreach ($e['items'] as $it) {
                $pid = (int)$it['producto_id'];
                $cubre = min((int)$it['cantidad'], max(0, $restante[$pid] ?? 0));
                $restante[$pid] = ($restante[$pid] ?? 0) - $cubre;
                if ($cubre < (int)$it['cantidad']) {
                    $faltan[] = ((int)$it['cantidad'] - $cubre) . ' ' . $it['nombre'];
                }
            }
            $e['cubierto'] = empty($faltan);
            $e['faltan'] = $faltan;
            $e['resumen'] = implode(' · ', array_map(fn($it) => $it['cantidad'] . ' ' . $it['nombre'], $e['items']));
        }
        unset($e);

        return ['productos' => $productos, 'encargos' => $encargos];
    }
}
