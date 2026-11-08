<?php
/**
 * Controlador de Producción (Tandas) - Panadería Don Leo
 * Ubicación: /panaderia/Controllers/ProduccionController.php
 *
 * Acciones (POST "accion"):
 *   registrar -> anota la tanda (no toca inventario)
 *   modificar -> cambia una tanda registrada (?editar=ID muestra el formulario)
 *   eliminar  -> borra una tanda registrada
 *   amasar    -> empieza a amasar: descuenta insumos y la manda a Hornear
 */

require_once __DIR__ . '/../Config/current_user.php';
require_once __DIR__ . '/../Models/ProduccionModel.php';

$produccionModel = new ProduccionModel($pdo);

$mensaje = '';
$tipo_alerta = '';

/** Lee y valida los campos del formulario de tanda. Devuelve null si faltan datos. */
function leerTanda() {
    $fecha_raw = trim($_POST['fecha'] ?? date('Y-m-d'));
    $fecha = preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $fecha_raw)
        ? DateTime::createFromFormat('d/m/Y', $fecha_raw)->format('Y-m-d')
        : $fecha_raw;

    $t = [
        'fecha'         => $fecha,
        'producto_id'   => (int)($_POST['producto_id'] ?? 0),
        'base_cantidad' => (float)($_POST['cantidad_base'] ?? 0),
        'piezas'        => (int)($_POST['piezas'] ?? 0),
        'nota'          => trim($_POST['nota'] ?? ''),
        'forzar'        => isset($_POST['forzar_stock']),
    ];
    return ($t['producto_id'] > 0 && $t['base_cantidad'] > 0 && $t['piezas'] > 0) ? $t : null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? 'registrar';
    $id = (int)($_POST['produccion_id'] ?? 0);

    try {
        if ($accion === 'registrar' || $accion === 'modificar') {
            $t = leerTanda();
            if (!$t) {
                throw new InvalidArgumentException("Selecciona un producto e ingresa cantidades válidas mayores a 0.");
            }
            if ($accion === 'registrar') {
                $produccionModel->registrar($t['fecha'], $t['producto_id'], $t['base_cantidad'], $t['piezas'], $t['nota'], $t['forzar'], $usuario_id);
                header("Location: ProduccionController.php?ok=registrada");
            } else {
                $produccionModel->modificar($id, $t['fecha'], $t['producto_id'], $t['base_cantidad'], $t['piezas'], $t['nota'], $t['forzar']);
                header("Location: ProduccionController.php?ok=modificada");
            }
            exit;
        }

        if ($accion === 'eliminar' && $id > 0) {
            $produccionModel->eliminar($id);
            header("Location: ProduccionController.php?ok=eliminada");
            exit;
        }

        if ($accion === 'amasar' && $id > 0) {
            $produccionModel->empezarAmasado($id, $usuario_id);
            header("Location: ProduccionController.php?ok=amasando");
            exit;
        }
    } catch (InvalidArgumentException $e) {
        $mensaje = $e->getMessage();
        $tipo_alerta = 'warning';
    } catch (Exception $e) {
        $mensaje = $e->getMessage();
        $tipo_alerta = 'danger';
    }
}

$mensajesOk = [
    'registrada' => 'Tanda registrada. Cuando empieces a amasar, pulsa "Empezar a amasar" para descontar los insumos.',
    'modificada' => 'Tanda modificada correctamente.',
    'eliminada'  => 'Tanda eliminada.',
    'amasando'   => 'Tanda en amasado: los insumos se descontaron del inventario. Ya aparece en Hornear.',
];
if (isset($_GET['ok']) && isset($mensajesOk[$_GET['ok']])) {
    $mensaje = $mensajesOk[$_GET['ok']];
    $tipo_alerta = 'success';
}

// Modo edición: ?editar=ID (solo tandas registradas)
$tandaEditar = null;
if (isset($_GET['editar'])) {
    $tandaEditar = $produccionModel->obtener((int)$_GET['editar']);
    if (!$tandaEditar || $tandaEditar['estado'] !== 'registrada') {
        $tandaEditar = null;
        $mensaje = 'Esa tanda ya se empezó a amasar y no se puede modificar.';
        $tipo_alerta = 'warning';
    }
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
