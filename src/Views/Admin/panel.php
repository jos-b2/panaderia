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

<link rel="stylesheet" href="/panaderia/Assets/css/siderbar.css?v=4">
<link rel="stylesheet" href="/panaderia/Assets/css/panel.css?v=3">

<div class="d-flex">
    <?php include_once __DIR__ . '/../Layouts/sidebar.php'; ?>

    <div class="main">
        <div class="page-head panel-header-section mb-4">
            <div class="d-flex justify-content-between align-items-baseline flex-wrap gap-2">
                <div>
                    <h2 class="fw-bold page-title">Panel</h2>
                    <p class="text-muted mb-0">
                        Hola, <strong><?php echo htmlspecialchars($nombreUsuario ?? 'Administrador'); ?></strong>. Este es el resumen del negocio.
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
                <small class="panel-stat-label"><i class="ph-duotone ph-fire"></i>Horneadas hoy</small>
                <b class="panel-stat-val"><?php echo number_format($horHoy); ?></b>
                <span class="panel-stat-desc">piezas producidas</span>
            </div>

            <div class="panel-stat-card">
                <small class="panel-stat-label"><i class="ph-duotone ph-coins"></i>Ventas de hoy</small>
                <b class="panel-stat-val">$<?php echo number_format($ventasHoy, 2); ?></b>
                <span class="panel-stat-desc">arqueo + abonos de hoy</span>
            </div>

            <div class="panel-stat-card panel-stat-blue">
                <small class="panel-stat-label"><i class="ph-duotone ph-clipboard-text"></i>Pedidos activos</small>
                <b class="panel-stat-val"><?php echo $activos; ?></b>
                <span class="panel-stat-desc">por entregar</span>
            </div>

            <div class="panel-stat-card panel-stat-green">
                <small class="panel-stat-label"><i class="ph-duotone ph-warning"></i>Insumos en alerta</small>
                <b class="panel-stat-val"><?php echo count($bajos); ?></b>
                <span class="panel-stat-desc">bajo o en el mínimo</span>
            </div>
        </div>

        <div class="panel-tabs mb-4 d-flex gap-2">
            <button class="panel-tab-btn active" data-target="tab-produccion"><i class="ph-duotone ph-chart-bar"></i>Producción vs Ventas</button>
            <button class="panel-tab-btn" data-target="tab-entregas"><i class="ph-duotone ph-truck"></i>Próximas Entregas</button>
            <button class="panel-tab-btn" data-target="tab-alertas"><i class="ph-duotone ph-bell-ringing"></i>Alertas de Insumos</button>
        </div>

        <div class="panel-tab-content active" id="tab-produccion">
            <div class="panel-card">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                    <div>
                        <h3 class="panel-card-title mb-1">Producción vs. ventas</h3>
                        <small class="text-muted">Piezas horneadas y vendidas en los últimos 7 días</small>
                    </div>
                    <span class="panel-week-total">
                        <?= number_format($totalSemana['horneadas']) ?> horneadas · <?= number_format($totalSemana['vendidas']) ?> vendidas
                    </span>
                </div>

                <!-- Gráfica Chart.js (la dibuja panel.js con estos datos) -->
                <div class="panel-chart"><canvas id="chartPanel" aria-label="Gráfica de producción vs. ventas"></canvas></div>
                <script id="datosPanel" type="application/json"><?= json_encode($chartDias, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?></script>

                <div class="text-muted mt-3">
                    <?= $hayDatosSemana ? 'Resumen de los últimos 7 días.' : 'Aún no hay arqueos registrados esta semana.' ?>
                </div>
            </div>
        </div>

        <div class="panel-tab-content" id="tab-entregas">
            <div class="panel-card h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="panel-card-title mb-0">Próximas entregas</h3>
                    <a href="PedidosController.php" class="btn btn-sm panel-btn-outline">Ver todos<i class="ph ph-arrow-right"></i></a>
                </div>

                <?php if (empty($proximos)): ?>
                    <div class="text-muted text-center py-5">
                        <span class="empty-state"><i class="ph-duotone ph-coffee"></i>No hay pedidos pendientes.</span>
                    </div>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($proximos as $p): ?>
                            <?php $saldo = $p['total'] - $p['abonado']; ?>
                            <li class="list-group-item bg-transparent d-flex justify-content-between align-items-center px-0 py-2">
                                <div>
                                    <strong><?php echo htmlspecialchars($p['cliente']); ?></strong>
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
                    <a href="InsumosController.php" class="btn btn-sm panel-btn-outline">Ir a inventario<i class="ph ph-arrow-right"></i></a>
                </div>

                <?php if (empty($bajos)): ?>
                    <div class="text-muted text-center py-4">
                        <span class="empty-state"><i class="ph-duotone ph-seal-check"></i>Todo el inventario está por encima del mínimo.</span>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Insumo</th>
                                    <th class="text-end">Stock</th>
                                    <th class="text-center">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bajos as $i): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($i['nombre']); ?></td>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="/panaderia/Assets/js/graficas.js?v=1"></script>
<script src="/panaderia/Assets/js/panel.js?v=2"></script>
