<?php
/**
 * Modelo de Arqueo Diario - Panadería Don Leo
 * Ubicación: /panaderia/Models/ArqueoModel.php
 */

class ArqueoModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Guarda (o reemplaza) el arqueo de una fecha: vendidas y sobrante por producto, y el dinero contado.
     * $vendidas y $sobrante son arreglos [producto_id => cantidad].
     */
    public function guardar($fecha, $vendidas, $sobrante, $dinero_contado, $observaciones) {
        try {
            $this->pdo->beginTransaction();

            $stmtDet = $this->pdo->prepare("
                INSERT INTO arqueo_detalle (fecha, producto_id, vendidas, sobrante)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE vendidas = VALUES(vendidas), sobrante = VALUES(sobrante)
            ");

            foreach ($vendidas as $pid => $cant_v) {
                $pid = (int)$pid;
                $v = max(0, (int)$cant_v);
                $s = max(0, (int)($sobrante[$pid] ?? 0));
                $stmtDet->execute([$fecha, $pid, $v, $s]);
            }

            $stmtCaja = $this->pdo->prepare("
                INSERT INTO arqueo_caja (fecha, caja_real, nota)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE caja_real = VALUES(caja_real), nota = VALUES(nota)
            ");
            $stmtCaja->execute([$fecha, $dinero_contado, $observaciones !== '' ? $observaciones : null]);

            $this->pdo->commit();
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    /** Sobrante contado por producto en una fecha: [producto_id => sobrante] */
    public function sobrantesDe($fecha) {
        $stmt = $this->pdo->prepare("SELECT producto_id, sobrante FROM arqueo_detalle WHERE fecha = ?");
        $stmt->execute([$fecha]);
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /** Piezas horneadas (tandas no anuladas) por producto en una fecha: [producto_id => piezas] */
    public function horneadasDe($fecha) {
        $stmt = $this->pdo->prepare("SELECT producto_id, COALESCE(SUM(piezas), 0) FROM producciones WHERE fecha = ? AND anulada = 0 AND estado = 'lista' GROUP BY producto_id");
        $stmt->execute([$fecha]);
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /** Detalle guardado de una fecha: [producto_id => fila] */
    public function detalleDe($fecha) {
        $stmt = $this->pdo->prepare("SELECT producto_id, vendidas, sobrante FROM arqueo_detalle WHERE fecha = ?");
        $stmt->execute([$fecha]);
        $detalle = [];
        foreach ($stmt->fetchAll() as $row) {
            $detalle[(int)$row['producto_id']] = $row;
        }
        return $detalle;
    }

    public function cajaDe($fecha) {
        $stmt = $this->pdo->prepare("SELECT caja_real, nota FROM arqueo_caja WHERE fecha = ?");
        $stmt->execute([$fecha]);
        return $stmt->fetch() ?: ['caja_real' => '', 'nota' => ''];
    }

    /** Piezas entregadas en pedidos en una fecha (salen del pan del día): [producto_id => piezas] */
    public function entregadasDe($fecha) {
        $stmt = $this->pdo->prepare("
            SELECT pi.producto_id, SUM(pi.cantidad)
            FROM pedido_items pi JOIN pedidos p ON p.id = pi.pedido_id
            WHERE p.estado = 'entregado' AND DATE(p.entregado_en) = ?
            GROUP BY pi.producto_id
        ");
        $stmt->execute([$fecha]);
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /** Dinero de pedidos recibido en una fecha (abonos y cobros al entregar). */
    public function abonosDe($fecha) {
        $stmt = $this->pdo->prepare("SELECT COALESCE(SUM(monto), 0) FROM abonos WHERE fecha = ?");
        $stmt->execute([$fecha]);
        return (float)$stmt->fetchColumn();
    }

    /** Historial: el esperado en caja = ventas de mostrador + abonos de pedidos de ese día. */
    public function historial($limite = 15) {
        $stmt = $this->pdo->prepare("
            SELECT ac.fecha, ac.caja_real, ac.nota,
                   COALESCE((SELECT SUM(ad.vendidas * p.precio) FROM arqueo_detalle ad JOIN productos p ON p.id = ad.producto_id WHERE ad.fecha = ac.fecha), 0)
                 + COALESCE((SELECT SUM(a.monto) FROM abonos a WHERE a.fecha = ac.fecha), 0) AS esperado
            FROM arqueo_caja ac
            ORDER BY ac.fecha DESC
            LIMIT :limite
        ");
        $stmt->bindValue(':limite', (int)$limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
