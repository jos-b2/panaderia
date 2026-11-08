<?php
/**
 * Controlador de Respaldos - Panadería Don Leo
 * Ubicación: /panaderia/Controllers/RespaldoController.php
 */

require_once __DIR__ . '/../Config/current_user.php';
require_once __DIR__ . '/../Models/RespaldoModel.php';

// Solo administradores (se verifica antes de crear o descargar respaldos)
soloAdministrador();

$respaldoModel = new RespaldoModel($pdo, __DIR__ . '/../backups');

$mensaje = '';
$tipo_alerta = '';

// Descargar un respaldo
if (isset($_GET['descargar'])) {
    $ruta = $respaldoModel->rutaDe($_GET['descargar']);

    if ($ruta) {
        $archivo = basename($ruta);
        header('Content-Description: File Transfer');
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $archivo . '"');
        header('Content-Length: ' . filesize($ruta));
        readfile($ruta);
        exit;
    } else {
        $mensaje = "El archivo de respaldo no existe.";
        $tipo_alerta = 'danger';
    }
}

// Crear un respaldo nuevo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $respaldoModel->generar();
        header("Location: RespaldoController.php?ok=1");
        exit;
    } catch (PDOException $e) {
        $mensaje = "Error al crear respaldo: " . $e->getMessage();
        $tipo_alerta = 'danger';
    }
}

if (isset($_GET['ok'])) {
    $mensaje = "Respaldo creado correctamente.";
    $tipo_alerta = 'success';
}

$listaRespaldos = $respaldoModel->listar();

$pagina_activa = 'respaldo';

require __DIR__ . '/../src/Views/Admin/respaldo.php';
