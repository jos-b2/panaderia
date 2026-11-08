<?php
/**
 * Vista: Arqueo diario - Panadería Don Leo
 * Ubicación: /panaderia/src/Views/Admin/arqueo.php
 * La prepara Controllers/ArqueoController.php
 */
// Si se abre esta vista directamente (dirección vieja), ir a su controlador
if (realpath($_SERVER["SCRIPT_FILENAME"]) === __FILE__) {
    header("Location: /panaderia/Controllers/ArqueoController.php");
    exit;
}

include_once __DIR__ . '/../Layouts/header.php';
?>

<link rel="stylesheet" href="/panaderia/Assets/css/siderbar.css?v=4">
<link rel="stylesheet" href="/panaderia/Assets/css/arqueo.css?v=2">

<div class="d-flex">
    <?php include_once __DIR__ . '/../Layouts/sidebar.php'; ?>

    <div class="main">
        <div class="page-head arq-header-section mb-4">
            <div class="d-flex justify-content-between align-items-baseline flex-wrap gap-2">
                <h2 class="fw-bold page-title">Arqueo diario</h2>
                <div class="text-muted small">
                    <?php 
                        $meses = ["", "ene", "feb", "mar", "abr", "may", "jun", "jul", "ago", "sep", "oct", "nov", "dic"];
                        echo date('j') . ' ' . $meses[(int)date('n')] . ' ' . date('Y');
                    ?>
                </div>
            </div>
        </div>

        <?php if ($mensaje !== ''): ?>
            <div class="alert alert-<?= $tipo_alerta ?> alert-dismissible fade show py-2 px-3 mb-3" role="alert">
                <?= htmlspecialchars($mensaje) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        <?php endif; ?>

        <div class="arq-tabs mb-4 d-flex gap-2">
            <button type="button" class="arq-tab-btn active" data-target="tab-cuadre"><i class="ph-duotone ph-list-checks"></i>Cuadre de Productos</button>
            <button type="button" class="arq-tab-btn" data-target="tab-cierre"><i class="ph-duotone ph-cash-register"></i>Ingresos y Cierre</button>
            <button type="button" class="arq-tab-btn" data-target="tab-historial"><i class="ph-duotone ph-clock-counter-clockwise"></i>Historial</button>
        </div>

        <form method="post" action="ArqueoController.php">
            <div class="arq-tab-content active" id="tab-cuadre">
                <div class="arq-card mb-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                        <h3 class="arq-card-title mb-0">Registro de piezas</h3>
                        <div class="arq-date-picker-group">
                            <input type="date" name="fecha_arqueo" id="fechaArqueo" class="form-control arq-input-date" value="<?= htmlspecialchars($fecha_sel) ?>">
                        </div>
                    </div>

                    <div class="table-responsive mb-3">
                        <table class="table table-sm align-middle mb-0" id="tablaArqueo">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th class="text-center">Sobrante ayer</th>
                                    <th class="text-center">Horneadas hoy</th>
                                    <th class="text-center">En pedidos</th>
                                    <th class="text-center">Vendidas (mostrador)</th>
                                    <th class="text-center">Sobrante hoy (contado)</th>
                                    <th class="text-center">Diferencia</th>
                                    <th class="text-end">Valor</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($productos)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">No hay productos activos en la base de datos.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($productos as $p): ?>
                                        <?php
                                            $pid = (int)$p['id'];
                                            $precio = (float)$p['precio'];
                                            $ayer = (int)($sobranteAyer[$pid] ?? 0);
                                            $horn = (int)($horneadasHoy[$pid] ?? 0);
                                            $entr = (int)($entregadasHoy[$pid] ?? 0);
                                            $vend = (int)($detalleHoy[$pid]['vendidas'] ?? 0);
                                            $sobr = (int)($detalleHoy[$pid]['sobrante'] ?? 0);
                                            $dif = $ayer + $horn - $entr - $vend - $sobr;
                                            $val = $vend * $precio;
                                        ?>
                                        <tr class="arq-row" data-precio="<?= $precio ?>" data-ayer="<?= $ayer ?>" data-horn="<?= $horn ?>" data-entr="<?= $entr ?>">
                                            <td>
                                                <strong style="display: block;"><?= htmlspecialchars($p['nombre']) ?></strong>
                                                <span class="text-muted">$<?= number_format($precio, 2) ?> c/u</span>
                                            </td>
                                            <td class="text-center"><?= $ayer ?></td>
                                            <td class="text-center"><?= $horn ?></td>
                                            <td class="text-center" title="Piezas de pedidos entregados este día"><?= $entr > 0 ? $entr : '—' ?></td>
                                            <td class="text-center">
                                                <input type="number" min="0" name="vendidas[<?= $pid ?>]" class="form-control arq-table-input mx-auto js-arq-vend" value="<?= $vend ?>">
                                            </td>
                                            <td class="text-center">
                                                <input type="number" min="0" name="sobrante[<?= $pid ?>]" class="form-control arq-table-input mx-auto js-arq-sobr" value="<?= $sobr ?>">
                                            </td>
                                            <td class="text-center js-arq-dif"><?= $dif ?></td>
                                            <td class="text-end js-arq-val">$<?= number_format($val, 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="text-muted">
                        Diferencia = sobrante de ayer + horneadas – entregadas en pedidos – vendidas en mostrador – sobrante contado. Las piezas de pedidos entregados se suman solas. Positiva: pan que no aparece (merma o faltante). Negativa: excedente (revise si faltó anotar una tanda).
                    </div>
                </div>
            </div>

            <div class="arq-tab-content" id="tab-cierre">
                <div class="arq-card mb-4">
                    <h3 class="arq-card-title mb-4">Resumen de ingresos</h3>
                    
                    <div class="arq-summary-grid mb-4">
                        <div class="arq-sub-card">
                            <span class="arq-sub-label"><i class="ph-duotone ph-coins"></i>Esperado en caja</span>
                            <strong class="arq-sub-val" id="resEsperado" data-abonos="<?= number_format($abonosHoy, 2, '.', '') ?>">$0.00</strong>
                            <span class="arq-sub-desc">mostrador <span id="resMostrador">$0.00</span> + pedidos $<?= number_format($abonosHoy, 2) ?></span>
                        </div>
                        <div class="arq-sub-card arq-sub-warning">
                            <span class="arq-sub-label"><i class="ph-duotone ph-trend-down"></i>Valor de la diferencia</span>
                            <strong class="arq-sub-val" id="resDiferencia">$0.00</strong>
                            <span class="arq-sub-desc">pérdida estimada si es positiva</span>
                        </div>
                        <div class="arq-sub-card arq-sub-info">
                            <span class="arq-sub-label"><i class="ph-duotone ph-scales"></i>Descuadre de caja</span>
                            <strong class="arq-sub-val" id="resDescuadre">—</strong>
                            <span class="arq-sub-desc">dinero contado – esperado</span>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="arq-form-label">Dinero contado en caja ($)</label>
                            <input type="number" step="any" min="0" name="dinero_contado" id="dineroContado" class="form-control arq-input" placeholder="0.00" value="<?= $cajaHoy['caja_real'] !== null && $cajaHoy['caja_real'] !== '' ? htmlspecialchars($cajaHoy['caja_real']) : '' ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="arq-form-label">Observaciones</label>
                            <input type="text" name="observaciones" class="form-control arq-input" placeholder="Ej. se regaló pan a un vecino" value="<?= htmlspecialchars($cajaHoy['nota'] ?? '') ?>">
                        </div>
                    </div>

                    <button type="submit" class="btn text-white px-4 py-2 btn-brand">
                        <i class="ph-duotone ph-floppy-disk"></i>Guardar arqueo
                    </button>
                </div>
            </div>
        </form>

        <div class="arq-tab-content" id="tab-historial">
            <div class="arq-card mb-4">
                <h3 class="arq-card-title">Historial reciente</h3>
                <?php if (empty($historial)): ?>
                    <div class="text-muted text-center py-5">
                        <span class="empty-state"><i class="ph-duotone ph-clock-counter-clockwise"></i>Aún no hay arqueos guardados.</span>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th class="text-end">Esperado</th>
                                    <th class="text-end">En caja</th>
                                    <th>Observaciones</th>
                                    <th class="text-end">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($historial as $h): ?>
                                    <tr>
                                        <td><?= date('d/m/Y', strtotime($h['fecha'])) ?></td>
                                        <td class="text-end">$<?= number_format((float)$h['esperado'], 2) ?></td>
                                        <td class="text-end"><?= $h['caja_real'] !== null ? '$' . number_format((float)$h['caja_real'], 2) : '—' ?></td>
                                        <td class="small text-muted"><?= htmlspecialchars($h['nota'] ?? '-') ?></td>
                                        <td class="text-end">
                                            <a href="ArqueoController.php?fecha=<?= urlencode($h['fecha']) ?>" class="btn btn-sm btn-outline-secondary">Ver</a>
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

<script src="/panaderia/Assets/js/arqueo.js?v=2"></script>
