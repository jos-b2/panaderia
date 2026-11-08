<?php
/**
 * Vista: Producción - Panadería Don Leo
 * Ubicación: /panaderia/src/Views/Admin/produccion.php
 * La prepara Controllers/ProduccionController.php
 */
// Si se abre esta vista directamente (dirección vieja), ir a su controlador
if (realpath($_SERVER["SCRIPT_FILENAME"]) === __FILE__) {
    header("Location: /panaderia/Controllers/ProduccionController.php");
    exit;
}

include_once __DIR__ . '/../Layouts/header.php';
?>

<link rel="stylesheet" href="/panaderia/Assets/css/siderbar.css?v=4">
<link rel="stylesheet" href="/panaderia/Assets/css/produccion.css?v=4">

<div class="d-flex">
    <?php include_once __DIR__ . '/../Layouts/sidebar.php'; ?>

    <div class="main">
        <div class="page-head prod-header-section mb-3">
            <div class="d-flex justify-content-between align-items-baseline flex-wrap gap-2">
                <div>
                    <h2 class="fw-bold page-title">Producción</h2>
                    <p class="mb-0">Registra cada horneada y el inventario se actualiza solo.</p>
                </div>
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

        <div class="prod-layout">

            <!-- Registro de tanda en 3 pasos -->
            <form method="post" action="ProduccionController.php" id="formProduccion" class="prod-card prod-wizard"
                  data-productos='<?= htmlspecialchars(json_encode($dataProductos), ENT_QUOTES, 'UTF-8') ?>'>
                <input type="hidden" name="accion" value="registrar">

                <header class="prod-wizard-head">
                    <span class="prod-wizard-icon"><i class="ph-duotone ph-cooking-pot"></i></span>
                    <div>
                        <h3 class="prod-card-title mb-0">Registrar tanda de horneado</h3>
                        <small>El inventario se descuenta automáticamente según la receta.</small>
                    </div>
                </header>

                <?php if (empty($productos)): ?>
                    <div class="empty-state py-5">
                        <i class="ph-duotone ph-book-open-text"></i>
                        No hay recetas activas. Crea una en <a href="RecetasController.php">Recetas</a> para poder registrar tandas.
                    </div>
                <?php else: ?>

                    <!-- Paso 1: producto -->
                    <section class="prod-step">
                        <div class="prod-step-head"><span class="prod-step-num">1</span><h4>¿Qué vas a hornear?</h4></div>
                        <div class="prod-picks">
                            <?php foreach ($productos as $i => $p): ?>
                                <label class="prod-pick">
                                    <input type="radio" name="producto_id" value="<?= (int)$p['id'] ?>" <?= $i === 0 ? 'checked required' : '' ?>>
                                    <span class="prod-pick-box">
                                        <i class="ph-duotone ph-bread"></i>
                                        <strong><?= htmlspecialchars($p['nombre']) ?></strong>
                                        <small><?= rtrim(rtrim(number_format((float)$p['rendimiento'], 2), '0'), '.') ?> piezas por <?= htmlspecialchars($p['base_unidad']) ?></small>
                                        <span class="prod-pick-check"><i class="ph-fill ph-check-circle"></i></span>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <!-- Paso 2: cantidades -->
                    <section class="prod-step">
                        <div class="prod-step-head"><span class="prod-step-num">2</span><h4>Cantidades</h4></div>
                        <div class="row g-3">
                            <div class="col-sm-6 col-xl-4">
                                <label class="form-label" id="lblBaseInsumo" for="inputCantidadBase">Materia base</label>
                                <div class="prod-unit-input">
                                    <input type="number" step="any" min="0.01" name="cantidad_base" id="inputCantidadBase" class="form-control" placeholder="0" required>
                                    <span id="unidadBase">lb</span>
                                </div>
                                <small class="prod-hint" id="hintBase">&nbsp;</small>
                            </div>
                            <div class="col-sm-6 col-xl-4">
                                <label class="form-label" for="inputPiezas">Piezas horneadas</label>
                                <div class="prod-unit-input">
                                    <input type="number" min="1" name="piezas" id="inputPiezas" class="form-control" placeholder="0" required>
                                    <span>pzs</span>
                                </div>
                                <small class="prod-hint" id="hintPiezas">Se calculan solas; puedes ajustarlas.</small>
                            </div>
                            <div class="col-sm-6 col-xl-4">
                                <label class="form-label" for="prodFecha">Fecha</label>
                                <input type="date" name="fecha" id="prodFecha" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-sm-6 col-xl-12">
                                <label class="form-label" for="prodNota">Nota <span class="text-muted text-lowercase">(opcional)</span></label>
                                <input type="text" name="nota" id="prodNota" class="form-control" placeholder="Ej. tanda de la mañana">
                            </div>
                        </div>
                    </section>

                    <!-- Paso 3: revisión de insumos -->
                    <section class="prod-step">
                        <div class="prod-step-head">
                            <span class="prod-step-num">3</span><h4>Revisión de insumos</h4>
                            <span class="prod-status" id="estadoStock"></span>
                        </div>
                        <div class="prod-consumo" id="listaConsumo">
                            <div class="empty-state py-3"><i class="ph-duotone ph-scales"></i>Ingresa la cantidad de materia base para ver el consumo.</div>
                        </div>
                    </section>

                    <footer class="prod-wizard-foot">
                        <label class="prod-force" id="forzarCaja">
                            <input class="form-check-input" type="checkbox" name="forzar_stock" id="forzarStock">
                            <span>Registrar de todos modos <small>(el stock quedará en 0)</small></span>
                        </label>
                        <button type="submit" class="btn btn-brand prod-submit">
                            <i class="ph-duotone ph-fire"></i><span id="btnRegistrarTexto">Registrar tanda</span>
                        </button>
                    </footer>
                <?php endif; ?>
            </form>

            <!-- Columna lateral -->
            <aside class="prod-aside">
                <section class="prod-card">
                    <h3 class="prod-card-title">Últimas tandas</h3>

                    <?php if (empty($ultimasTandas)): ?>
                        <div class="empty-state py-4"><i class="ph-duotone ph-fire"></i>Aún no hay tandas registradas.</div>
                    <?php else: ?>
                        <?php $mesesCortos = ['', 'ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic']; ?>
                        <ol class="prod-timeline">
                            <?php foreach ($ultimasTandas as $t): ?>
                                <?php $ts = strtotime($t['fecha']); ?>
                                <li class="<?= $t['anulada'] ? 'is-void' : '' ?>">
                                    <div class="prod-date">
                                        <strong><?= date('j', $ts) ?></strong>
                                        <small><?= $mesesCortos[(int)date('n', $ts)] ?></small>
                                    </div>
                                    <div class="prod-batch">
                                        <span class="prod-batch-name"><?= htmlspecialchars($t['producto']) ?></span>
                                        <small>
                                            <?= rtrim(rtrim(number_format((float)$t['base_cantidad'], 2), '0'), '.') ?> <?= htmlspecialchars($t['base_unidad']) ?>
                                            <?php if (!empty($t['nota'])): ?> · <?= htmlspecialchars($t['nota']) ?><?php endif; ?>
                                        </small>
                                    </div>
                                    <div class="prod-batch-pieces">
                                        <strong><?= (int)$t['piezas'] ?></strong><small>pzs</small>
                                    </div>
                                    <?php if ($t['anulada']): ?>
                                        <span class="prod-void-tag">Anulada</span>
                                    <?php else: ?>
                                        <form method="post" action="ProduccionController.php" class="m-0">
                                            <input type="hidden" name="accion" value="anular">
                                            <input type="hidden" name="produccion_id" value="<?= (int)$t['id'] ?>">
                                            <button type="submit" class="prod-void-btn js-confirm-anular" title="Anular tanda y devolver insumos" aria-label="Anular tanda">
                                                <i class="ph-duotone ph-arrow-counter-clockwise"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    <?php endif; ?>
                </section>

                <section class="prod-card prod-how">
                    <h3 class="prod-card-title">¿Cómo funciona?</h3>
                    <ul>
                        <li><i class="ph-duotone ph-book-open-text"></i><span>El consumo de cada insumo se calcula con la <a href="RecetasController.php">receta</a> del producto.</span></li>
                        <li><i class="ph-duotone ph-package"></i><span>Al registrar, se descuenta del <a href="InsumosController.php">inventario</a> automáticamente.</span></li>
                        <li><i class="ph-duotone ph-cash-register"></i><span>Las piezas pasan al <a href="ArqueoController.php">arqueo diario</a> para compararse con lo vendido.</span></li>
                        <li><i class="ph-duotone ph-arrow-counter-clockwise"></i><span>Si hubo un error, anula la tanda y los insumos regresan al stock.</span></li>
                    </ul>
                </section>
            </aside>

        </div>

        <?php include_once __DIR__ . '/../Layouts/footer.php'; ?>
    </div>
</div>

<script src="/panaderia/Assets/js/produccion.js?v=2"></script>
