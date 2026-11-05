<?php
/**
 * Modelo de Datos del Panel - Panadería Don Leo
 * Ubicación: /panaderia/src/Models/PanelModel.php
 */

class PanelModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo =$pdo;
    }

    public function getHorneadasHoy($fecha) {
        $stmt =$this->pdo->prepare("SELECT COALESCE(SUM(piezas), 0) FROM producciones WHERE fecha = :fecha AND anulada = 0");
        $stmt->execute([':fecha' =>$fecha]);
        return (int)$stmt->fetchColumn();
    }

    public function getVentasHoy($fecha) {
        $stmt =$this->pdo->prepare("
            SELECT COALESCE(SUM(a.vendidas * p.precio), 0) 
            FROM arqueo_detalle a 
            JOIN productos p ON p.id = a.producto_id 
            WHERE a.fecha = :fecha
        ");
        $stmt->execute([':fecha' =>$fecha]);
        return (float)$stmt->fetchColumn();
    }

    public function getPedidosActivosCount() {
        $stmt =$this->pdo->query("SELECT COUNT(*) FROM pedidos WHERE estado IN ('pendiente', 'en_produccion', 'listo')");
        return (int)$stmt->fetchColumn();
    }

    public function getInsumosEnAlerta() {
        $stmt =$this->pdo->query("
            SELECT * FROM insumos 
            WHERE activo = 1 AND stock <= stock_minimo 
            ORDER BY (stock * 1.0 / CASE WHEN stock_minimo = 0 THEN 1 ELSE stock_minimo END), nombre
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProximosPedidos($limit = 6) {
        $stmt =$this->pdo->prepare("
            SELECT p.*, c.nombre AS cliente,
                COALESCE((SELECT SUM(monto) FROM abonos WHERE pedido_id = p.id), 0) AS abonado
            FROM pedidos p 
            JOIN clientes c ON c.id = p.cliente_id
            WHERE p.estado IN ('pendiente', 'en_produccion', 'listo')
            ORDER BY p.fecha_entrega, p.id 
            LIMIT :limite
        ");
        $stmt->bindValue(':limite', $limit, PDO::PARAM_INT);$stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}