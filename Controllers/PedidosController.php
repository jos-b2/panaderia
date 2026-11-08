<?php
/**
 * Controlador de Pedidos y Abonos - Panadería Don Leo
 * Ubicación: /panaderia/Controllers/PedidosController.php
 */

require_once __DIR__ . '/../Config/current_user.php';
require_once __DIR__ . '/../Models/PedidoModel.php';

$pedidoModel = new PedidoModel($pdo);

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

        if ($cliente_nombre !== '' && $descripcion !== '' && $fecha_entrega !== '') {
            try {
                $pedidoModel->crear($cliente_nombre, $cliente_tel, $descripcion, $fecha_entrega, $total, $abono_inicial, $nota, $usuario_id);
                header("Location: PedidosController.php?ok=1");
                exit;
            } catch (PDOException $e) {
                $mensaje = "Error al guardar el pedido: " . $e->getMessage();
                $tipo_alerta = 'danger';
            }
        } else {
            $mensaje = "Cliente, descripción y fecha de entrega son obligatorios.";
            $tipo_alerta = 'warning';
        }
    } elseif ($accion === 'cambiar_estado') {
        $pedido_id = (int)($_POST['pedido_id'] ?? 0);
        $nuevo_estado = $_POST['estado'] ?? '';
        $estados_validos = ['pendiente', 'en_produccion', 'listo', 'entregado', 'cancelado'];

        if ($pedido_id > 0 && in_array($nuevo_estado, $estados_validos, true)) {
            $pedidoModel->cambiarEstado($pedido_id, $nuevo_estado);
            $mensaje = "Estado actualizado.";
            $tipo_alerta = 'success';
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

$pagina_activa = 'pedidos';

require __DIR__ . '/../src/Views/Admin/pedidos.php';
