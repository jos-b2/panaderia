<?php
/**
 * Vista: Recetas - Panadería Don Leo
 * Ubicación: /panaderia/src/Views/Admin/recetas.php
 * La prepara Controllers/RecetasController.php
 */
// Si se abre esta vista directamente (dirección vieja), ir a su controlador
if (realpath($_SERVER["SCRIPT_FILENAME"]) === __FILE__) {
    header("Location: /panaderia/Controllers/RecetasController.php");
    exit;
}

include_once __DIR__ . '/../Layouts/header.php';
?>

<link rel="stylesheet" href="/panaderia/Assets/css/siderbar.css?v=4">
<link rel="stylesheet" href="/panaderia/Assets/css/recetas.css?v=5">

<div class="d-flex">
    <?php include_once __DIR__ . '/../Layouts/sidebar.php'; ?>

    <div class="main">
        <div class="page-head rec-header-section mb-4">
            <div class="d-flex justify-content-between align-items-baseline flex-wrap gap-2">
                <div>
                    <h2 class="fw-bold page-title">Recetas y productos</h2>
                    <p class="text-muted mb-0">Cada receta se define por <strong>1 unidad de la materia base</strong> (por ejemplo, 1 libra de harina).</p>
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

        <div class="mb-4 d-flex gap-2 flex-wrap align-items-center">
            <a class="ins-btn ins-btn-primary btn-accent" href="<?= $mostrarNuevo ? 'RecetasController.php' : 'RecetasController.php?nuevo=1' ?>" style="display: inline-flex;">
                <i class="ph <?= $mostrarNuevo ? 'ph-x' : 'ph-plus' ?>"></i>
                <?= $mostrarNuevo ? 'Cerrar formulario' : 'Nuevo producto' ?>
            </a>
            <a class="btn btn-outline-secondary" href="RecetasController.php<?= $verInactivos ? '' : '?inactivos=1' ?>">
                <i class="ph-duotone <?= $verInactivos ? 'ph-eye-slash' : 'ph-eye' ?>"></i><?= $verInactivos ? 'Ocultar inactivos' : 'Mostrar inactivos' ?>
            </a>
        </div>

        <?php if ($mostrarNuevo || $productoEditar): ?>
            <div class="rec-card mb-4">
                <h3 class="rec-product-title mb-3">
                    <?= $productoEditar ? 'Editar receta: ' . htmlspecialchars($productoEditar['nombre']) : 'Nuevo producto y receta' ?>
                </h3>
                <form method="post" action="RecetasController.php">
                    <input type="hidden" name="accion" value="<?= $productoEditar ? 'editar' : 'crear' ?>">
                    <?php if ($productoEditar): ?>
                        <input type="hidden" name="id" value="<?= (int)$productoEditar['id'] ?>">
                    <?php endif; ?>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Nombre del producto</label>
                            <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($productoEditar['nombre'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Precio venta ($)</label>
                            <input type="number" step="0.01" min="0" name="precio" class="form-control" value="<?= htmlspecialchars($productoEditar['precio'] ?? '0.00') ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Materia base (1 unidad)</label>
                            <select name="base_insumo_id" class="form-select" required>
                                <?php foreach ($insumosActivos as $ins): ?>
                                    <option value="<?= (int)$ins['id'] ?>" <?= ($productoEditar && (int)$productoEditar['base_insumo_id'] === (int)$ins['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($ins['nombre']) ?> (<?= htmlspecialchars($ins['unidad']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Rinde (piezas por base)</label>
                            <input type="number" step="0.01" min="0.01" name="rendimiento" class="form-control" value="<?= htmlspecialchars($productoEditar['rendimiento'] ?? '1.00') ?>" required>
                        </div>
                    </div>

                    <!-- Tiempos de horno (los usa la vista Hornear para avisar cuándo voltear y sacar) -->
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6 col-md-3">
                            <label class="form-label small fw-semibold">Minutos de horneado</label>
                            <input type="number" min="1" name="minutos_horneado" class="form-control" value="<?= (int)($productoEditar['minutos_horneado'] ?? 20) ?>" required>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <label class="form-label small fw-semibold">Voltear al minuto <span class="text-lowercase">(opcional)</span></label>
                            <input type="number" min="1" name="minutos_volteo" class="form-control" placeholder="No se voltea"
                                   value="<?= isset($productoEditar['minutos_volteo']) && $productoEditar['minutos_volteo'] !== null ? (int)$productoEditar['minutos_volteo'] : '' ?>">
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <small class="text-muted"><i class="ph-duotone ph-oven me-1"></i>En Hornear, el temporizador avisa al minuto de volteo y al terminar el horneado.</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label small fw-semibold mb-0">Ingredientes adicionales (por 1 unidad base)</label>
                            <button type="button" id="btnAddIngrediente" class="btn btn-sm btn-outline-secondary"><i class="ph ph-plus"></i>Agregar ingrediente</button>
                        </div>
                        <div id="contenedorIngredientes" class="d-flex flex-column gap-2">
                            <?php if (!empty($itemsEditar)): ?>
                                <?php foreach ($itemsEditar as $it): ?>
                                    <div class="row g-2 align-items-center rec-ing-row">
                                        <div class="col-md-6">
                                            <select name="ing_id[]" class="form-select form-select-sm">
                                                <?php foreach ($insumosActivos as $ins): ?>
                                                    <option value="<?= (int)$ins['id'] ?>" <?= (int)$it['insumo_id'] === (int)$ins['id'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($ins['nombre']) ?> (<?= htmlspecialchars($ins['unidad']) ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <input type="number" step="any" min="0.01" name="ing_cant[]" class="form-control form-control-sm" value="<?= (float)$it['cantidad'] ?>" placeholder="Cantidad">
                                        </div>
                                        <div class="col-md-2">
                                            <button type="button" class="btn btn-sm btn-outline-danger w-100 js-remove-ing"><i class="ph-duotone ph-trash"></i>Quitar</button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn text-white px-4 py-2 btn-brand"><i class="ph-duotone ph-floppy-disk"></i>Guardar receta</button>
                        <a href="RecetasController.php" class="btn btn-outline-secondary px-4 py-2">Cancelar</a>
                    </div>
                </form>

                <template id="tplIngrediente">
                    <div class="row g-2 align-items-center rec-ing-row">
                        <div class="col-md-6">
                            <select name="ing_id[]" class="form-select form-select-sm">
                                <?php foreach ($insumosActivos as $ins): ?>
                                    <option value="<?= (int)$ins['id'] ?>">
                                        <?= htmlspecialchars($ins['nombre']) ?> (<?= htmlspecialchars($ins['unidad']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <input type="number" step="any" min="0.01" name="ing_cant[]" class="form-control form-control-sm" placeholder="Cantidad">
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-sm btn-outline-danger w-100 js-remove-ing"><i class="ph-duotone ph-trash"></i>Quitar</button>
                        </div>
                    </div>
                </template>
            </div>
        <?php endif; ?>

        <div class="rec-grid mb-4">
            <?php if (empty($productos)): ?>
                <div class="rec-card text-center text-muted py-5">No hay productos o recetas registradas.</div>
            <?php else: ?>
                <?php $numReceta = 0; ?>
                <?php foreach ($productos as $p): ?>
                    <?php
                        $pid = (int)$p['id'];
                        $items = $itemsPorProducto[$pid] ?? [];
                        $costoTanda = (float)$p['base_costo'];
                        foreach ($items as $it) {
                            $costoTanda += (float)$it['cantidad'] * (float)$it['costo'];
                        }
                        $rend = (float)$p['rendimiento'];
                        $costoPieza = $rend > 0 ? ($costoTanda / $rend) : 0;
                        $precioPieza = (float)$p['precio'];
                        $margen = $precioPieza > 0 ? round((($precioPieza - $costoPieza) / $precioPieza) * 100) : 0;

                        // Formato de cantidades sin ceros sobrantes (0.30 → 0.3)
                        $cant = fn($n) => rtrim(rtrim(number_format((float)$n, 2), '0'), '.');
                        $numReceta++;
                        $baseTxt = '1 ' . htmlspecialchars($p['base_unidad']) . ' de ' . htmlspecialchars(mb_strtolower($p['base_nombre']));
                    ?>
                    <!-- Tarjeta de receta con estilo de libro de cocina -->
                    <article class="rec-card recipe <?= $p['activo'] ? '' : 'is-inactive' ?>">

                        <header class="recipe-head">
                            <div class="recipe-kicker">
                                <span>Receta Nº <?= str_pad($numReceta, 2, '0', STR_PAD_LEFT) ?></span>
                                <span class="recipe-kicker-cat">Panadería</span>
                            </div>

                            <h3 class="recipe-title"><?= htmlspecialchars($p['nombre']) ?></h3>

                            <div class="recipe-ornament" aria-hidden="true">
                                <span></span><i class="ph-duotone ph-grains"></i><span></span>
                            </div>

                            <!-- Precio como sello -->
                            <div class="recipe-stamp">
                                <small>Precio</small>
                                <strong>$<?= number_format($precioPieza, 2) ?></strong>
                                <small>por pieza</small>
                            </div>

                            <?php if (!$p['activo']): ?>
                                <span class="recipe-off">Fuera de venta</span>
                            <?php endif; ?>
                        </header>

                        <p class="recipe-intro">
                            Por cada <strong><?= $baseTxt ?></strong> se hornean
                            <strong><?= $cant($rend) ?> piezas</strong>, con un costo aproximado de
                            <strong>$<?= number_format($costoPieza, 2) ?></strong> por pieza.
                        </p>
                        <p class="recipe-horno">
                            <i class="ph-duotone ph-oven"></i>Horno: <strong><?= (int)$p['minutos_horneado'] ?> min</strong>
                            <?= $p['minutos_volteo'] !== null ? '· voltear a los ' . (int)$p['minutos_volteo'] . ' min' : '· sin volteo' ?>
                        </p>

                        <!-- Ficha técnica -->
                        <dl class="recipe-facts">
                            <div>
                                <dt><i class="ph-duotone ph-bread"></i>Rinde</dt>
                                <dd><?= $cant($rend) ?><small>piezas</small></dd>
                            </div>
                            <div>
                                <dt><i class="ph-duotone ph-coins"></i>Costo</dt>
                                <dd>$<?= number_format($costoPieza, 2) ?><small>por pieza</small></dd>
                            </div>
                            <div>
                                <dt><i class="ph-duotone ph-chart-line-up"></i>Margen</dt>
                                <dd class="<?= $margen >= 0 ? 'is-pos' : 'is-neg' ?>"><?= $margen ?>%<small>de ganancia</small></dd>
                            </div>
                        </dl>

                        <!-- Ingredientes con líneas de puntos -->
                        <section class="recipe-ingredients">
                            <h4 class="recipe-subtitle">Ingredientes <em>por <?= $baseTxt ?></em></h4>
                            <ul>
                                <li class="is-base">
                                    <span class="ing-name"><?= htmlspecialchars($p['base_nombre']) ?><em>base</em></span>
                                    <span class="ing-dots"></span>
                                    <span class="ing-qty">1 <?= htmlspecialchars($p['base_unidad']) ?></span>
                                </li>
                                <?php foreach ($items as $it): ?>
                                    <li>
                                        <span class="ing-name"><?= htmlspecialchars($it['nombre']) ?></span>
                                        <span class="ing-dots"></span>
                                        <span class="ing-qty"><?= $cant($it['cantidad']) ?> <?= htmlspecialchars($it['unidad']) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </section>

                        <footer class="recipe-actions">
                            <a href="RecetasController.php?editar=<?= $pid ?>" class="btn btn-sm btn-accent"><i class="ph-duotone ph-pencil-simple"></i>Editar receta</a>
                            <a href="RecetasController.php?toggle=<?= $pid ?><?= $verInactivos ? '&inactivos=1' : '' ?>" class="btn btn-sm btn-outline-danger js-toggle-receta">
                                <i class="ph-duotone <?= $p['activo'] ? 'ph-prohibit' : 'ph-check-circle' ?>"></i><?= $p['activo'] ? 'Desactivar' : 'Activar' ?>
                            </a>
                        </footer>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php include_once __DIR__ . '/../Layouts/footer.php'; ?>
    </div>
</div>

<script src="/panaderia/Assets/js/recetas.js?v=2"></script>
