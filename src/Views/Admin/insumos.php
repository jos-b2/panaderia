<?php
/**
 * Vista: Materia prima - Panadería Don Leo
 * Ubicación: /panaderia/src/Views/Admin/insumos.php
 * La prepara Controllers/InsumosController.php
 */
// Si se abre esta vista directamente (dirección vieja), ir a su controlador
if (realpath($_SERVER["SCRIPT_FILENAME"]) === __FILE__) {
    header("Location: /panaderia/Controllers/InsumosController.php");
    exit;
}

include_once __DIR__ . '/../Layouts/header.php';
?>

<link rel="stylesheet" href="/panaderia/Assets/css/siderbar.css?v=4">
<link rel="stylesheet" href="/panaderia/Assets/css/insumos.css?v=2">

<div class="d-flex">
    <?php include_once __DIR__ . '/../Layouts/sidebar.php'; ?>

    <div class="main">
        <div class="page-head ins-header-section mb-4">
            <div class="d-flex justify-content-between align-items-baseline flex-wrap gap-2">
                <div>
                    <h2 class="fw-bold page-title">Inventario</h2>
                    <p class="text-muted mb-0">Administración de inventario y control de existencias.</p>
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

        <div class="ins-actions-bar d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div class="d-flex gap-2 align-items-center">
                <a class="ins-btn ins-btn-primary" href="<?= $mostrarNuevo ? 'InsumosController.php' : 'InsumosController.php?nuevo=1' ?>">
                    <i class="ph <?= $mostrarNuevo ? 'ph-x' : 'ph-plus' ?>"></i>
                    <?= $mostrarNuevo ? 'Cerrar formulario' : 'Nuevo insumo' ?>
                </a>
                <a class="ins-btn ins-btn-line" href="InsumosController.php<?= $verInactivos ? '' : '?inactivos=1' ?>">
                    <i class="ph-duotone <?= $verInactivos ? 'ph-eye-slash' : 'ph-eye' ?>"></i><?= $verInactivos ? 'Ocultar inactivos' : 'Mostrar inactivos' ?>
                </a>
            </div>
            <div class="text-muted">
                Valor del inventario: <strong>$<?= number_format($valorInventario, 2) ?></strong>
            </div>
        </div>

        <?php if ($mostrarNuevo || $insumoEditar): ?>
            <div class="card p-3 mb-4">
                <h5 class="fw-bold mb-3">
                    <?= $insumoEditar ? 'Editar insumo: ' . htmlspecialchars($insumoEditar['nombre']) : 'Nuevo insumo' ?>
                </h5>
                <form method="post" action="InsumosController.php">
                    <input type="hidden" name="accion" value="<?= $insumoEditar ? 'editar' : 'crear' ?>">
                    <?php if ($insumoEditar): ?>
                        <input type="hidden" name="id" value="<?= (int)$insumoEditar['id'] ?>">
                    <?php endif; ?>
                    <div class="row g-3 align-items-end">
                        <?php $colNum = $insumoEditar ? 'col-sm-6 col-xl-2' : 'col-sm-4 col-xl-2'; ?>
                        <div class="col-sm-6 col-xl">
                            <label class="form-label small fw-semibold">Nombre</label>
                            <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($insumoEditar['nombre'] ?? '') ?>" required>
                        </div>
                        <div class="col-sm-6 col-xl-2">
                            <label class="form-label small fw-semibold">Unidad</label>
                            <input type="text" name="unidad" class="form-control" placeholder="lb, unidad, lt..." value="<?= htmlspecialchars($insumoEditar['unidad'] ?? 'lb') ?>" required>
                        </div>
                        <?php if (!$insumoEditar): ?>
                            <div class="<?= $colNum ?>">
                                <label class="form-label small fw-semibold">Stock inicial</label>
                                <input type="number" step="0.01" min="0" name="stock" class="form-control" value="0.00" required>
                            </div>
                        <?php endif; ?>
                        <div class="<?= $colNum ?>">
                            <label class="form-label small fw-semibold">Stock mínimo</label>
                            <input type="number" step="0.01" min="0" name="stock_minimo" class="form-control" value="<?= htmlspecialchars($insumoEditar['stock_minimo'] ?? '0.00') ?>" required>
                        </div>
                        <div class="<?= $colNum ?>">
                            <label class="form-label small fw-semibold">Costo ($)</label>
                            <input type="number" step="0.01" min="0" name="costo" class="form-control" value="<?= htmlspecialchars($insumoEditar['costo'] ?? '0.00') ?>" required>
                        </div>
                        <!-- Los botones toman solo el ancho que necesitan -->
                        <div class="col-12 col-xl-auto d-flex gap-2 justify-content-end">
                            <?php if ($insumoEditar): ?>
                                <a href="InsumosController.php" class="btn btn-outline-secondary">Cancelar</a>
                            <?php endif; ?>
                            <button type="submit" class="btn btn-brand"><i class="ph-duotone ph-floppy-disk"></i>Guardar</button>
                        </div>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <?php if ($insumoMovimiento): ?>
            <div class="card p-3 mb-4">
                <h5 class="fw-bold mb-3">
                    Registrar movimiento: <?= htmlspecialchars($insumoMovimiento['nombre']) ?> (Actual: <?= number_format($insumoMovimiento['stock'], 2) ?> <?= htmlspecialchars($insumoMovimiento['unidad']) ?>)
                </h5>
                <form method="post" action="InsumosController.php">
                    <input type="hidden" name="accion" value="movimiento">
                    <input type="hidden" name="insumo_id" value="<?= (int)$insumoMovimiento['id'] ?>">
                    <div class="row g-3 align-items-end">
                        <div class="col-sm-6 col-xl-3">
                            <label class="form-label small fw-semibold">Tipo</label>
                            <select name="tipo" class="form-select">
                                <option value="entrada">Entrada (compra / suma)</option>
                                <option value="salida">Salida (merma / resta)</option>
                                <option value="ajuste">Ajuste (fijar stock exacto)</option>
                            </select>
                        </div>
                        <div class="col-sm-6 col-xl-2">
                            <label class="form-label small fw-semibold">Cantidad</label>
                            <input type="number" step="0.01" min="0.01" name="cantidad" class="form-control" required>
                        </div>
                        <div class="col-12 col-xl">
                            <label class="form-label small fw-semibold">Nota (opcional)</label>
                            <input type="text" name="nota" class="form-control" placeholder="Ej: Compra proveedor, ajuste físico...">
                        </div>
                        <!-- Los botones toman solo el ancho que necesitan -->
                        <div class="col-12 col-xl-auto d-flex gap-2 justify-content-end">
                            <a href="InsumosController.php" class="btn btn-outline-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-brand"><i class="ph-duotone ph-check-circle"></i>Aplicar</button>
                        </div>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <div class="ins-tabs mb-4 d-flex gap-2">
            <button class="ins-tab-btn active" data-target="tab-inventario"><i class="ph-duotone ph-package"></i>Inventario</button>
            <button class="ins-tab-btn" data-target="tab-movimientos"><i class="ph-duotone ph-clock-counter-clockwise"></i>Últimos Movimientos</button>
        </div>

        <div class="ins-tab-content active" id="tab-inventario">
            <div class="card p-3 mb-4">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Insumo</th>
                                <th>Existencia</th>
                                <th class="text-center">Mínimo</th>
                                <th class="text-center">Costo</th>
                                <th>Estado</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($listaInsumos)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No hay insumos registrados.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($listaInsumos as $item): ?>
                                    <?php
                                        $min = (float)$item['stock_minimo'];
                                        $stk = (float)$item['stock'];
                                        $pct = $min > 0 ? min(100, round(($stk / ($min * 2)) * 100)) : 100;
                                        if ($stk <= 0) {
                                            $badgeClass = 'bg-danger-subtle text-danger border-danger-subtle';
                                            $estadoTxt = 'Agotado';
                                        } elseif ($stk <= $min) {
                                            $badgeClass = 'bg-warning-subtle text-warning-emphasis border-warning-subtle';
                                            $estadoTxt = 'Bajo';
                                        } else {
                                            $badgeClass = 'bg-success-subtle text-success border-success-subtle';
                                            $estadoTxt = 'Normal';
                                        }
                                    ?>
                                    <tr class="<?= $item['activo'] ? '' : 'opacity-50' ?>">
                                        <td><strong><?= htmlspecialchars($item['nombre']) ?></strong></td>
                                        <td>
                                            <div><?= number_format($stk, 2) ?> <?= htmlspecialchars($item['unidad']) ?></div>
                                            <div class="ins-bar"><i style="width: <?= $pct ?>%"></i></div>
                                        </td>
                                        <td class="text-center"><?= number_format($min, 2) ?></td>
                                        <td class="text-center">$<?= number_format((float)$item['costo'], 2) ?></td>
                                        <td>
                                            <?php if (!$item['activo']): ?>
                                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1">Inactivo</span>
                                            <?php else: ?>
                                                <span class="badge border px-2 py-1 <?= $badgeClass ?>"><?= $estadoTxt ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-flex gap-1 justify-content-end">
                                                <a href="InsumosController.php?movimiento=<?= (int)$item['id'] ?>" class="btn btn-sm btn-accent"><i class="ph-duotone ph-arrows-down-up"></i>Movimiento</a>
                                                <a href="InsumosController.php?editar=<?= (int)$item['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="ph-duotone ph-pencil-simple"></i>Editar</a>
                                                <a href="InsumosController.php?toggle=<?= (int)$item['id'] ?><?= $verInactivos ? '&inactivos=1' : '' ?>" class="btn btn-sm btn-outline-danger ins-btn-danger">
                                                    <i class="ph-duotone <?= $item['activo'] ? 'ph-prohibit' : 'ph-check-circle' ?>"></i><?= $item['activo'] ? 'Desactivar' : 'Activar' ?>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="ins-tab-content" id="tab-movimientos">
            <div class="card p-3 mb-4">
                <?php if (empty($listaMovimientos)): ?>
                    <div class="text-muted text-center py-5">
                        <span class="empty-state"><i class="ph-duotone ph-clock-counter-clockwise"></i>Sin movimientos todavía.</span>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Insumo</th>
                                    <th>Tipo</th>
                                    <th class="text-end">Cantidad</th>
                                    <th class="text-end">Resultante</th>
                                    <th>Nota</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($listaMovimientos as $m): ?>
                                    <tr>
                                        <td class="small text-muted text-nowrap"><?= date('d/m/Y H:i', strtotime($m['fecha'])) ?></td>
                                        <td><?= htmlspecialchars($m['insumo']) ?></td>
                                        <td>
                                            <span class="badge <?= $m['tipo'] === 'entrada' ? 'bg-success-subtle text-success' : ($m['tipo'] === 'salida' ? 'bg-danger-subtle text-danger' : 'bg-info-subtle text-info') ?>">
                                                <?= ucfirst($m['tipo']) ?>
                                            </span>
                                        </td>
                                        <td class="text-end"><?= number_format((float)$m['cantidad'], 2) ?> <?= htmlspecialchars($m['unidad']) ?></td>
                                        <td class="text-end fw-semibold"><?= number_format((float)$m['stock_resultante'], 2) ?></td>
                                        <td class="small text-muted"><?= htmlspecialchars($m['nota'] ?? '-') ?></td>
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

<script src="/panaderia/Assets/js/insumos.js"></script>
