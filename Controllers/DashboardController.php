<?php
/**
 * Controlador del Menú principal - Panadería Don Leo
 * Ubicación: /panaderia/Controllers/DashboardController.php
 */

require_once __DIR__ . '/../Config/current_user.php';

$pagina_activa = 'menu';

require __DIR__ . '/../src/Views/Admin/dashboard.php';
