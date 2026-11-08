<?php
/**
 * Controlador de Recetas (Productos) - Panadería Don Leo
 * Ubicación: /panaderia/Controllers/RecetasController.php
 */

require_once __DIR__ . '/../Config/current_user.php';
require_once __DIR__ . '/../Models/ProductoModel.php';
require_once __DIR__ . '/../Models/InsumoModel.php';

$productoModel = new ProductoModel($pdo);
$insumoModel = new InsumoModel($pdo);

$mensaje = '';
$tipo_alerta = '';

// Activar / desactivar
if (isset($_GET['toggle'])) {
    $id_toggle = (int)$_GET['toggle'];
    if ($id_toggle > 0) {
        $productoModel->alternarActivo($id_toggle);
        header("Location: RecetasController.php" . (isset($_GET['inactivos']) ? '?inactivos=1' : ''));
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $nombre = trim($_POST['nombre'] ?? '');
    $precio = (float)($_POST['precio'] ?? 0);
    $base_insumo_id = (int)($_POST['base_insumo_id'] ?? 0);
    $rendimiento = (float)($_POST['rendimiento'] ?? 1);
    $ing_ids = $_POST['ing_id'] ?? [];
    $ing_cants = $_POST['ing_cant'] ?? [];

    if ($nombre !== '' && $base_insumo_id > 0 && $rendimiento > 0) {
        try {
            if ($accion === 'crear') {
                $productoModel->crear($nombre, $precio, $base_insumo_id, $rendimiento, $ing_ids, $ing_cants);
            } elseif ($accion === 'editar') {
                $producto_id = (int)($_POST['id'] ?? 0);
                $productoModel->actualizar($producto_id, $nombre, $precio, $base_insumo_id, $rendimiento, $ing_ids, $ing_cants);
            }

            header("Location: RecetasController.php?ok=" . ($accion === 'editar' ? 'editado' : 'creado'));
            exit;
        } catch (PDOException $e) {
            $mensaje = "Error al guardar la receta: " . $e->getMessage();
            $tipo_alerta = 'danger';
        }
    } else {
        $mensaje = "Nombre, materia base y rendimiento son obligatorios.";
        $tipo_alerta = 'warning';
    }
}

if (isset($_GET['ok'])) {
    $mensaje = $_GET['ok'] === 'editado' ? 'Receta actualizada correctamente.' : 'Producto y receta creados correctamente.';
    $tipo_alerta = 'success';
}

$verInactivos = isset($_GET['inactivos']);
$mostrarNuevo = isset($_GET['nuevo']);
$editarId = (int)($_GET['editar'] ?? 0);

$insumosActivos = $insumoModel->listarActivos();

$productoEditar = null;
$itemsEditar = [];
if ($editarId > 0) {
    $productoEditar = $productoModel->obtener($editarId);
    if ($productoEditar) {
        $itemsEditar = $productoModel->itemsDeProducto($editarId);
    }
}

$productos = $productoModel->listarConBase($verInactivos);

$itemsPorProducto = [];
foreach ($productoModel->itemsDeTodasLasRecetas() as $ri) {
    $itemsPorProducto[(int)$ri['producto_id']][] = $ri;
}

$pagina_activa = 'recetas';

require __DIR__ . '/../src/Views/Admin/recetas.php';
