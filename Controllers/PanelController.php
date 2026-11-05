<?php
/**
 * Controlador del Panel - Panadería Don Leo
 * Ubicación: /panaderia/Controllers/PanelController.php
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Carga centralizada absoluta y segura del usuario y sesión
require_once $_SERVER['DOCUMENT_ROOT'] . '/panaderia/config/current_user.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/panaderia/src/Models/PanelModel.php';

$panelModel = new PanelModel($pdo);

// Obtener datos del negocio a través del Modelo
$hoy = date('Y-m-d');
$horHoy    = $panelModel->getHorneadasHoy($hoy);
$ventasHoy = $panelModel->getVentasHoy($hoy);
$activos   = $panelModel->getPedidosActivosCount();
$bajos     = $panelModel->getInsumosEnAlerta();
$proximos  = $panelModel->getProximosPedidos(6);

$pagina_activa = 'panel';

// Cargar la vista del panel usando ruta absoluta
require_once $_SERVER['DOCUMENT_ROOT'] . '/panaderia/src/Views/Admin/panel.php';