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
<link rel="stylesheet" href="/panaderia/Assets/css/produccion.css?v=6">

<div class="d-flex">
    <?php include_once __DIR__ . '/../Layouts/sidebar.php'; ?>

    <div class="main">
        <div class="page-head prod-header-section mb-3">
            <div class="d-flex justify-content-between align-items-baseline flex-wrap gap-2">
                <div>
                    <h2 class="fw-bold page-title">Producción</h2>
                    <p class="mb-0">Registra las tandas y empieza a amasarlas; el horneado se sigue en Hornear.</p>
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

        <?php
            $editando = $tandaEditar !== null;
            $productoMarcado = $editando ? (int)$tandaEditar['producto_id'] : (int)($productos[0]['id'] ?? 0);
            $cant = fn($n) => rtrim(rtrim(number_format((float)$n, 2), '0'), '.');
        ?>
        <div class="prod-layout">

            <!-- Registro / modificación de tanda en 3 pasos -->
            <form method="post" action="ProduccionController.php" id="formProduccion" class="prod-card prod-wizard <?= $editando ? 'is-editing' : '' ?>"
                  data-productos='<?= htmlspecialchars(json_encode($dataProductos), ENT_QUOTES, 'UTF-8') ?>'>
                <input type="hidden" name="accion" value="<?= $editando ? 'modificar' : 'registrar' ?>">
                <?php if ($editando): ?>
                    <input type="hidden" name="produccion_id" value="<?= (int)$tandaEditar['id'] ?>">
                <?php endif; ?>

                <header class="prod-wizard-head">
                    <span class="prod-wizard-icon"><i class="ph-duotone <?= $editando ? 'ph-pencil-simple' : 'ph-cooking-pot' ?>"></i></span>
                    <div class="prod-wizard-title">
                        <h3 class="prod-card-title mb-0"><?= $editando ? 'Modificar tanda #' . (int)$tandaEditar['id'] : 'Registrar tanda' ?></h3>
                        <small><?= $editando ? 'Puedes cambiarla mientras no se empiece a amasar.' : 'Anótala ahora; los insumos se descuentan al empezar a amasar.' ?></small>
                    </div>

                    <!-- Botón cuadrado "!" con la explicación -->
                    <button type="button" class="prod-info-btn" id="btnInfo" aria-expanded="false" aria-controls="panelInfo" title="¿Cómo funciona?">!</button>
                </header>

                <div class="prod-info-panel" id="panelInfo" hidden>
                    <h4>¿Cómo funciona?</h4>
                    <ol>
                        <li><i class="ph-duotone ph-note-pencil"></i><span><strong>Registra</strong> la tanda: queda anotada y todavía puedes modificarla o eliminarla.</span></li>
                        <li><i class="ph-duotone ph-hand-grabbing"></i><span><strong>Empezar a amasar</strong> descuenta los insumos del <a href="InsumosController.php">inventario</a> según la <a href="RecetasController.php">receta</a>.</span></li>
                        <li><i class="ph-duotone ph-oven"></i><span>La masa aparece en <a href="HornearController.php">Hornear</a>: métela a un horno y el sistema te avisa cuándo voltear y cuándo sacar.</span></li>
                        <li><i class="ph-duotone ph-storefront"></i><span>Al sacar todas sus piezas, la tanda queda <strong>lista</strong>: cuenta como pan disponible para pedidos y para el <a href="ArqueoController.php">arqueo</a>.</span></li>
                    </ol>
                </div>

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
                                    <input type="radio" name="producto_id" value="<?= (int)$p['id'] ?>" <?= (int)$p['id'] === $productoMarcado ? 'checked' : '' ?> <?= $i === 0 ? 'required' : '' ?>>
                                    <span class="prod-pick-box">
                                        <i class="ph-duotone ph-bread"></i>
                                        <strong><?= htmlspecialchars($p['nombre']) ?></strong>
                                        <small><?= $cant($p['rendimiento']) ?> piezas por <?= htmlspecialchars($p['base_unidad']) ?></small>
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
                                    <input type="number" step="any" min="0.01" name="cantidad_base" id="inputCantidadBase" class="form-control" placeholder="0"
                                           value="<?= $editando ? $cant($tandaEditar['base_cantidad']) : '' ?>" required>
                                    <span id="unidadBase">lb</span>
                                </div>
                                <small class="prod-hint" id="hintBase">&nbsp;</small>
                            </div>
                            <div class="col-sm-6 col-xl-4">
                                <label class="form-label" for="inputPiezas">Piezas</label>
                                <div class="prod-unit-input">
                                    <input type="number" min="1" name="piezas" id="inputPiezas" class="form-control" placeholder="0"
                                           value="<?= $editando ? (int)$tandaEditar['piezas'] : '' ?>" required>
                                    <span>pzs</span>
                                </div>
                                <small class="prod-hint" id="hintPiezas">Se calculan solas; puedes ajustarlas.</small>
                            </div>
                            <div class="col-sm-6 col-xl-4">
                                <label class="form-label" for="prodFecha">Fecha</label>
                                <input type="date" name="fecha" id="prodFecha" class="form-control" value="<?= $editando ? htmlspecialchars($tandaEditar['fecha']) : date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-sm-6 col-xl-12">
                                <label class="form-label" for="prodNota">Nota <span class="text-muted text-lowercase">(opcional)</span></label>
                                <input type="text" name="nota" id="prodNota" class="form-control" placeholder="Ej. tanda de la mañana"
                                       value="<?= $editando ? htmlspecialchars($tandaEditar['nota'] ?? '') : '' ?>">
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
                            <input class="form-check-input" type="checkbox" name="forzar_stock" id="forzarStock" <?= ($editando && $tandaEditar['forzar_stock']) ? 'checked' : '' ?>>
                            <span>Registrar de todos modos <small>(al amasar, el stock quedará en 0)</small></span>
                        </label>
                        <?php if ($editando): ?>
                            <a href="ProduccionController.php" class="btn btn-outline-secondary">Cancelar</a>
                            <button type="button" class="btn btn-outline-danger" id="btnEliminarTanda" data-id="<?= (int)$tandaEditar['id'] ?>">
                                <i class="ph-duotone ph-trash"></i>Eliminar tanda
                            </button>
                        <?php endif; ?>
                        <button type="submit" class="btn btn-brand prod-submit">
                            <i class="ph-duotone <?= $editando ? 'ph-floppy-disk' : 'ph-note-pencil' ?>"></i><span id="btnRegistrarTexto"><?= $editando ? 'Guardar cambios' : 'Registrar tanda' ?></span>
                        </button>
                    </footer>
                <?php endif; ?>
            </form>

            <!-- Columna lateral: tandas y sus etapas -->
            <aside class="prod-aside">
                <section class="prod-card">
                    <h3 class="prod-card-title">Tandas</h3>

                    <?php if (empty($ultimasTandas)): ?>
                        <div class="empty-state py-4"><i class="ph-duotone ph-fire"></i>Aún no hay tandas registradas.</div>
                    <?php else: ?>
                        <?php
                            $mesesCortos = ['', 'ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
                            $etapas = [
                                'registrada' => ['Por amasar', 'ph-note-pencil'],
                                'amasando'   => ['Amasando', 'ph-hand-grabbing'],
                                'horneando'  => ['En el horno', 'ph-oven'],
                                'lista'      => ['Lista', 'ph-check-circle'],
                            ];
                        ?>
                        <ol class="prod-timeline">
                            <?php foreach ($ultimasTandas as $t): ?>
                                <?php
                                    $ts = strtotime($t['fecha']);
                                    [$etapaTxt, $etapaIcono] = $etapas[$t['estado']] ?? ['—', 'ph-circle'];
                                    $esEdicion = $editando && (int)$tandaEditar['id'] === (int)$t['id'];
                                ?>
                                <li class="etapa-<?= $t['estado'] ?> <?= $esEdicion ? 'is-current' : '' ?>">
                                    <div class="prod-tl-row">
                                        <div class="prod-date">
                                            <strong><?= date('j', $ts) ?></strong>
                                            <small><?= $mesesCortos[(int)date('n', $ts)] ?></small>
                                        </div>
                                        <div class="prod-batch">
                                            <span class="prod-batch-name"><?= htmlspecialchars($t['producto']) ?></span>
                                            <small>
                                                <?= $cant($t['base_cantidad']) ?> <?= htmlspecialchars($t['base_unidad']) ?>
                                                <?php if (!empty($t['nota'])): ?> · <?= htmlspecialchars($t['nota']) ?><?php endif; ?>
                                            </small>
                                        </div>
                                        <div class="prod-batch-pieces">
                                            <strong><?= (int)$t['piezas'] ?></strong><small>pzs</small>
                                        </div>
                                    </div>

                                    <div class="prod-tl-foot">
                                        <span class="prod-etapa etapa-<?= $t['estado'] ?>"><i class="ph-duotone <?= $etapaIcono ?>"></i><?= $etapaTxt ?></span>

                                        <?php if ($t['estado'] === 'registrada'): ?>
                                            <div class="prod-tl-actions">
                                                <a href="ProduccionController.php?editar=<?= (int)$t['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Modificar tanda">
                                                    <i class="ph-duotone ph-pencil-simple"></i>Modificar
                                                </a>
                                                <form method="post" action="ProduccionController.php" class="m-0">
                                                    <input type="hidden" name="accion" value="amasar">
                                                    <input type="hidden" name="produccion_id" value="<?= (int)$t['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-accent js-amasar"
                                                            data-producto="<?= htmlspecialchars($t['producto']) ?>" data-piezas="<?= (int)$t['piezas'] ?>">
                                                        <i class="ph-duotone ph-hand-grabbing"></i>Empezar a amasar
                                                    </button>
                                                </form>
                                            </div>
                                        <?php elseif ($t['estado'] === 'amasando' || $t['estado'] === 'horneando'): ?>
                                            <a href="HornearController.php" class="prod-tl-link">Ir a Hornear<i class="ph ph-arrow-right"></i></a>
                                        <?php endif; ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    <?php endif; ?>
                </section>
            </aside>

        </div>

        <!-- Formulario oculto para eliminar una tanda registrada -->
        <form method="post" action="ProduccionController.php" id="formEliminarTanda" class="d-none">
            <input type="hidden" name="accion" value="eliminar">
            <input type="hidden" name="produccion_id" id="eliminarTandaId" value="">
        </form>

        <?php include_once __DIR__ . '/../Layouts/footer.php'; ?>
    </div>
</div>

<script src="/panaderia/Assets/js/produccion.js?v=3"></script>
