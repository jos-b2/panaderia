<?php
/**
 * Vista: Reportes - Panadería Don Leo
 * Ubicación: /panaderia/src/Views/Admin/reportes.php
 * La prepara Controllers/ReportesController.php
 */
// Si se abre esta vista directamente (dirección vieja), ir a su controlador
if (realpath($_SERVER["SCRIPT_FILENAME"]) === __FILE__) {
    header("Location: /panaderia/Controllers/ReportesController.php");
    exit;
}

include_once __DIR__ . '/../Layouts/header.php';

$hoy = date('Y-m-d');
$atajos = [
    '7 días'     => [date('Y-m-d', strtotime('-6 days')), $hoy],
    '30 días'    => [date('Y-m-d', strtotime('-29 days')), $hoy],
    'Este mes'   => [date('Y-m-01'), $hoy],
    'Mes pasado' => [date('Y-m-01', strtotime('first day of last month')), date('Y-m-t', strtotime('last day of last month'))],
];
$dinero = fn($n) => '$' . number_format((float)$n, 2);
?>

<link rel="stylesheet" href="/panaderia/Assets/css/siderbar.css?v=4">
<link rel="stylesheet" href="/panaderia/Assets/css/reportes.css?v=2">

<div class="d-flex">
    <?php include_once __DIR__ . '/../Layouts/sidebar.php'; ?>

    <div class="main">
        <div class="page-head rep-header-section">
            <div class="d-flex justify-content-between align-items-baseline flex-wrap gap-2">
                <div>
                    <h2 class="fw-bold page-title">Reportes</h2>
                    <p class="mb-0">Producción, ventas, pedidos, inventario y caja del periodo elegido.</p>
                </div>
                <div class="text-muted small"><?= htmlspecialchars($periodoTexto) ?></div>
            </div>
        </div>

        <?php if ($mensaje !== ''): ?>
            <div class="alert alert-<?= $tipo_alerta ?> alert-dismissible fade show" role="alert">
                <i class="ph-duotone ph-warning me-1"></i><?= htmlspecialchars($mensaje) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        <?php endif; ?>

        <!-- Filtro de fechas + exportar -->
        <div class="rep-toolbar">
            <form method="get" action="ReportesController.php" class="rep-range">
                <div class="rep-field">
                    <label class="form-label" for="repDesde">Desde</label>
                    <input type="date" id="repDesde" name="desde" class="form-control" value="<?= htmlspecialchars($desde) ?>" max="<?= $hoy ?>">
                </div>
                <div class="rep-field">
                    <label class="form-label" for="repHasta">Hasta</label>
                    <input type="date" id="repHasta" name="hasta" class="form-control" value="<?= htmlspecialchars($hasta) ?>" max="<?= $hoy ?>">
                </div>
                <button type="submit" class="btn btn-brand"><i class="ph-duotone ph-funnel"></i>Aplicar</button>

                <div class="rep-presets">
                    <?php foreach ($atajos as $texto => [$d, $h]): ?>
                        <a href="ReportesController.php?desde=<?= $d ?>&hasta=<?= $h ?>" class="<?= ($d === $desde && $h === $hasta) ? 'active' : '' ?>"><?= $texto ?></a>
                    <?php endforeach; ?>
                </div>
            </form>

            <div class="rep-export">
                <button type="button" class="btn btn-outline-secondary" id="btnExportarPdf" <?= $exportarDisponible ? '' : 'data-sin-vendor="1"' ?>>
                    <i class="ph-duotone ph-file-pdf"></i>Exportar PDF
                </button>
                <a href="ReportesController.php?exportar=excel&desde=<?= $desde ?>&hasta=<?= $hasta ?>" class="btn btn-outline-secondary" id="btnExportarExcel" <?= $exportarDisponible ? 'download' : '' ?>>
                    <i class="ph-duotone ph-microsoft-excel-logo"></i>Exportar Excel
                </a>
            </div>
        </div>

        <!-- Formulario oculto para el PDF: lleva las gráficas como imágenes -->
        <form method="post" action="ReportesController.php" id="formPdf" data-no-transicion>
            <input type="hidden" name="accion" value="pdf">
            <input type="hidden" name="desde" value="<?= htmlspecialchars($desde) ?>">
            <input type="hidden" name="hasta" value="<?= htmlspecialchars($hasta) ?>">
            <div id="pdfImagenes"></div>
        </form>

        <!-- Resumen del periodo -->
        <div class="rep-summary">
            <div><small>Piezas horneadas</small><strong><?= number_format($totales['horneadas']) ?></strong></div>
            <div><small>Piezas vendidas</small><strong><?= number_format($totales['vendidas']) ?></strong></div>
            <div><small>Ingresos</small><strong><?= $dinero($totales['ingresos']) ?></strong></div>
            <div><small>Pedidos</small><strong><?= number_format($totales['pedidos']) ?></strong></div>
            <div><small>Inventario hoy</small><strong><?= $dinero($totales['inventario']) ?></strong></div>
        </div>

        <!-- Gráficas -->
        <div class="rep-grid">
            <section class="rep-card rep-wide">
                <header class="rep-card-head">
                    <div>
                        <h3 class="rep-card-title"><i class="ph-duotone ph-chart-bar"></i>Producción vs. ventas</h3>
                        <small>Piezas horneadas y vendidas por día</small>
                    </div>
                    <span class="rep-total"><?= number_format($totales['horneadas']) ?> / <?= number_format($totales['vendidas']) ?> pzs</span>
                </header>
                <div class="rep-chart rep-chart-lg"><canvas id="chartProduccion"></canvas></div>
            </section>

            <section class="rep-card rep-wide">
                <header class="rep-card-head">
                    <div>
                        <h3 class="rep-card-title"><i class="ph-duotone ph-coins"></i>Ingresos por día</h3>
                        <small>Ventas del arqueo y abonos de pedidos</small>
                    </div>
                    <span class="rep-total"><?= $dinero($totales['ingresos']) ?></span>
                </header>
                <div class="rep-chart rep-chart-lg"><canvas id="chartIngresos"></canvas></div>
            </section>

            <section class="rep-card">
                <header class="rep-card-head">
                    <div>
                        <h3 class="rep-card-title"><i class="ph-duotone ph-bread"></i>Producción por producto</h3>
                        <small>Participación de cada pan en lo horneado</small>
                    </div>
                </header>
                <div class="rep-chart"><canvas id="chartProductos"></canvas></div>
            </section>

            <section class="rep-card">
                <header class="rep-card-head">
                    <div>
                        <h3 class="rep-card-title"><i class="ph-duotone ph-clipboard-text"></i>Pedidos por estado</h3>
                        <small>Encargos creados en el periodo</small>
                    </div>
                    <span class="rep-total"><?= number_format($totales['pedidos']) ?> pedidos</span>
                </header>
                <div class="rep-chart"><canvas id="chartPedidos"></canvas></div>
            </section>

            <section class="rep-card">
                <header class="rep-card-head">
                    <div>
                        <h3 class="rep-card-title"><i class="ph-duotone ph-package"></i>Inventario actual</h3>
                        <small>Existencia frente al mínimo de cada insumo</small>
                    </div>
                </header>
                <div class="rep-chart"><canvas id="chartInventario"></canvas></div>
            </section>

            <section class="rep-card">
                <header class="rep-card-head">
                    <div>
                        <h3 class="rep-card-title"><i class="ph-duotone ph-cash-register"></i>Arqueo de caja</h3>
                        <small>Ingreso esperado frente al dinero contado</small>
                    </div>
                    <span class="rep-total <?= $totales['descuadre'] < 0 ? 'is-neg' : '' ?>"><?= $dinero($totales['descuadre']) ?></span>
                </header>
                <div class="rep-chart"><canvas id="chartCaja"></canvas></div>
            </section>
        </div>

        <?php include_once __DIR__ . '/../Layouts/footer.php'; ?>
    </div>
</div>

<!-- Datos para las gráficas -->
<script id="datosReporte" type="application/json"><?= json_encode($datos, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="/panaderia/Assets/js/graficas.js?v=1"></script>
<script src="/panaderia/Assets/js/reportes.js?v=3"></script>
