<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/panaderia/Config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/panaderia/Config/current_user.php';

$mensaje = '';
$tipo_alerta = '';

if (isset($_GET['toggle'])) {
    $id_toggle = (int)$_GET['toggle'];
    if ($id_toggle > 0) {
        $stmt = $pdo->prepare("UPDATE productos SET activo = NOT activo WHERE id = ?");
        $stmt->execute([$id_toggle]);
        header("Location: recetas.php" . (isset($_GET['inactivos']) ? '?inactivos=1' : ''));
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $nombre = trim($_POST['nombre'] ?? '');
    $precio = (float)($_POST['precio'] ?? 0);
    $base_insumo_id = (int)($_POST['base_insumo_id'] ?? 0);
    $rendimiento = (float)($_POST['rendimiento'] ?? 1);
    $ing_ids = $_POST['ing_id'] ?? [];
    $ing_cants = $_POST['ing_cant'] ?? [];

    if ($nombre !== '' && $base_insumo_id > 0 && $rendimiento > 0) {
        try {
            $pdo->beginTransaction();

            if ($accion === 'crear') {
                $stmtProd = $pdo->prepare("INSERT INTO productos (nombre, precio, base_insumo_id, rendimiento) VALUES (?, ?, ?, ?)");
                $stmtProd->execute([$nombre, $precio, $base_insumo_id, $rendimiento]);
                $producto_id = (int)$pdo->lastInsertId();
            } elseif ($accion === 'editar') {
                $producto_id = (int)($_POST['id'] ?? 0);
                $stmtProd = $pdo->prepare("UPDATE productos SET nombre = ?, precio = ?, base_insumo_id = ?, rendimiento = ? WHERE id = ?");
                $stmtProd->execute([$nombre, $precio, $base_insumo_id, $rendimiento, $producto_id]);
                $pdo->prepare("DELETE FROM receta_items WHERE producto_id = ?")->execute([$producto_id]);
            }

            if (!empty($producto_id)) {
                $stmtItem = $pdo->prepare("INSERT INTO receta_items (producto_id, insumo_id, cantidad) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE cantidad = VALUES(cantidad)");
                foreach ($ing_ids as $idx => $iid) {
                    $iid = (int)$iid;
                    $cant = (float)($ing_cants[$idx] ?? 0);
                    if ($iid > 0 && $iid !== $base_insumo_id && $cant > 0) {
                        $stmtItem->execute([$producto_id, $iid, $cant]);
                    }
                }
            }

            $pdo->commit();
            header("Location: recetas.php?ok=" . ($accion === 'editar' ? 'editado' : 'creado'));
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $mensaje = "Error al guardar la receta: " . $e->getMessage();
            $tipo_alerta = 'danger';
        }
    } else {
        $mensaje = "Nombre, materia base y rendimiento son obligatorios.";
        $tipo_alerta = 'warning';
    }
}

if (isset($_GET['ok'])) {
    $mensaje = $_GET['ok'] === 'editado' ? 'Receta actualizada correctamente.' : 'Producto y receta creados correctamente.';
    $tipo_alerta = 'success';
}

$verInactivos = isset($_GET['inactivos']);
$mostrarNuevo = isset($_GET['nuevo']);
$editarId = (int)($_GET['editar'] ?? 0);

$insumosActivos = $pdo->query("SELECT id, nombre, unidad, costo FROM insumos WHERE activo = 1 ORDER BY nombre ASC")->fetchAll();

$productoEditar = null;
$itemsEditar = [];
if ($editarId > 0) {
    $stmtEd = $pdo->prepare("SELECT * FROM productos WHERE id = ?");
    $stmtEd->execute([$editarId]);
    $productoEditar = $stmtEd->fetch();

    if ($productoEditar) {
        $stmtIt = $pdo->prepare("SELECT insumo_id, cantidad FROM receta_items WHERE producto_id = ?");
        $stmtIt->execute([$editarId]);
        $itemsEditar = $stmtIt->fetchAll();
    }
}

$sqlProductos = "
    SELECT p.*, i.nombre AS base_nombre, i.unidad AS base_unidad, i.costo AS base_costo
    FROM productos p
    JOIN insumos i ON p.base_insumo_id = i.id
    " . ($verInactivos ? "" : "WHERE p.activo = 1 ") . "
    ORDER BY p.activo DESC, p.nombre ASC
";
$productos = $pdo->query($sqlProductos)->fetchAll();

$recetasItemsRaw = $pdo->query("
    SELECT ri.producto_id, ri.cantidad, i.nombre, i.unidad, i.costo
    FROM receta_items ri
    JOIN insumos i ON ri.insumo_id = i.id
")->fetchAll();

$itemsPorProducto = [];
foreach ($recetasItemsRaw as $ri) {
    $itemsPorProducto[(int)$ri['producto_id']][] = $ri;
}

$pagina_activa = 'recetas';
include_once __DIR__ . '/../Layouts/header.php';
?>

<link rel="stylesheet" href="/panaderia/Assets/css/siderbar.css">
<link rel="stylesheet" href="/panaderia/Assets/css/recetas.css">

<div class="d-flex">
    <?php include_once __DIR__ . '/../Layouts/sidebar.php'; ?>

    <div class="main flex-grow-1 p-4">
        <div class="rec-header-section mb-4">
            <div class="d-flex justify-content-between align-items-baseline flex-wrap gap-2">
                <div>
                    <h2 class="fw-bold" style="color: #6B4226;">Recetas y productos</h2>
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
            <div class="alert alert-<?= $tipo_alerta ?> alert-dismissible fade show py-2 px-3 mb-3" role="alert" style="font-size: 13.5px; border-radius: 10px;">
                <?= htmlspecialchars($mensaje) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar" style="padding: 0.65rem;"></button>
            </div>
        <?php endif; ?>

        <div class="mb-4 d-flex gap-2 flex-wrap align-items-center">
            <a class="ins-btn ins-btn-primary" href="<?= $mostrarNuevo ? 'recetas.php' : 'recetas.php?nuevo=1' ?>" style="background: #C98A3B; color: #2d1b10; border-radius: 10px; padding: 9px 18px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                <?= $mostrarNuevo ? 'Cerrar formulario' : 'Nuevo producto' ?>
            </a>
            <a class="btn btn-outline-secondary" href="recetas.php<?= $verInactivos ? '' : '?inactivos=1' ?>" style="border-radius: 10px; padding: 8px 16px; font-weight: 600; font-size: 14px;">
                <?= $verInactivos ? 'Ocultar inactivos' : 'Mostrar inactivos' ?>
            </a>
        </div>

        <?php if ($mostrarNuevo || $productoEditar): ?>
            <div class="rec-card mb-4">
                <h3 class="rec-product-title mb-3">
                    <?= $productoEditar ? 'Editar receta: ' . htmlspecialchars($productoEditar['nombre']) : 'Nuevo producto y receta' ?>
                </h3>
                <form method="post" action="recetas.php">
                    <input type="hidden" name="accion" value="<?= $productoEditar ? 'editar' : 'crear' ?>">
                    <?php if ($productoEditar): ?>
                        <input type="hidden" name="id" value="<?= (int)$productoEditar['id'] ?>">
                    <?php endif; ?>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold" style="color: #6b4531;">Nombre del producto</label>
                            <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($productoEditar['nombre'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold" style="color: #6b4531;">Precio venta ($)</label>
                            <input type="number" step="0.01" min="0" name="precio" class="form-control" value="<?= htmlspecialchars($productoEditar['precio'] ?? '0.00') ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold" style="color: #6b4531;">Materia base (1 unidad)</label>
                            <select name="base_insumo_id" class="form-select" required>
                                <?php foreach ($insumosActivos as $ins): ?>
                                    <option value="<?= (int)$ins['id'] ?>" <?= ($productoEditar && (int)$productoEditar['base_insumo_id'] === (int)$ins['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($ins['nombre']) ?> (<?= htmlspecialchars($ins['unidad']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold" style="color: #6b4531;">Rinde (piezas por base)</label>
                            <input type="number" step="0.01" min="0.01" name="rendimiento" class="form-control" value="<?= htmlspecialchars($productoEditar['rendimiento'] ?? '1.00') ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label small fw-semibold mb-0" style="color: #6b4531;">Ingredientes adicionales (por 1 unidad base)</label>
                            <button type="button" id="btnAddIngrediente" class="btn btn-sm btn-outline-secondary" style="border-radius: 8px; font-size: 12px;">+ Agregar ingrediente</button>
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
                                            <button type="button" class="btn btn-sm btn-outline-danger w-100 js-remove-ing">Quitar</button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn text-white px-4 py-2" style="background: #4A2F20; border-radius: 10px; font-weight: 600;">Guardar receta</button>
                        <a href="recetas.php" class="btn btn-outline-secondary px-4 py-2" style="border-radius: 10px;">Cancelar</a>
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
                            <button type="button" class="btn btn-sm btn-outline-danger w-100 js-remove-ing">Quitar</button>
                        </div>
                    </div>
                </template>
            </div>
        <?php endif; ?>

        <div class="rec-grid mb-4">
            <?php if (empty($productos)): ?>
                <div class="rec-card text-center text-muted py-5">No hay productos o recetas registradas.</div>
            <?php else: ?>
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
                    ?>
                    <div class="rec-card <?= $p['activo'] ? '' : 'opacity-50' ?>">
                        <div class="rec-card-header">
                            <h3 class="rec-product-title">
                                <?= htmlspecialchars($p['nombre']) ?>
                                <?php if (!$p['activo']): ?>
                                    <span class="badge bg-secondary-subtle text-secondary ms-1" style="font-size: 11px;">Inactivo</span>
                                <?php endif; ?>
                            </h3>
                            <div class="rec-price-tag"><strong>$<?= number_format($precioPieza, 2) ?></strong> / pieza</div>
                        </div>

                        <div class="rec-details-grid mb-3">
                            <div class="rec-row-info">
                                <span class="text-muted">Rinde</span>
                                <span><strong><?= rtrim(rtrim(number_format($rend, 2), '0'), '.') ?> piezas</strong> por <?= htmlspecialchars($p['base_unidad']) ?> de <?= htmlspecialchars(mb_strtolower($p['base_nombre'])) ?></span>
                            </div>
                            <div class="rec-row-info">
                                <span class="text-muted">Costo estimado</span>
                                <span><strong>$<?= number_format($costoPieza, 2) ?></strong> por pieza</span>
                            </div>
                            <div class="rec-row-info">
                                <span class="text-muted">Margen</span>
                                <span class="<?= $margen >= 0 ? 'text-success' : 'text-danger' ?> fw-bold"><?= $margen ?>%</span>
                            </div>
                        </div>

                        <hr style="border-color: #EADFCE; opacity: 1; margin: 14px 0;">

                        <ul class="rec-ingredients-list mb-4">
                            <li>1 <?= htmlspecialchars($p['base_unidad']) ?> de <?= htmlspecialchars(mb_strtolower($p['base_nombre'])) ?> <span class="rec-base-label">(base)</span></li>
                            <?php foreach ($items as $it): ?>
                                <li><?= rtrim(rtrim(number_format((float)$it['cantidad'], 2), '0'), '.') ?> <?= htmlspecialchars($it['unidad']) ?> de <?= htmlspecialchars(mb_strtolower($it['nombre'])) ?></li>
                            <?php endforeach; ?>
                        </ul>

                        <div class="rec-card-actions">
                            <a href="recetas.php?editar=<?= $pid ?>" class="btn btn-sm px-3 py-1.5" style="background: #C98A3B; color: #2d1b10; font-weight: 600; border-radius: 8px;">Editar</a>
                            <a href="recetas.php?toggle=<?= $pid ?><?= $verInactivos ? '&inactivos=1' : '' ?>" class="btn btn-sm btn-outline-danger px-3 py-1.5 js-toggle-receta" style="border-radius: 8px; color: #b3392f; border-color: #eec5c2;">
                                <?= $p['activo'] ? 'Desactivar' : 'Activar' ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php include_once __DIR__ . '/../Layouts/footer.php'; ?>
    </div>
</div>

<script src="/panaderia/Assets/js/recetas.js"></script>