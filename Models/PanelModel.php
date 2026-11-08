<?php
/**
 * Modelo de Datos del Panel - Panadería Don Leo
 * Ubicación: /panaderia/Models/PanelModel.php
 */

class PanelModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getHorneadasHoy() {
        return (int)$this->pdo->query("SELECT COALESCE(SUM(piezas), 0) FROM producciones WHERE fecha = CURDATE() AND anulada = 0")->fetchColumn();
    }

    public function getVentasArqueoHoy() {
        return (float)$this->pdo->query("SELECT COALESCE(SUM(ad.vendidas * p.precio), 0) FROM arqueo_detalle ad JOIN productos p ON ad.producto_id = p.id WHERE ad.fecha = CURDATE()")->fetchColumn();
    }

    public function getAbonosHoy() {
        return (float)$this->pdo->query("SELECT COALESCE(SUM(monto), 0) FROM abonos WHERE fecha = CURDATE()")->fetchColumn();
    }

    public function getPedidosActivosCount() {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM pedidos WHERE estado IN ('pendiente', 'en_produccion', 'listo')")->fetchColumn();
    }

    public function getInsumosEnAlerta() {
        return $this->pdo->query("SELECT nombre, unidad, stock, stock_minimo FROM insumos WHERE activo = 1 AND stock <= stock_minimo ORDER BY stock ASC")->fetchAll();
    }

    public function getProximosPedidos($limite = 6) {
        $stmt = $this->pdo->prepare("
            SELECT p.id, c.nombre AS cliente, p.descripcion, p.fecha_entrega, p.total,
                   COALESCE((SELECT SUM(a.monto) FROM abonos a WHERE a.pedido_id = p.id), 0) AS abonado
            FROM pedidos p
            JOIN clientes c ON p.cliente_id = c.id
            WHERE p.estado IN ('pendiente', 'en_produccion', 'listo')
            ORDER BY p.fecha_entrega ASC
            LIMIT :limite
        ");
        $stmt->bindValue(':limite', (int)$limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** Piezas horneadas por fecha en los últimos 7 días: [fecha => total] */
    public function getProduccionSemana() {
        return $this->pdo->query("SELECT fecha, COALESCE(SUM(piezas), 0) AS total FROM producciones WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND anulada = 0 GROUP BY fecha")->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /** Piezas vendidas en el arqueo por fecha en los últimos 7 días: [fecha => total] */
    public function getVentasArqueoSemana() {
        return $this->pdo->query("SELECT fecha, COALESCE(SUM(vendidas), 0) AS total FROM arqueo_detalle WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY fecha")->fetchAll(PDO::FETCH_KEY_PAIR);
    }
}
