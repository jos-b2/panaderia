<?php
/**
 * Controlador de Producción (Tandas) - Panadería Don Leo
 * Ubicación: /panaderia/Controllers/ProduccionController.php
 */

require_once __DIR__ . '/../Config/current_user.php';
require_once __DIR__ . '/../Models/ProduccionModel.php';

$produccionModel = new ProduccionModel($pdo);

$mensaje = '';
$tipo_alerta = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? 'registrar';

    if ($accion === 'registrar') {
        // Acepta la fecha como YYYY-MM-DD o DD/MM/YYYY
        $fecha_raw = trim($_POST['fecha'] ?? date('Y-m-d'));
        $fecha = preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $fecha_raw)
            ? DateTime::createFromFormat('d/m/Y', $fecha_raw)->format('Y-m-d')
            : $fecha_raw;

        $producto_id = (int)($_POST['producto_id'] ?? 0);
        $base_cantidad = (float)($_POST['cantidad_base'] ?? 0);
        $piezas = (int)($_POST['piezas'] ?? 0);
        $nota = trim($_POST['nota'] ?? '');
        $forzar_stock = isset($_POST['forzar_stock']);

        if ($producto_id > 0 && $base_cantidad > 0 && $piezas > 0) {
            try {
                $produccionModel->registrar($fecha, $producto_id, $base_cantidad, $piezas, $nota, $forzar_stock, $usuario_id);
                header("Location: ProduccionController.php?ok=1");
                exit;
            } catch (Exception $e) {
                $mensaje = $e->getMessage();
                $tipo_alerta = 'danger';
            }
        } else {
            $mensaje = "Selecciona un producto e ingresa cantidades válidas mayores a 0.";
            $tipo_alerta = 'warning';
        }
    } elseif ($accion === 'anular') {
        $produccion_id = (int)($_POST['produccion_id'] ?? 0);
        if ($produccion_id > 0) {
            try {
                $produccionModel->anular($produccion_id, $usuario_id);
                header("Location: ProduccionController.php?ok=anulada");
                exit;
            } catch (Exception $e) {
                $mensaje = "Error al anular la tanda: " . $e->getMessage();
                $tipo_alerta = 'danger';
            }
        }
    }
}

if (isset($_GET['ok'])) {
    $mensaje = $_GET['ok'] === 'anulada' ? 'Tanda anulada y existencias restauradas.' : 'Tanda de producción registrada correctamente.';
    $tipo_alerta = 'success';
}

$productos = $produccionModel->productosActivosConBase();

// Agrupar ingredientes por producto
$recetasPorProducto = [];
foreach ($produccionModel->recetasConStock() as $r) {
    $pid = (int)$r['producto_id'];
    $recetasPorProducto[$pid][] = [
        'insumo_id' => (int)$r['insumo_id'],
        'nombre' => $r['nombre'],
        'unidad' => $r['unidad'],
        'stock' => (float)$r['stock'],
        'cantidad' => (float)$r['cantidad']
    ];
}

// Datos que usa produccion.js para calcular el consumo en pantalla
$dataProductos = [];
foreach ($productos as $p) {
    $pid = (int)$p['id'];
    $dataProductos[$pid] = [
        'id' => $pid,
        'nombre' => $p['nombre'],
        'rendimiento' => (float)$p['rendimiento'],
        'base' => [
            'insumo_id' => (int)$p['base_insumo_id'],
            'nombre' => $p['base_nombre'],
            'unidad' => $p['base_unidad'],
            'stock' => (float)$p['base_stock']
        ],
        'items' => $recetasPorProducto[$pid] ?? []
    ];
}

$ultimasTandas = $produccionModel->ultimasTandas(10);

$pagina_activa = 'produccion';

require __DIR__ . '/../src/Views/Admin/produccion.php';
