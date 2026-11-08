<?php
/**
 * Controlador de Arqueo Diario - Panadería Don Leo
 * Ubicación: /panaderia/Controllers/ArqueoController.php
 */

require_once __DIR__ . '/../Config/current_user.php';
require_once __DIR__ . '/../Models/ArqueoModel.php';
require_once __DIR__ . '/../Models/ProductoModel.php';

$arqueoModel = new ArqueoModel($pdo);
$productoModel = new ProductoModel($pdo);

$mensaje = '';
$tipo_alerta = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fecha = $_POST['fecha_arqueo'] ?? date('Y-m-d');
    $vendidas = $_POST['vendidas'] ?? [];
    $sobrante = $_POST['sobrante'] ?? [];
    $dinero_contado = (isset($_POST['dinero_contado']) && $_POST['dinero_contado'] !== '') ? (float)$_POST['dinero_contado'] : null;
    $observaciones = trim($_POST['observaciones'] ?? '');

    try {
        $arqueoModel->guardar($fecha, $vendidas, $sobrante, $dinero_contado, $observaciones);
        header("Location: ArqueoController.php?fecha=" . urlencode($fecha) . "&ok=1");
        exit;
    } catch (PDOException $e) {
        $mensaje = "Error al guardar el arqueo: " . $e->getMessage();
        $tipo_alerta = 'danger';
    }
}

if (isset($_GET['ok'])) {
    $mensaje = "Arqueo guardado correctamente.";
    $tipo_alerta = 'success';
}

$fecha_sel = $_GET['fecha'] ?? date('Y-m-d');
$fecha_ayer = date('Y-m-d', strtotime($fecha_sel . ' -1 day'));

$productos     = $productoModel->listarActivos();
$sobranteAyer  = $arqueoModel->sobrantesDe($fecha_ayer);
$horneadasHoy  = $arqueoModel->horneadasDe($fecha_sel);
$detalleHoy    = $arqueoModel->detalleDe($fecha_sel);
$cajaHoy       = $arqueoModel->cajaDe($fecha_sel);
$historial     = $arqueoModel->historial(15);

$pagina_activa = 'arqueo';

require __DIR__ . '/../src/Views/Admin/arqueo.php';
