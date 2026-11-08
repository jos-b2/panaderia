<?php
/**
 * Controlador de Pedidos y Abonos - Panadería Don Leo
 * Ubicación: /panaderia/Controllers/PedidosController.php
 */

require_once __DIR__ . '/../Config/current_user.php';
require_once __DIR__ . '/../Models/PedidoModel.php';
require_once __DIR__ . '/../Models/ProductoModel.php';

$pedidoModel = new PedidoModel($pdo);
$productosActivos = (new ProductoModel($pdo))->listarActivos();   // id, nombre, precio

$mensaje = '';
$tipo_alerta = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'crear') {
        $cliente_nombre = trim($_POST['cliente_nombre'] ?? '');
        $cliente_tel = trim($_POST['cliente_telefono'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $fecha_entrega = $_POST['fecha_entrega'] ?? '';
        $total = (float)($_POST['total'] ?? 0);
        $abono_inicial = (float)($_POST['abono_inicial'] ?? 0);
        $nota = trim($_POST['nota'] ?? '');

        // Líneas del pedido: producto + cantidad (se suman si el mismo producto aparece dos veces)
        $nombresProducto = array_column($productosActivos, 'nombre', 'id');
        $porProducto = [];
        foreach (($_POST['item_producto'] ?? []) as $i => $pid) {
            $pid = (int)$pid;
            $cant = (int)($_POST['item_cantidad'][$i] ?? 0);
            if (isset($nombresProducto[$pid]) && $cant > 0) {
                $porProducto[$pid] = ($porProducto[$pid] ?? 0) + $cant;
            }
        }
        $items = array_map(fn($pid, $cant) => [$pid, $cant], array_keys($porProducto), $porProducto);

        // Sin descripción escrita, se arma con los productos ("20 Pan francés · 10 Pan dulce")
        if ($descripcion === '' && $items) {
            $descripcion = implode(' · ', array_map(fn($it) => $it[1] . ' ' . $nombresProducto[$it[0]], $items));
        }

        if ($cliente_nombre !== '' && $fecha_entrega !== '' && $items) {
            try {
                $pedidoModel->crear($cliente_nombre, $cliente_tel, $descripcion, $fecha_entrega, $total, $abono_inicial, $nota, $usuario_id, $items);
                header("Location: PedidosController.php?ok=1");
                exit;
            } catch (PDOException $e) {
                $mensaje = "No se pudo guardar el pedido. Revisa los datos e inténtalo de nuevo.";
                $tipo_alerta = 'danger';
            }
        } else {
            $mensaje = "Cliente, fecha de entrega y al menos un producto con cantidad son obligatorios.";
            $tipo_alerta = 'warning';
        }
    } elseif ($accion === 'cambiar_estado') {
        $pedido_id = (int)($_POST['pedido_id'] ?? 0);
        $nuevo_estado = $_POST['estado'] ?? '';
        $estados_validos = ['pendiente', 'en_produccion', 'listo', 'entregado', 'cancelado'];

        if ($pedido_id > 0 && in_array($nuevo_estado, $estados_validos, true)) {
            try {
                $cobrado = $pedidoModel->cambiarEstado($pedido_id, $nuevo_estado, ($_POST['cobrar_saldo'] ?? '') === '1');
                if ($nuevo_estado === 'entregado') {
                    $info = $pedidoModel->saldo($pedido_id);
                    $mensaje = "Pedido #{$pedido_id} entregado: venta registrada"
                        . ($cobrado > 0 ? " y saldo de $" . number_format($cobrado, 2) . " cobrado." : ".")
                        . ($info && $info['saldo'] > 0 ? " Quedó un saldo pendiente de $" . number_format($info['saldo'], 2) . "." : "");
                    $tipo_alerta = ($info && $info['saldo'] > 0) ? 'warning' : 'success';
                } else {
                    $mensaje = "Estado actualizado.";
                    $tipo_alerta = 'success';
                }
            } catch (Exception $e) {
                $mensaje = $e->getMessage();
                $tipo_alerta = 'danger';
            }
        }
    } elseif ($accion === 'abonar') {
        $pedido_id = (int)($_POST['pedido_id'] ?? 0);
        $monto = (float)($_POST['monto'] ?? 0);

        if ($pedido_id > 0 && $monto > 0) {
            $pedidoModel->abonar($pedido_id, $monto);
            $mensaje = "Abono de $" . number_format($monto, 2) . " registrado.";
            $tipo_alerta = 'success';
        }
    }
}

if (isset($_GET['ok'])) {
    $mensaje = "Pedido registrado correctamente.";
    $tipo_alerta = 'success';
}

$filtro = $_GET['filtro'] ?? 'activos';
$buscar = trim($_GET['buscar'] ?? '');
$mostrar_nuevo = isset($_GET['nuevo']);

$pedidos = $pedidoModel->listar($filtro, $buscar);

// Ventas por encargo de hoy (pedidos entregados hoy)
$ventasHoy = $pedidoModel->ventasDelDia(date('Y-m-d'));

// Pan disponible hoy y cobertura de los encargos de hoy (card superior)
$disponibilidad = $pedidoModel->disponibilidad();

$pagina_activa = 'pedidos';

require __DIR__ . '/../src/Views/Admin/pedidos.php';
