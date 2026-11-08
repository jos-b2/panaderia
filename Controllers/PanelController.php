<?php
/**
 * Controlador del Panel - Panadería Don Leo
 * Ubicación: /panaderia/Controllers/PanelController.php
 */

require_once __DIR__ . '/../Config/current_user.php';
require_once __DIR__ . '/../Models/PanelModel.php';

$panelModel = new PanelModel($pdo);

$horHoy       = $panelModel->getHorneadasHoy();
$ventasArqueo = $panelModel->getVentasArqueoHoy();
$abonosHoy    = $panelModel->getAbonosHoy();
$ventasHoy    = $ventasArqueo + $abonosHoy;
$activos      = $panelModel->getPedidosActivosCount();
$bajos        = $panelModel->getInsumosEnAlerta();
$proximos     = $panelModel->getProximosPedidos(6);

// Datos de la gráfica de los últimos 7 días
$prodSemana        = $panelModel->getProduccionSemana();
$ventArqueoSemana  = $panelModel->getVentasArqueoSemana();
$ventPedidosSemana = $panelModel->getPedidosSemana();

$dias_es = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
$chartDias = [];
$maxVal = 1;
$hayDatosSemana = !empty($ventArqueoSemana) || !empty($ventPedidosSemana) || !empty($prodSemana);

for ($i = 6; $i >= 0; $i--) {
    $ts = strtotime("-{$i} days");
    $fechaStr = date('Y-m-d', $ts);
    $h = (int)($prodSemana[$fechaStr] ?? 0);
    $v = (int)($ventArqueoSemana[$fechaStr] ?? 0) + (int)($ventPedidosSemana[$fechaStr] ?? 0);

    if ($h > $maxVal) $maxVal = $h;
    if ($v > $maxVal) $maxVal = $v;

    $chartDias[] = [
        'label' => $dias_es[(int)date('w', $ts)] . ' ' . date('j', $ts),
        'horneadas' => $h,
        'vendidas' => $v
    ];
}

$pagina_activa = 'panel';

require __DIR__ . '/../src/Views/Admin/panel.php';
