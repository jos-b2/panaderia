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

// Datos de la gráfica de los últimos 7 días (en piezas: horneadas vs. vendidas en el arqueo)
$prodSemana       = $panelModel->getProduccionSemana();
$ventArqueoSemana = $panelModel->getVentasArqueoSemana();
$entregadasSemana = $panelModel->getEntregadasSemana();   // pedidos entregados (también son ventas)

// Una fila por día para la gráfica de Chart.js (panel.js)
$dias_es = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
$chartDias = [];
$hayDatosSemana = !empty($ventArqueoSemana) || !empty($prodSemana) || !empty($entregadasSemana);

for ($i = 6; $i >= 0; $i--) {
    $ts = strtotime("-{$i} days");
    $fechaStr = date('Y-m-d', $ts);

    $chartDias[] = [
        'label'     => $dias_es[(int)date('w', $ts)] . ' ' . date('j', $ts),
        'horneadas' => (int)($prodSemana[$fechaStr] ?? 0),
        'vendidas'  => (int)($ventArqueoSemana[$fechaStr] ?? 0) + (int)($entregadasSemana[$fechaStr] ?? 0),
    ];
}

$totalSemana = [
    'horneadas' => array_sum(array_column($chartDias, 'horneadas')),
    'vendidas'  => array_sum(array_column($chartDias, 'vendidas')),
];

$pagina_activa = 'panel';

require __DIR__ . '/../src/Views/Admin/panel.php';
