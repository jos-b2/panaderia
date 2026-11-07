<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/panaderia/Config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/panaderia/Config/current_user.php';

$mensaje = '';
$tipo_alerta = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fecha = $_POST['fecha_arqueo'] ?? date('Y-m-d');
    $vendidas = $_POST['vendidas'] ?? [];
    $sobrante = $_POST['sobrante'] ?? [];
    $dinero_contado = (isset($_POST['dinero_contado']) && $_POST['dinero_contado'] !== '') ? (float)$_POST['dinero_contado'] : null;
    $observaciones = trim($_POST['observaciones'] ?? '');

    try {
        $pdo->beginTransaction();

        $stmtDet = $pdo->prepare("
            INSERT INTO arqueo_detalle (fecha, producto_id, vendidas, sobrante)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE vendidas = VALUES(vendidas), sobrante = VALUES(sobrante)
        ");

        foreach ($vendidas as $pid => $cant_v) {
            $pid = (int)$pid;
            $v = max(0, (int)$cant_v);
            $s = max(0, (int)($sobrante[$pid] ?? 0));
            $stmtDet->execute([$fecha, $pid, $v, $s]);
        }

        $stmtCaja = $pdo->prepare("
            INSERT INTO arqueo_caja (fecha, caja_real, nota)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE caja_real = VALUES(caja_real), nota = VALUES(nota)
        ");
        $stmtCaja->execute([$fecha, $dinero_contado, $observaciones !== '' ? $observaciones : null]);

        $pdo->commit();
        header("Location: arqueo.php?fecha=" . urlencode($fecha) . "&ok=1");
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        $mensaje = "Error al guardar el arqueo: " . $e->getMessage();
        $tipo_alerta = 'danger';
    }
}

if (isset($_GET['ok'])) {
    $mensaje = "Arqueo guardado correctamente.";
    $tipo_alerta = 'success';
}

$fecha_sel = $_GET['fecha'] ?? date('Y-m-d');
$fecha_ayer = date('Y-m-d', strtotime($fecha_sel . ' -1 day'));

$productos = $pdo->query("SELECT id, nombre, precio FROM productos WHERE activo = 1 ORDER BY nombre ASC")->fetchAll();

$stmtAyer = $pdo->prepare("SELECT producto_id, sobrante FROM arqueo_detalle WHERE fecha = ?");
$stmtAyer->execute([$fecha_ayer]);
$sobranteAyer = $stmtAyer->fetchAll(PDO::FETCH_KEY_PAIR);

$stmtHorn = $pdo->prepare("SELECT producto_id, COALESCE(SUM(piezas), 0) FROM producciones WHERE fecha = ? AND anulada = 0 GROUP BY producto_id");
$stmtHorn->execute([$fecha_sel]);
$horneadasHoy = $stmtHorn->fetchAll(PDO::FETCH_KEY_PAIR);

$stmtHoy = $pdo->prepare("SELECT producto_id, vendidas, sobrante FROM arqueo_detalle WHERE fecha = ?");
$stmtHoy->execute([$fecha_sel]);
$detalleHoy = [];
foreach ($stmtHoy->fetchAll() as $row) {
    $detalleHoy[(int)$row['producto_id']] = $row;
}

$stmtCajaHoy = $pdo->prepare("SELECT caja_real, nota FROM arqueo_caja WHERE fecha = ?");
$stmtCajaHoy->execute([$fecha_sel]);
$cajaHoy = $stmtCajaHoy->fetch() ?: ['caja_real' => '', 'nota' => ''];

$historial = $pdo->query("
    SELECT ac.fecha, ac.caja_real, ac.nota,
           COALESCE(SUM(ad.vendidas * p.precio), 0) AS esperado
    FROM arqueo_caja ac
    LEFT JOIN arqueo_detalle ad ON ac.fecha = ad.fecha
    LEFT JOIN productos p ON ad.producto_id = p.id
    GROUP BY ac.fecha, ac.caja_real, ac.nota
    ORDER BY ac.fecha DESC
    LIMIT 15
")->fetchAll();

$pagina_activa = 'arqueo';
include_once __DIR__ . '/../Layouts/header.php';
?>

<link rel="stylesheet" href="/panaderia/Assets/css/siderbar.css">
<link rel="stylesheet" href="/panaderia/Assets/css/arqueo.css">

<div class="d-flex">
    <?php include_once __DIR__ . '/../Layouts/sidebar.php'; ?>

    <div class="main flex-grow-1 p-4">
        <div class="arq-header-section mb-4">
            <div class="d-flex justify-content-between align-items-baseline flex-wrap gap-2">
                <h2 class="fw-bold" style="color: #6B4226; font-family: Georgia, serif; font-size: 28px; margin-bottom: 4px;">Arqueo diario</h2>
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

        <div class="arq-tabs mb-4 d-flex gap-2">
            <button type="button" class="arq-tab-btn active" data-target="tab-cuadre">Cuadre de Productos</button>
            <button type="button" class="arq-tab-btn" data-target="tab-cierre">Ingresos y Cierre</button>
            <button type="button" class="arq-tab-btn" data-target="tab-historial">Historial</button>
        </div>

        <form method="post" action="arqueo.php">
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
                                    <th style="color: #8A7565; font-size: 11.5px; text-transform: uppercase;">Producto</th>
                                    <th class="text-center" style="color: #8A7565; font-size: 11.5px; text-transform: uppercase;">Sobrante ayer</th>
                                    <th class="text-center" style="color: #8A7565; font-size: 11.5px; text-transform: uppercase;">Horneadas hoy</th>
                                    <th class="text-center" style="color: #8A7565; font-size: 11.5px; text-transform: uppercase;">Vendidas</th>
                                    <th class="text-center" style="color: #8A7565; font-size: 11.5px; text-transform: uppercase;">Sobrante hoy (contado)</th>
                                    <th class="text-center" style="color: #8A7565; font-size: 11.5px; text-transform: uppercase;">Diferencia</th>
                                    <th class="text-end" style="color: #8A7565; font-size: 11.5px; text-transform: uppercase;">Valor</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($productos)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">No hay productos activos en la base de datos.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($productos as $p): ?>
                                        <?php
                                            $pid = (int)$p['id'];
                                            $precio = (float)$p['precio'];
                                            $ayer = (int)($sobranteAyer[$pid] ?? 0);
                                            $horn = (int)($horneadasHoy[$pid] ?? 0);
                                            $vend = (int)($detalleHoy[$pid]['vendidas'] ?? 0);
                                            $sobr = (int)($detalleHoy[$pid]['sobrante'] ?? 0);
                                            $dif = $ayer + $horn - $vend - $sobr;
                                            $val = $vend * $precio;
                                        ?>
                                        <tr class="arq-row" data-precio="<?= $precio ?>" data-ayer="<?= $ayer ?>" data-horn="<?= $horn ?>">
                                            <td>
                                                <strong style="color: #4A2F20; display: block;"><?= htmlspecialchars($p['nombre']) ?></strong>
                                                <span class="text-muted" style="font-size: 12px;">$<?= number_format($precio, 2) ?> c/u</span>
                                            </td>
                                            <td class="text-center" style="color: #4A2F20;"><?= $ayer ?></td>
                                            <td class="text-center" style="color: #4A2F20;"><?= $horn ?></td>
                                            <td class="text-center">
                                                <input type="number" min="0" name="vendidas[<?= $pid ?>]" class="form-control arq-table-input mx-auto js-arq-vend" value="<?= $vend ?>">
                                            </td>
                                            <td class="text-center">
                                                <input type="number" min="0" name="sobrante[<?= $pid ?>]" class="form-control arq-table-input mx-auto js-arq-sobr" value="<?= $sobr ?>">
                                            </td>
                                            <td class="text-center js-arq-dif" style="color: #4A2F20;"><?= $dif ?></td>
                                            <td class="text-end js-arq-val" style="color: #4A2F20; font-weight: 600;">$<?= number_format($val, 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="text-muted" style="font-size: 12px; line-height: 1.5;">
                        Diferencia = sobrante de ayer + horneadas – vendidas – sobrante contado. Positiva: pan que no aparece (merma o faltante). Negativa: excedente (revise si faltó anotar una tanda).
                    </div>
                </div>
            </div>

            <div class="arq-tab-content" id="tab-cierre">
                <div class="arq-card mb-4">
                    <h3 class="arq-card-title mb-4">Resumen de ingresos</h3>
                    
                    <div class="arq-summary-grid mb-4">
                        <div class="arq-sub-card">
                            <span class="arq-sub-label">Ingreso esperado</span>
                            <strong class="arq-sub-val" id="resEsperado">$0.00</strong>
                            <span class="arq-sub-desc">vendidas × precio</span>
                        </div>
                        <div class="arq-sub-card arq-sub-warning">
                            <span class="arq-sub-label">Valor de la diferencia</span>
                            <strong class="arq-sub-val" id="resDiferencia">$0.00</strong>
                            <span class="arq-sub-desc">pérdida estimada si es positiva</span>
                        </div>
                        <div class="arq-sub-card arq-sub-info">
                            <span class="arq-sub-label">Descuadre de caja</span>
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

                    <button type="submit" class="btn text-white px-4 py-2" style="background: #4A2F20; border-radius: 10px; font-weight: 600;">
                        Guardar arqueo
                    </button>
                </div>
            </div>
        </form>

        <div class="arq-tab-content" id="tab-historial">
            <div class="arq-card mb-4">
                <h3 class="arq-card-title">Historial reciente</h3>
                <?php if (empty($historial)): ?>
                    <div class="text-muted text-center py-5" style="font-size: 14px;">
                        Aún no hay arqueos guardados.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="color: #8A7565; font-size: 11.5px; text-transform: uppercase;">Fecha</th>
                                    <th class="text-end" style="color: #8A7565; font-size: 11.5px; text-transform: uppercase;">Esperado</th>
                                    <th class="text-end" style="color: #8A7565; font-size: 11.5px; text-transform: uppercase;">En caja</th>
                                    <th style="color: #8A7565; font-size: 11.5px; text-transform: uppercase;">Observaciones</th>
                                    <th class="text-end" style="color: #8A7565; font-size: 11.5px; text-transform: uppercase;">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($historial as $h): ?>
                                    <tr>
                                        <td style="color: #4A2F20; font-weight: 600;"><?= date('d/m/Y', strtotime($h['fecha'])) ?></td>
                                        <td class="text-end">$<?= number_format((float)$h['esperado'], 2) ?></td>
                                        <td class="text-end"><?= $h['caja_real'] !== null ? '$' . number_format((float)$h['caja_real'], 2) : '—' ?></td>
                                        <td class="small text-muted"><?= htmlspecialchars($h['nota'] ?? '-') ?></td>
                                        <td class="text-end">
                                            <a href="arqueo.php?fecha=<?= urlencode($h['fecha']) ?>" class="btn btn-sm btn-outline-secondary" style="font-size: 12px;">Ver</a>
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

<script src="/panaderia/Assets/js/arqueo.js"></script>