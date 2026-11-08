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

<link rel="stylesheet" href="/panaderia/Assets/css/siderbar.css">
<link rel="stylesheet" href="/panaderia/Assets/css/insumos.css">

<div class="d-flex">
    <?php include_once __DIR__ . '/../Layouts/sidebar.php'; ?>

    <div class="main flex-grow-1 p-4">
        <div class="ins-header-section mb-4">
            <div class="d-flex justify-content-between align-items-baseline flex-wrap gap-2">
                <div>
                    <h2 class="fw-bold" style="color: #6B4226; font-family: Georgia, serif; font-size: 28px; margin-bottom: 4px;">Inventario</h2>
                    <p class="text-muted mb-0" style="font-size: 14.5px;">Administración de inventario y control de existencias.</p>
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
            <div class="alert alert-<?= $tipo_alerta ?> alert-dismissible fade show py-2 px-3 mb-3" role="alert" style="font-size: 13.5px; border-radius: 10px;">
                <?= htmlspecialchars($mensaje) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar" style="padding: 0.65rem;"></button>
            </div>
        <?php endif; ?>

        <div class="ins-actions-bar d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div class="d-flex gap-2 align-items-center">
                <a class="ins-btn ins-btn-primary" href="<?= $mostrarNuevo ? 'InsumosController.php' : 'InsumosController.php?nuevo=1' ?>">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    <?= $mostrarNuevo ? 'Cerrar formulario' : 'Nuevo insumo' ?>
                </a>
                <a class="ins-btn ins-btn-line" href="InsumosController.php<?= $verInactivos ? '' : '?inactivos=1' ?>">
                    <?= $verInactivos ? 'Ocultar inactivos' : 'Mostrar inactivos' ?>
                </a>
            </div>
            <div class="text-muted" style="font-size: 14.5px;">
                Valor del inventario: <strong style="color: #6B4226;">$<?= number_format($valorInventario, 2) ?></strong>
            </div>
        </div>

        <?php if ($mostrarNuevo || $insumoEditar): ?>
            <div class="card p-3 mb-4" style="background: #FFFDF9; border: 1px solid #EADFCE; border-radius: 12px;">
                <h5 class="fw-bold mb-3" style="color: #4A2F20; font-family: Georgia, serif;">
                    <?= $insumoEditar ? 'Editar insumo: ' . htmlspecialchars($insumoEditar['nombre']) : 'Nuevo insumo' ?>
                </h5>
                <form method="post" action="InsumosController.php">
                    <input type="hidden" name="accion" value="<?= $insumoEditar ? 'editar' : 'crear' ?>">
                    <?php if ($insumoEditar): ?>
                        <input type="hidden" name="id" value="<?= (int)$insumoEditar['id'] ?>">
                    <?php endif; ?>
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold" style="color: #6b4531;">Nombre</label>
                            <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($insumoEditar['nombre'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold" style="color: #6b4531;">Unidad</label>
                            <input type="text" name="unidad" class="form-control" placeholder="lb, unidad, lt..." value="<?= htmlspecialchars($insumoEditar['unidad'] ?? 'lb') ?>" required>
                        </div>
                        <?php if (!$insumoEditar): ?>
                            <div class="col-md-2">
                                <label class="form-label small fw-semibold" style="color: #6b4531;">Stock inicial</label>
                                <input type="number" step="0.01" min="0" name="stock" class="form-control" value="0.00" required>
                            </div>
                        <?php endif; ?>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold" style="color: #6b4531;">Stock mínimo</label>
                            <input type="number" step="0.01" min="0" name="stock_minimo" class="form-control" value="<?= htmlspecialchars($insumoEditar['stock_minimo'] ?? '0.00') ?>" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold" style="color: #6b4531;">Costo ($)</label>
                            <input type="number" step="0.01" min="0" name="costo" class="form-control" value="<?= htmlspecialchars($insumoEditar['costo'] ?? '0.00') ?>" required>
                        </div>
                        <div class="col-md-<?= $insumoEditar ? '3' : '1' ?> d-flex gap-2">
                            <button type="submit" class="btn text-white fw-semibold" style="background: #4A2F20;">Guardar</button>
                            <?php if ($insumoEditar): ?>
                                <a href="InsumosController.php" class="btn btn-outline-secondary">Cancelar</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <?php if ($insumoMovimiento): ?>
            <div class="card p-3 mb-4" style="background: #FFFDF9; border: 1px solid #C98A3B; border-radius: 12px;">
                <h5 class="fw-bold mb-3" style="color: #4A2F20; font-family: Georgia, serif;">
                    Registrar movimiento: <?= htmlspecialchars($insumoMovimiento['nombre']) ?> (Actual: <?= number_format($insumoMovimiento['stock'], 2) ?> <?= htmlspecialchars($insumoMovimiento['unidad']) ?>)
                </h5>
                <form method="post" action="InsumosController.php">
                    <input type="hidden" name="accion" value="movimiento">
                    <input type="hidden" name="insumo_id" value="<?= (int)$insumoMovimiento['id'] ?>">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold" style="color: #6b4531;">Tipo</label>
                            <select name="tipo" class="form-select">
                                <option value="entrada">Entrada (compra / suma)</option>
                                <option value="salida">Salida (merma / resta)</option>
                                <option value="ajuste">Ajuste (fijar stock exacto)</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold" style="color: #6b4531;">Cantidad</label>
                            <input type="number" step="0.01" min="0.01" name="cantidad" class="form-control" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold" style="color: #6b4531;">Nota (opcional)</label>
                            <input type="text" name="nota" class="form-control" placeholder="Ej: Compra proveedor, ajuste físico...">
                        </div>
                        <div class="col-md-2 d-flex gap-2">
                            <button type="submit" class="btn text-white fw-semibold" style="background: #4A2F20;">Aplicar</button>
                            <a href="InsumosController.php" class="btn btn-outline-secondary">Cancelar</a>
                        </div>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <div class="ins-tabs mb-4 d-flex gap-2">
            <button class="ins-tab-btn active" data-target="tab-inventario">Inventario</button>
            <button class="ins-tab-btn" data-target="tab-movimientos">Últimos Movimientos</button>
        </div>

        <div class="ins-tab-content active" id="tab-inventario">
            <div class="card p-3 mb-4" style="background: #FFFDF9; border: 1px solid #EADFCE; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="color: #8A7565; font-size: 11.5px; text-transform: uppercase;">Insumo</th>
                                <th style="color: #8A7565; font-size: 11.5px; text-transform: uppercase;">Existencia</th>
                                <th class="text-center" style="color: #8A7565; font-size: 11.5px; text-transform: uppercase;">Mínimo</th>
                                <th class="text-center" style="color: #8A7565; font-size: 11.5px; text-transform: uppercase;">Costo</th>
                                <th style="color: #8A7565; font-size: 11.5px; text-transform: uppercase;">Estado</th>
                                <th class="text-end" style="color: #8A7565; font-size: 11.5px; text-transform: uppercase;">Acciones</th>
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
                                        <td><strong style="color: #4A2F20;"><?= htmlspecialchars($item['nombre']) ?></strong></td>
                                        <td>
                                            <div style="margin-bottom: 3px; font-weight: 500;"><?= number_format($stk, 2) ?> <?= htmlspecialchars($item['unidad']) ?></div>
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
                                                <a href="InsumosController.php?movimiento=<?= (int)$item['id'] ?>" class="btn btn-sm" style="background: #C98A3B; color: #2d1b10; font-weight: 600; font-size: 12px;">Movimiento</a>
                                                <a href="InsumosController.php?editar=<?= (int)$item['id'] ?>" class="btn btn-sm btn-outline-secondary" style="font-size: 12px;">Editar</a>
                                                <a href="InsumosController.php?toggle=<?= (int)$item['id'] ?><?= $verInactivos ? '&inactivos=1' : '' ?>" class="btn btn-sm btn-outline-danger ins-btn-danger" style="font-size: 12px;">
                                                    <?= $item['activo'] ? 'Desactivar' : 'Activar' ?>
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
            <div class="card p-3 mb-4" style="background: #FFFDF9; border: 1px solid #EADFCE; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                <?php if (empty($listaMovimientos)): ?>
                    <div class="text-muted text-center py-5" style="font-size: 14.5px;">
                        Sin movimientos todavía.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="color: #8A7565; font-size: 11.5px; text-transform: uppercase;">Fecha</th>
                                    <th style="color: #8A7565; font-size: 11.5px; text-transform: uppercase;">Insumo</th>
                                    <th style="color: #8A7565; font-size: 11.5px; text-transform: uppercase;">Tipo</th>
                                    <th class="text-end" style="color: #8A7565; font-size: 11.5px; text-transform: uppercase;">Cantidad</th>
                                    <th class="text-end" style="color: #8A7565; font-size: 11.5px; text-transform: uppercase;">Resultante</th>
                                    <th style="color: #8A7565; font-size: 11.5px; text-transform: uppercase;">Nota</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($listaMovimientos as $m): ?>
                                    <tr>
                                        <td class="small text-muted text-nowrap"><?= date('d/m/Y H:i', strtotime($m['fecha'])) ?></td>
                                        <td style="color: #4A2F20; font-weight: 600;"><?= htmlspecialchars($m['insumo']) ?></td>
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
