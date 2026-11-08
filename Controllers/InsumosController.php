<?php
/**
 * Controlador de Materia Prima (Insumos) - Panadería Don Leo
 * Ubicación: /panaderia/Controllers/InsumosController.php
 */

require_once __DIR__ . '/../Config/current_user.php';
require_once __DIR__ . '/../Models/InsumoModel.php';

$insumoModel = new InsumoModel($pdo);

$mensaje = '';
$tipo_alerta = '';

// Activar / desactivar
if (isset($_GET['toggle'])) {
    $id_toggle = (int)$_GET['toggle'];
    if ($id_toggle > 0) {
        $insumoModel->alternarActivo($id_toggle);
        header("Location: InsumosController.php" . (isset($_GET['inactivos']) ? '?inactivos=1' : ''));
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'crear') {
        $nombre = trim($_POST['nombre'] ?? '');
        $unidad = trim($_POST['unidad'] ?? 'lb');
        $stock = (float)($_POST['stock'] ?? 0);
        $stock_minimo = (float)($_POST['stock_minimo'] ?? 0);
        $costo = (float)($_POST['costo'] ?? 0);

        if ($nombre !== '' && $unidad !== '') {
            try {
                if ($insumoModel->existeNombre($nombre)) {
                    $mensaje = "Ya existe un insumo con el nombre '{$nombre}'.";
                    $tipo_alerta = 'danger';
                } else {
                    $insumoModel->crear($nombre, $unidad, $stock, $stock_minimo, $costo);
                    header("Location: InsumosController.php?ok=creado");
                    exit;
                }
            } catch (PDOException $e) {
                $mensaje = "Error al crear insumo: " . $e->getMessage();
                $tipo_alerta = 'danger';
            }
        } else {
            $mensaje = "Nombre y unidad son obligatorios.";
            $tipo_alerta = 'warning';
        }
    } elseif ($accion === 'editar') {
        $id = (int)($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $unidad = trim($_POST['unidad'] ?? 'lb');
        $stock_minimo = (float)($_POST['stock_minimo'] ?? 0);
        $costo = (float)($_POST['costo'] ?? 0);

        if ($id > 0 && $nombre !== '' && $unidad !== '') {
            try {
                $insumoModel->actualizar($id, $nombre, $unidad, $stock_minimo, $costo);
                header("Location: InsumosController.php?ok=editado");
                exit;
            } catch (PDOException $e) {
                $mensaje = "Error al actualizar insumo: " . $e->getMessage();
                $tipo_alerta = 'danger';
            }
        }
    } elseif ($accion === 'movimiento') {
        $insumo_id = (int)($_POST['insumo_id'] ?? 0);
        $tipo = $_POST['tipo'] ?? 'entrada';
        $cantidad = (float)($_POST['cantidad'] ?? 0);
        $nota = trim($_POST['nota'] ?? '');

        if ($insumo_id > 0 && $cantidad > 0 && in_array($tipo, ['entrada', 'salida', 'ajuste'], true)) {
            try {
                $insumoModel->registrarMovimiento($insumo_id, $tipo, $cantidad, $nota, $usuario_id);
                header("Location: InsumosController.php?ok=movimiento");
                exit;
            } catch (PDOException $e) {
                $mensaje = "Error al registrar movimiento: " . $e->getMessage();
                $tipo_alerta = 'danger';
            }
        } else {
            $mensaje = "Ingresa una cantidad válida mayor a 0.";
            $tipo_alerta = 'warning';
        }
    }
}

if (isset($_GET['ok'])) {
    $mensajes_ok = [
        'creado' => 'Insumo creado correctamente.',
        'editado' => 'Insumo actualizado correctamente.',
        'movimiento' => 'Movimiento registrado y stock actualizado.'
    ];
    $mensaje = $mensajes_ok[$_GET['ok']] ?? 'Operación realizada.';
    $tipo_alerta = 'success';
}

$verInactivos = isset($_GET['inactivos']);
$mostrarNuevo = isset($_GET['nuevo']);
$editarId = (int)($_GET['editar'] ?? 0);
$movimientoId = (int)($_GET['movimiento'] ?? 0);

$insumoEditar = $editarId > 0 ? $insumoModel->obtener($editarId) : null;
$insumoMovimiento = $movimientoId > 0 ? $insumoModel->obtener($movimientoId) : null;

$valorInventario = $insumoModel->valorInventario();
$listaInsumos = $insumoModel->listar($verInactivos);
$listaMovimientos = $insumoModel->ultimosMovimientos(30);

$pagina_activa = 'insumos';

require __DIR__ . '/../src/Views/Admin/insumos.php';
