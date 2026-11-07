<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/panaderia/Config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/panaderia/Config/current_user.php';

$mensaje = '';
$tipo_alerta = '';
$usuario_id = $_SESSION['usuario_id'] ?? ($_SESSION['id'] ?? null);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? 'registrar';

    if ($accion === 'registrar') {
        $fecha_raw = trim($_POST['fecha'] ?? date('Y-m-d'));
        $fecha = preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $fecha_raw)
            ? DateTime::createFromFormat('d/m/Y', $fecha_raw)->format('Y-m-d')
            : $fecha_raw;

        $producto_id = (int)($_POST['producto_id'] ?? 0);
        $base_cantidad = (float)($_POST['cantidad_base'] ?? 0);
        $piezas = (int)($_POST['piezas'] ?? 0);
        $nota = trim($_POST['nota'] ?? '');
        $forzar_stock = isset($_POST['forzar_stock']);

        if ($producto_id > 0 && $base_cantidad > 0 && $piezas > 0) {
            try {
                $pdo->beginTransaction();

                $stmtProd = $pdo->prepare("SELECT id, nombre, base_insumo_id FROM productos WHERE id = ? AND activo = 1");
                $stmtProd->execute([$producto_id]);
                $prod = $stmtProd->fetch();

                if (!$prod) {
                    throw new Exception("Producto no encontrado.");
                }

                $consumos = [];
                $consumos[$prod['base_insumo_id']] = $base_cantidad;

                $stmtRec = $pdo->prepare("SELECT insumo_id, cantidad FROM receta_items WHERE producto_id = ?");
                $stmtRec->execute([$producto_id]);
                foreach ($stmtRec->fetchAll() as $r) {
                    $iid = (int)$r['insumo_id'];
                    $cant = (float)$r['cantidad'] * $base_cantidad;
                    $consumos[$iid] = ($consumos[$iid] ?? 0) + $cant;
                }

                if (!$forzar_stock) {
                    foreach ($consumos as $iid => $cant_necesaria) {
                        $stmtIns = $pdo->prepare("SELECT nombre, stock, unidad FROM insumos WHERE id = ?");
                        $stmtIns->execute([$iid]);
                        $ins = $stmtIns->fetch();
                        if ($ins && (float)$ins['stock'] < $cant_necesaria) {
                            throw new Exception("Stock insuficiente de {$ins['nombre']} (hay {$ins['stock']} {$ins['unidad']}, se necesitan " . number_format($cant_necesaria, 2) . ").");
                        }
                    }
                }

                $stmtInsert = $pdo->prepare("INSERT INTO producciones (fecha, producto_id, base_cantidad, piezas, nota, usuario_id) VALUES (?, ?, ?, ?, ?, ?)");
                $stmtInsert->execute([$fecha, $producto_id, $base_cantidad, $piezas, $nota !== '' ? $nota : null, $usuario_id]);
                $produccion_id = $pdo->lastInsertId();

                foreach ($consumos as $iid => $cant_usada) {
                    $stmtPC = $pdo->prepare("INSERT INTO produccion_consumo (produccion_id, insumo_id, cantidad) VALUES (?, ?, ?)");
                    $stmtPC->execute([$produccion_id, $iid, $cant_usada]);

                    $stmtStk = $pdo->prepare("SELECT stock FROM insumos WHERE id = ? FOR UPDATE");
                    $stmtStk->execute([$iid]);
                    $stock_actual = (float)$stmtStk->fetchColumn();
                    $nuevo_stock = max(0, $stock_actual - $cant_usada);

                    $pdo->prepare("UPDATE insumos SET stock = ? WHERE id = ?")->execute([$nuevo_stock, $iid]);

                    $stmtMov = $pdo->prepare("INSERT INTO movimientos (insumo_id, tipo, cantidad, stock_resultante, nota, usuario_id) VALUES (?, 'salida', ?, ?, ?, ?)");
                    $stmtMov->execute([$iid, $cant_usada, $nuevo_stock, "Producción #{$produccion_id} ({$prod['nombre']})", $usuario_id]);
                }

                $pdo->commit();
                header("Location: produccion.php?ok=1");
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $mensaje = $e->getMessage();
                $tipo_alerta = 'danger';
            }
        } else {
            $mensaje = "Selecciona un producto e ingresa cantidades válidas mayores a 0.";
            $tipo_alerta = 'warning';
        }
    } elseif ($accion === 'anular') {
        $produccion_id = (int)($_POST['produccion_id'] ?? 0);
        if ($produccion_id > 0) {
            try {
                $pdo->beginTransaction();

                $stmtCheck = $pdo->prepare("SELECT id, anulada FROM producciones WHERE id = ? FOR UPDATE");
                $stmtCheck->execute([$produccion_id]);
                $tanda = $stmtCheck->fetch();

                if ($tanda && !$tanda['anulada']) {
                    $pdo->prepare("UPDATE producciones SET anulada = 1 WHERE id = ?")->execute([$produccion_id]);

                    $stmtCons = $pdo->prepare("SELECT insumo_id, cantidad FROM produccion_consumo WHERE produccion_id = ?");
                    $stmtCons->execute([$produccion_id]);
                    foreach ($stmtCons->fetchAll() as $c) {
                        $stmtStk = $pdo->prepare("SELECT stock FROM insumos WHERE id = ? FOR UPDATE");
                        $stmtStk->execute([$c['insumo_id']]);
                        $stock_actual = (float)$stmtStk->fetchColumn();
                        $nuevo_stock = $stock_actual + (float)$c['cantidad'];

                        $pdo->prepare("UPDATE insumos SET stock = ? WHERE id = ?")->execute([$nuevo_stock, $c['insumo_id']]);

                        $stmtMov = $pdo->prepare("INSERT INTO movimientos (insumo_id, tipo, cantidad, stock_resultante, nota, usuario_id) VALUES (?, 'entrada', ?, ?, ?, ?)");
                        $stmtMov->execute([$c['insumo_id'], $c['cantidad'], $nuevo_stock, "Anulación de tanda #{$produccion_id}", $usuario_id]);
                    }
                }

                $pdo->commit();
                header("Location: produccion.php?ok=anulada");
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $mensaje = "Error al anular la tanda: " . $e->getMessage();
                $tipo_alerta = 'danger';
            }
        }
    }
}

