<?php
/**
 * Vista: Panel - Panadería Don Leo
 * Ubicación: /panaderia/src/Views/Admin/panel.php
 * La prepara Controllers/PanelController.php
 */
// Si se abre esta vista directamente (dirección vieja), ir a su controlador
if (realpath($_SERVER["SCRIPT_FILENAME"]) === __FILE__) {
    header("Location: /panaderia/Controllers/PanelController.php");
    exit;
}

include_once __DIR__ . '/../Layouts/header.php';
?>

<link rel="stylesheet" href="/panaderia/Assets/css/siderbar.css">
<link rel="stylesheet" href="/panaderia/Assets/css/panel.css">

<div class="d-flex">
    <?php include_once __DIR__ . '/../Layouts/sidebar.php'; ?>

    <div class="main flex-grow-1 p-4">
        <div class="panel-header-section mb-4">
            <div class="d-flex justify-content-between align-items-baseline flex-wrap gap-2">
                <div>
                    <h2 class="fw-bold" style="color: #4A2F20; font-family: Georgia, serif; font-size: 28px; margin-bottom: 4px;">Panel</h2>
                    <p class="text-muted mb-0" style="font-size: 14.5px;">
                        Hola, <strong style="color: #4A2F20;"><?php echo htmlspecialchars($nombreUsuario ?? 'Administrador'); ?></strong>. Este es el resumen del negocio.
                    </p>
                </div>
                <div class="text-muted small">
                    <?php 
                        $meses = ["", "ene", "feb", "mar", "abr", "may", "jun", "jul", "ago", "sep", "oct", "nov", "dic"];
                        echo date('j') . ' ' . $meses[(int)date('n')] . ' ' . date('Y');
                    ?>
                </div>
            </div>
        </div>

        <div class="panel-stats-grid mb-4">
            <div class="panel-stat-card">
                <small class="panel-stat-label">Horneadas hoy</small>
                <b class="panel-stat-val"><?php echo number_format($horHoy); ?></b>
                <span class="panel-stat-desc">piezas producidas</span>
            </div>

            <div class="panel-stat-card">
                <small class="panel-stat-label">Ventas de hoy</small>
                <b class="panel-stat-val">$<?php echo number_format($ventasHoy, 2); ?></b>
                <span class="panel-stat-desc">arqueo + abonos de hoy</span>
            </div>

            <div class="panel-stat-card panel-stat-blue">
                <small class="panel-stat-label">Pedidos activos</small>
                <b class="panel-stat-val"><?php echo $activos; ?></b>
                <span class="panel-stat-desc">por entregar</span>
            </div>

            <div class="panel-stat-card panel-stat-green">
                <small class="panel-stat-label">Insumos en alerta</small>
                <b class="panel-stat-val"><?php echo count($bajos); ?></b>
                <span class="panel-stat-desc">bajo o en el mínimo</span>
            </div>
        </div>

        <div class="panel-tabs mb-4 d-flex gap-2">
            <button class="panel-tab-btn active" data-target="tab-produccion">Producción vs Ventas</button>
            <button class="panel-tab-btn" data-target="tab-entregas">Próximas Entregas</button>
            <button class="panel-tab-btn" data-target="tab-alertas">Alertas de Insumos</button>
        </div>

        <div class="panel-tab-content active" id="tab-produccion">
            <div class="panel-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="panel-card-title mb-0">Producción vs. ventas</h3>
                    <div class="d-flex align-items-center gap-3" style="font-size: 12.5px; color: #8A7565;">
                        <div class="d-flex align-items-center gap-1.5 panel-legend-item active" data-series="horneadas" style="cursor: pointer; user-select: none;">
                            <span style="width: 10px; height: 10px; background: #C98A3B; border-radius: 50%; display: inline-block;"></span>
                            Horneadas
                        </div>
                        <div class="d-flex align-items-center gap-1.5 panel-legend-item active" data-series="vendidas" style="cursor: pointer; user-select: none;">
                            <span style="width: 10px; height: 10px; background: #4A2F20; border-radius: 50%; display: inline-block;"></span>
                            Vendidas
                        </div>
                    </div>
                </div>

                <div class="panel-chart-container my-4">
                    <div class="panel-chart-bars">
                        <?php foreach ($chartDias as $d): ?>
                            <?php
                                $hPct = $d['horneadas'] > 0 ? max(8, round(($d['horneadas'] / $maxVal) * 100)) : 4;
                                $vPct = $d['vendidas'] > 0 ? max(8, round(($d['vendidas'] / $maxVal) * 100)) : 4;
                            ?>
                            <div class="panel-day-col" title="<?= $d['label'] ?>: <?= $d['horneadas'] ?> horneadas / <?= $d['vendidas'] ?> vendidas">
                                <div class="panel-bars-group">
                                    <i class="bar-h" style="height: <?= $hPct ?>%;"></i>
                                    <i class="bar-v" style="height: <?= $vPct ?>%;"></i>
                                </div>
                                <span class="panel-day-label"><?= $d['label'] ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="text-muted" style="font-size: 13px;">
                    <?= $hayDatosSemana ? 'Resumen de los últimos 7 días.' : 'Aún no hay arqueos registrados esta semana.' ?>
                </div>
            </div>
        </div>

        <div class="panel-tab-content" id="tab-entregas">
            <div class="panel-card h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="panel-card-title mb-0">Próximas entregas</h3>
                    <a href="PedidosController.php" class="btn btn-sm panel-btn-outline">Ver todos</a>
                </div>

                <?php if (empty($proximos)): ?>
                    <div class="text-muted text-center py-5" style="font-size: 14px;">
                        No hay pedidos pendientes.
                    </div>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($proximos as $p): ?>
                            <?php $saldo = $p['total'] - $p['abonado']; ?>
                            <li class="list-group-item bg-transparent d-flex justify-content-between align-items-center px-0 py-2">
                                <div>
                                    <strong style="color: #4A2F20;"><?php echo htmlspecialchars($p['cliente']); ?></strong>
                                    <div class="small text-muted"><?php echo htmlspecialchars(mb_strimwidth($p['descripcion'], 0, 40, '…')); ?></div>
                                </div>
                                <span class="badge bg-light text-dark border">Saldo: $<?php echo number_format($saldo, 2); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <div class="panel-tab-content" id="tab-alertas">
            <div class="panel-card mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="panel-card-title mb-0">Alertas de materia prima</h3>
                    <a href="InsumosController.php" class="btn btn-sm panel-btn-outline">Ir a inventario</a>
                </div>

                <?php if (empty($bajos)): ?>
                    <div class="text-muted text-center py-4" style="font-size: 14.5px;">
                        Todo el inventario está por encima del mínimo. 👌
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="color: #8A7565; font-size: 11.5px; text-transform: uppercase;">Insumo</th>
                                    <th class="text-end" style="color: #8A7565; font-size: 11.5px; text-transform: uppercase;">Stock</th>
                                    <th class="text-center" style="color: #8A7565; font-size: 11.5px; text-transform: uppercase;">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bajos as $i): ?>
                                    <tr>
                                        <td style="color: #4A2F20; font-weight: 600;"><?php echo htmlspecialchars($i['nombre']); ?></td>
                                        <td class="text-end"><?php echo number_format($i['stock'], 2); ?> <?php echo htmlspecialchars($i['unidad']); ?></td>
                                        <td class="text-center">
                                            <span class="badge <?php echo ($i['stock'] <= 0) ? 'bg-danger' : 'bg-warning text-dark'; ?>">
                                                <?php echo ($i['stock'] <= 0) ? 'Agotado' : 'Comprar'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php include_once __DIR__ . '/../Layouts/footer.php'; ?>
    </div>
</div>

<script src="/panaderia/Assets/js/dashboard.js"></script>
<script src="/panaderia/Assets/js/panel.js"></script>
