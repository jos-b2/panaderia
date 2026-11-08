<?php
/**
 * Modelo de Reportes - Panadería Don Leo
 * Ubicación: /panaderia/Models/ReporteModel.php
 *
 * Todas las consultas reciben un rango de fechas (Y-m-d) inclusivo.
 * Las series diarias se completan con ceros para que las gráficas no tengan huecos.
 */

class ReporteModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /** Lista de fechas Y-m-d entre $desde y $hasta (inclusive). */
    public static function dias($desde, $hasta) {
        $dias = [];
        $d = new DateTime($desde);
        $fin = new DateTime($hasta);
        while ($d <= $fin) {
            $dias[] = $d->format('Y-m-d');
            $d->modify('+1 day');
        }
        return $dias;
    }

    private function porFecha($sql, $desde, $hasta) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$desde, $hasta]);
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /** Piezas horneadas y vendidas por día. */
    public function produccionVsVentas($desde, $hasta) {
        $horneadas = $this->porFecha("SELECT fecha, SUM(piezas) FROM producciones WHERE anulada = 0 AND fecha BETWEEN ? AND ? GROUP BY fecha", $desde, $hasta);
        $vendidas  = $this->porFecha("SELECT fecha, SUM(vendidas) FROM arqueo_detalle WHERE fecha BETWEEN ? AND ? GROUP BY fecha", $desde, $hasta);

        $filas = [];
        foreach (self::dias($desde, $hasta) as $dia) {
            $filas[] = [
                'fecha'     => $dia,
                'horneadas' => (int)($horneadas[$dia] ?? 0),
                'vendidas'  => (int)($vendidas[$dia] ?? 0),
            ];
        }
        return $filas;
    }

    /** Ingresos por día: ventas del arqueo (vendidas × precio) y abonos de pedidos. */
    public function ingresosPorDia($desde, $hasta) {
        $ventas = $this->porFecha("
            SELECT ad.fecha, SUM(ad.vendidas * p.precio)
            FROM arqueo_detalle ad JOIN productos p ON p.id = ad.producto_id
            WHERE ad.fecha BETWEEN ? AND ? GROUP BY ad.fecha", $desde, $hasta);
        $abonos = $this->porFecha("SELECT fecha, SUM(monto) FROM abonos WHERE fecha BETWEEN ? AND ? GROUP BY fecha", $desde, $hasta);

        $filas = [];
        foreach (self::dias($desde, $hasta) as $dia) {
            $v = round((float)($ventas[$dia] ?? 0), 2);
            $a = round((float)($abonos[$dia] ?? 0), 2);
            $filas[] = ['fecha' => $dia, 'ventas' => $v, 'abonos' => $a, 'total' => round($v + $a, 2)];
        }
        return $filas;
    }

    /** Por producto: piezas horneadas, piezas vendidas e ingreso por ventas. */
    public function porProducto($desde, $hasta) {
        $stmt = $this->pdo->prepare("
            SELECT p.nombre, p.precio,
                   COALESCE((SELECT SUM(pr.piezas) FROM producciones pr
                             WHERE pr.producto_id = p.id AND pr.anulada = 0 AND pr.fecha BETWEEN ? AND ?), 0) AS horneadas,
                   COALESCE((SELECT SUM(ad.vendidas) FROM arqueo_detalle ad
                             WHERE ad.producto_id = p.id AND ad.fecha BETWEEN ? AND ?), 0) AS vendidas
            FROM productos p
            ORDER BY p.nombre ASC
        ");
        $stmt->execute([$desde, $hasta, $desde, $hasta]);

        return array_map(function ($f) {
            return [
                'producto'  => $f['nombre'],
                'precio'    => (float)$f['precio'],
                'horneadas' => (int)$f['horneadas'],
                'vendidas'  => (int)$f['vendidas'],
                'ingreso'   => round((int)$f['vendidas'] * (float)$f['precio'], 2),
            ];
        }, $stmt->fetchAll());
    }

    /** Pedidos creados en el periodo agrupados por estado, con su monto total. */
    public function pedidosPorEstado($desde, $hasta) {
        $stmt = $this->pdo->prepare("
            SELECT estado, COUNT(*) AS cantidad, COALESCE(SUM(total), 0) AS monto
            FROM pedidos
            WHERE DATE(creado) BETWEEN ? AND ?
            GROUP BY estado
        ");
        $stmt->execute([$desde, $hasta]);
        $datos = [];
        foreach ($stmt->fetchAll() as $f) {
            $datos[$f['estado']] = ['cantidad' => (int)$f['cantidad'], 'monto' => (float)$f['monto']];
        }

        $nombres = ['pendiente' => 'Pendiente', 'en_produccion' => 'En producción', 'listo' => 'Listo', 'entregado' => 'Entregado', 'cancelado' => 'Cancelado'];
        $filas = [];
        foreach ($nombres as $clave => $nombre) {
            $filas[] = [
                'estado'   => $nombre,
                'cantidad' => $datos[$clave]['cantidad'] ?? 0,
                'monto'    => round($datos[$clave]['monto'] ?? 0, 2),
            ];
        }
        return $filas;
    }

    /** Inventario actual de insumos activos (no depende del periodo). */
    public function inventario() {
        $filas = $this->pdo->query("SELECT nombre, unidad, stock, stock_minimo, costo FROM insumos WHERE activo = 1 ORDER BY nombre ASC")->fetchAll();
        return array_map(function ($f) {
            return [
                'insumo' => $f['nombre'],
                'unidad' => $f['unidad'],
                'stock'  => (float)$f['stock'],
                'minimo' => (float)$f['stock_minimo'],
                'costo'  => (float)$f['costo'],
                'valor'  => round((float)$f['stock'] * (float)$f['costo'], 2),
            ];
        }, $filas);
    }

    /** Arqueos guardados en el periodo: ingreso esperado vs dinero contado. */
    public function caja($desde, $hasta) {
        $stmt = $this->pdo->prepare("
            SELECT ac.fecha, ac.caja_real,
                   COALESCE(SUM(ad.vendidas * p.precio), 0) AS esperado
            FROM arqueo_caja ac
            LEFT JOIN arqueo_detalle ad ON ad.fecha = ac.fecha
            LEFT JOIN productos p ON p.id = ad.producto_id
            WHERE ac.fecha BETWEEN ? AND ?
            GROUP BY ac.fecha, ac.caja_real
            ORDER BY ac.fecha ASC
        ");
        $stmt->execute([$desde, $hasta]);

        return array_map(function ($f) {
            $esperado = round((float)$f['esperado'], 2);
            $real = $f['caja_real'] === null ? null : round((float)$f['caja_real'], 2);
            return [
                'fecha'      => $f['fecha'],
                'esperado'   => $esperado,
                'contado'    => $real,
                'diferencia' => $real === null ? null : round($real - $esperado, 2),
            ];
        }, $stmt->fetchAll());
    }
}