if (isset($_GET['ok'])) {
    $mensaje = $_GET['ok'] === 'anulada' ? 'Tanda anulada y existencias restauradas.' : 'Tanda de producción registrada correctamente.';
    $tipo_alerta = 'success';
}

$productos = $pdo->query("
    SELECT p.id, p.nombre, p.base_insumo_id, p.rendimiento,
           i.nombre AS base_nombre, i.unidad AS base_unidad, i.stock AS base_stock
    FROM productos p
    JOIN insumos i ON p.base_insumo_id = i.id
    WHERE p.activo = 1
    ORDER BY p.nombre ASC
")->fetchAll();

$recetasRaw = $pdo->query("
    SELECT ri.producto_id, ri.insumo_id, ri.cantidad,
           i.nombre, i.unidad, i.stock
    FROM receta_items ri
    JOIN insumos i ON ri.insumo_id = i.id
")->fetchAll();

$recetasPorProducto = [];
foreach ($recetasRaw as $r) {
    $pid = (int)$r['producto_id'];
    $recetasPorProducto[$pid][] = [
        'insumo_id' => (int)$r['insumo_id'],
        'nombre' => $r['nombre'],
        'unidad' => $r['unidad'],
        'stock' => (float)$r['stock'],
        'cantidad' => (float)$r['cantidad']
    ];
}

$dataProductos = [];
foreach ($productos as $p) {
    $pid = (int)$p['id'];
    $dataProductos[$pid] = [
        'id' => $pid,
        'nombre' => $p['nombre'],
        'rendimiento' => (float)$p['rendimiento'],
        'base' => [
            'insumo_id' => (int)$p['base_insumo_id'],
            'nombre' => $p['base_nombre'],
            'unidad' => $p['base_unidad'],
            'stock' => (float)$p['base_stock']
        ],
        'items' => $recetasPorProducto[$pid] ?? []
    ];
}

$ultimasTandas = $pdo->query("
    SELECT pr.id, pr.fecha, pr.base_cantidad, pr.piezas, pr.nota, pr.anulada,
           p.nombre AS producto, i.unidad AS base_unidad
    FROM producciones pr
    JOIN productos p ON pr.producto_id = p.id
    JOIN insumos i ON p.base_insumo_id = i.id
    ORDER BY pr.id DESC
    LIMIT 10
")->fetchAll();

$pagina_activa = 'produccion';
include_once __DIR__ . '/../Layouts/header.php';
?>

<link rel="stylesheet" href="/panaderia/Assets/css/siderbar.css">
<link rel="stylesheet" href="/panaderia/Assets/css/produccion.css">

<div class="d-flex">
    <?php include_once __DIR__ . '/../Layouts/sidebar.php'; ?>

    <div class="main flex-grow-1 p-4">
        <div class="prod-header-section mb-3">
            <div class="d-flex justify-content-between align-items-baseline flex-wrap gap-2">
                <h2 class="fw-bold" style="color: #6B4226; font-family: Georgia, serif; font-size: 28px; margin-bottom: 0;">Producción</h2>
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

        <div class="prod-grid-top mb-4">
            <form method="post" action="produccion.php" id="formProduccion" class="d-flex flex-column gap-3" data-productos='<?= htmlspecialchars(json_encode($dataProductos), ENT_QUOTES, 'UTF-8') ?>'>
                <input type="hidden" name="accion" value="registrar">

                <div class="prod-card" style="padding: 16px;">
                    <h3 class="prod-card-title ps-1">Registrar tanda de horneado</h3>
                    
                    <div class="row g-2 mb-2">
                        <div class="col-md-6">
                            <label class="prod-form-label">Fecha</label>
                            <input type="date" name="fecha" class="form-control prod-input" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="prod-form-label">Producto</label>
                            <select name="producto_id" id="prodSelect" class="form-select prod-input" required>
                                <?php if (empty($productos)): ?>
                                    <option value="">Sin productos activos</option>
                                <?php else: ?>
                                    <?php foreach ($productos as $p): ?>
                                        <option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-md-6">
                            <label class="prod-form-label" id="lblBaseInsumo">Insumo base</label>
                            <input type="number" step="any" min="0.01" name="cantidad_base" id="inputCantidadBase" class="form-control prod-input" placeholder="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="prod-form-label">Piezas horneadas</label>
                            <input type="number" min="1" name="piezas" id="inputPiezas" class="form-control prod-input" placeholder="0" required>
                        </div>
                    </div>

                    <div>
                        <label class="prod-form-label">Nota (opcional)</label>
                        <input type="text" name="nota" class="form-control prod-input" placeholder="Ej. tanda de la mañana">
                    </div>
                </div>

                <div class="prod-card" style="padding: 16px;">
                    <label class="prod-form-label mb-2" style="font-size: 13px;">Consumo estimado de insumos</label>
                    <div class="table-responsive mb-3 border rounded" style="border-color: #EADFCE !important;">
                        <table class="table table-sm align-middle mb-0">
                            <thead style="background-color: #fcf9f4;">
                                <tr>
                                    <th class="ps-3 py-1" style="color: #8A7565; font-size: 11px; text-transform: uppercase;">Insumo</th>
                                    <th class="py-1" style="color: #8A7565; font-size: 11px; text-transform: uppercase;">Se usará</th>
                                    <th class="py-1" style="color: #8A7565; font-size: 11px; text-transform: uppercase;">Hay</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyConsumo">
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-3" style="font-size: 12.5px;">Selecciona un producto e ingresa la cantidad base.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" name="forzar_stock" id="forzarStock">
                            <label class="form-check-label text-muted" for="forzarStock" style="font-size: 12px; margin-top: 2px;">
                                Forzar registro sin stock
                            </label>
                        </div>
                        <button type="submit" class="btn text-white px-3 py-1" style="background: #4A2F20; border-radius: 8px; font-weight: 600; font-size: 13px; display: inline-flex; align-items: center; gap: 6px;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                            Registrar
                        </button>
                    </div>
                </div>
            </form>

            <div class="d-flex flex-column gap-3">
                <div class="prod-card" style="padding: 16px;">
                    <h3 class="prod-card-title">¿Cómo funciona?</h3>
                    <p class="text-muted mb-0" style="font-size: 12.5px; line-height: 1.5;">
                        El sistema calcula el consumo de cada insumo según la receta y lo descuenta del inventario. Las piezas pasan al <a href="arqueo.php" style="color: #C98A3B; font-weight: 600; text-decoration: none;">arqueo diario</a> para compararse con lo vendido. Si hay un error, puede anular la tanda para restaurar existencias.
                    </p>
                </div>

                <div class="prod-card flex-grow-1" style="padding: 16px;">
                    <h3 class="prod-card-title">Últimas tandas</h3>
                    <?php if (empty($ultimasTandas)): ?>
                        <div class="text-muted text-center py-4" style="font-size: 13px;">
                            Aún no hay tandas registradas.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0" style="font-size: 12.5px;">
                                <thead>
                                    <tr>
                                        <th style="color: #8A7565; font-size: 11px;">Producto</th>
                                        <th class="text-end" style="color: #8A7565; font-size: 11px;">Piezas</th>
                                        <th class="text-end" style="color: #8A7565; font-size: 11px;">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ultimasTandas as $t): ?>
                                        <tr class="<?= $t['anulada'] ? 'opacity-50' : '' ?>">
                                            <td>
                                                <strong style="color: #4A2F20;"><?= htmlspecialchars($t['producto']) ?></strong>
                                                <div class="text-muted" style="font-size: 11px;">
                                                    <?= date('d/m', strtotime($t['fecha'])) ?> · <?= number_format((float)$t['base_cantidad'], 2) ?> <?= htmlspecialchars($t['base_unidad']) ?>
                                                </div>
                                            </td>
                                            <td class="text-end fw-semibold" style="color: #4A2F20;">
                                                <?= (int)$t['piezas'] ?>
                                            </td>
                                            <td class="text-end">
                                                <?php if ($t['anulada']): ?>
                                                    <span class="badge bg-secondary-subtle text-secondary" style="font-size: 10.5px;">Anulada</span>
                                                <?php else: ?>
                                                    <form method="post" action="produccion.php" class="m-0 d-inline">
                                                        <input type="hidden" name="accion" value="anular">
                                                        <input type="hidden" name="produccion_id" value="<?= (int)$t['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2 js-confirm-anular" style="font-size: 11px; border-radius: 6px;">Anular</button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php include_once __DIR__ . '/../Layouts/footer.php'; ?>
    </div>
</div>

<script src="/panaderia/Assets/js/produccion.js"></script>