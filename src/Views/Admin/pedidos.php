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
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'crear') {
        $cliente_nombre = trim($_POST['cliente_nombre'] ?? '');
        $cliente_tel = trim($_POST['cliente_telefono'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $fecha_entrega = $_POST['fecha_entrega'] ?? '';
        $total = (float)($_POST['total'] ?? 0);
        $abono_inicial = (float)($_POST['abono_inicial'] ?? 0);
        $nota = trim($_POST['nota'] ?? '');

        if ($cliente_nombre !== '' && $descripcion !== '' && $fecha_entrega !== '') {
            try {
                $pdo->beginTransaction();

                $stmtCli = $pdo->prepare("SELECT id FROM clientes WHERE nombre = ? LIMIT 1");
                $stmtCli->execute([$cliente_nombre]);
                $cliente_id = $stmtCli->fetchColumn();

                if (!$cliente_id) {
                    $stmtNewCli = $pdo->prepare("INSERT INTO clientes (nombre, telefono) VALUES (?, ?)");
                    $stmtNewCli->execute([$cliente_nombre, $cliente_tel !== '' ? $cliente_tel : null]);
                    $cliente_id = $pdo->lastInsertId();
                }

                $stmtPed = $pdo->prepare("INSERT INTO pedidos (cliente_id, descripcion, fecha_entrega, total, estado, nota, usuario_id) VALUES (?, ?, ?, ?, 'pendiente', ?, ?)");
                $stmtPed->execute([$cliente_id, $descripcion, $fecha_entrega, $total, $nota !== '' ? $nota : null, $usuario_id]);
                $pedido_id = $pdo->lastInsertId();

                if ($abono_inicial > 0) {
                    $stmtAb = $pdo->prepare("INSERT INTO abonos (pedido_id, monto, nota) VALUES (?, ?, 'Abono inicial')");
                    $stmtAb->execute([$pedido_id, $abono_inicial]);
                }

                $pdo->commit();
                header("Location: pedidos.php?ok=1");
                exit;
            } catch (PDOException $e) {
                $pdo->rollBack();
                $mensaje = "Error al guardar el pedido: " . $e->getMessage();
                $tipo_alerta = 'danger';
            }
        } else {
            $mensaje = "Cliente, descripción y fecha de entrega son obligatorios.";
            $tipo_alerta = 'warning';
        }
    } elseif ($accion === 'cambiar_estado') {
        $pedido_id = (int)($_POST['pedido_id'] ?? 0);
        $nuevo_estado = $_POST['estado'] ?? '';
        $estados_validos = ['pendiente', 'en_produccion', 'listo', 'entregado', 'cancelado'];

        if ($pedido_id > 0 && in_array($nuevo_estado, $estados_validos, true)) {
            $stmt = $pdo->prepare("UPDATE pedidos SET estado = ? WHERE id = ?");
            $stmt->execute([$nuevo_estado, $pedido_id]);
            $mensaje = "Estado actualizado.";
            $tipo_alerta = 'success';
        }
    } elseif ($accion === 'abonar') {
        $pedido_id = (int)($_POST['pedido_id'] ?? 0);
        $monto = (float)($_POST['monto'] ?? 0);

        if ($pedido_id > 0 && $monto > 0) {
            $stmt = $pdo->prepare("INSERT INTO abonos (pedido_id, monto, nota) VALUES (?, ?, 'Abono registrado')");
            $stmt->execute([$pedido_id, $monto]);
            $mensaje = "Abono de $" . number_format($monto, 2) . " registrado.";
            $tipo_alerta = 'success';
        }
    }
}

if (isset($_GET['ok'])) {
    $mensaje = "Pedido registrado correctamente.";
    $tipo_alerta = 'success';
}

$filtro = $_GET['filtro'] ?? 'activos';
$buscar = trim($_GET['buscar'] ?? '');
$mostrar_nuevo = isset($_GET['nuevo']);

$where = [];
$params = [];

if ($filtro === 'activos') {
    $where[] = "p.estado IN ('pendiente', 'en_produccion', 'listo')";
} elseif ($filtro === 'pendientes') {
    $where[] = "p.estado = 'pendiente'";
} elseif ($filtro === 'entregados') {
    $where[] = "p.estado = 'entregado'";
}

if ($buscar !== '') {
    $where[] = "(c.nombre LIKE ? OR p.descripcion LIKE ?)";
    $params[] = "%{$buscar}%";
    $params[] = "%{$buscar}%";
}

$sql = "
    SELECT p.id, p.descripcion, p.fecha_entrega, p.total, p.estado, p.nota,
           c.nombre AS cliente, c.telefono,
           COALESCE((SELECT SUM(a.monto) FROM abonos a WHERE a.pedido_id = p.id), 0) AS abonado
    FROM pedidos p
    JOIN clientes c ON p.cliente_id = c.id
";

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY p.fecha_entrega ASC";

$stmtLista = $pdo->prepare($sql);
$stmtLista->execute($params);
$pedidos = $stmtLista->fetchAll();

$pagina_activa = 'pedidos';
include_once __DIR__ . '/../Layouts/header.php';
?>

<link rel="stylesheet" href="/panaderia/Assets/css/siderbar.css">
<link rel="stylesheet" href="/panaderia/Assets/css/pedidos.css">

<div class="d-flex">
    <?php include_once __DIR__ . '/../Layouts/sidebar.php'; ?>

    <div class="main flex-grow-1 p-4">
        <div class="ped-header-section mb-4">
            <div class="d-flex justify-content-between align-items-baseline flex-wrap gap-2">
                <h2 class="fw-bold" style="color: #6B4226;">Pedidos y encargos</h2>
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

        <div class="ped-actions-bar mb-4">
            <div>
                <a class="ins-btn ins-btn-primary" href="<?= $mostrar_nuevo ? 'pedidos.php' : 'pedidos.php?nuevo=1' ?>" style="background: #C98A3B; color: #2d1b10; border-radius: 10px; padding: 9px 18px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    <?= $mostrar_nuevo ? 'Cerrar formulario' : 'Nuevo pedido' ?>
                </a>
            </div>

            <form method="get" action="pedidos.php" class="ped-search-group m-0">
                <select name="filtro" class="form-select ped-select js-auto-submit" aria-label="Filtrar pedidos">
                    <option value="activos" <?= $filtro === 'activos' ? 'selected' : '' ?>>Activos</option>
                    <option value="todos" <?= $filtro === 'todos' ? 'selected' : '' ?>>Todos</option>
                    <option value="pendientes" <?= $filtro === 'pendientes' ? 'selected' : '' ?>>Pendientes</option>
                    <option value="entregados" <?= $filtro === 'entregados' ? 'selected' : '' ?>>Entregados</option>
                </select>

                <input type="text" name="buscar" value="<?= htmlspecialchars($buscar) ?>" class="form-control ped-input" placeholder="Buscar cliente o pedido">

                <button type="submit" class="btn ped-btn-search">Buscar</button>
            </form>
        </div>

        <?php if ($mostrar_nuevo): ?>
            <div class="ped-card mb-4">
                <h3 class="ped-card-title">Registrar nuevo pedido</h3>
                <form method="post" action="pedidos.php">
                    <input type="hidden" name="accion" value="crear">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold" style="color: #6b4531;">Nombre del cliente</label>
                            <input type="text" name="cliente_nombre" class="form-control ped-input" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold" style="color: #6b4531;">Teléfono</label>
                            <input type="text" name="cliente_telefono" class="form-control ped-input">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold" style="color: #6b4531;">Fecha y hora de entrega</label>
                            <input type="datetime-local" name="fecha_entrega" class="form-control ped-input" required>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold" style="color: #6b4531;">Cant. panes</label>
                            <input type="number" min="0" id="cantidadPanes" class="form-control ped-input" placeholder="Ej: 16">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold" style="color: #6b4531;">Descripción del pedido</label>
                            <input type="text" name="descripcion" id="descPedido" class="form-control ped-input" placeholder="Ej: 16 panes franceses..." required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold" style="color: #6b4531;">Total ($)</label>
                            <input type="number" step="0.01" min="0" name="total" id="totalPedido" class="form-control ped-input" value="0.00" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold" style="color: #6b4531;">Abono inicial ($)</label>
                            <input type="number" step="0.01" min="0" name="abono_inicial" class="form-control ped-input" value="0.00">
                        </div>
                    </div>
                    <button type="submit" class="btn text-white px-4 py-2" style="background: #4A2F20; border-radius: 10px; font-weight: 600;">Guardar pedido</button>
                </form>
            </div>
        <?php endif; ?>

        <div class="ped-card mb-4">
            <h3 class="ped-card-title">Listado</h3>
            
            <?php if (empty($pedidos)): ?>
                <div class="text-center py-5">
                    <div class="text-muted mb-3" style="font-size: 15px;">No hay pedidos con este filtro.</div>
                    <div class="text-muted d-inline-flex align-items-center gap-2" style="font-size: 12.5px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                        Los datos de clientes son confidenciales y solo se muestran a usuarios con sesión iniciada.
                    </div>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="color: #8A7565; font-size: 11.5px; text-transform: uppercase;">Cliente</th>
                                <th style="color: #8A7565; font-size: 11.5px; text-transform: uppercase;">Pedido</th>
                                <th style="color: #8A7565; font-size: 11.5px; text-transform: uppercase;">Entrega</th>
                                <th class="text-end" style="color: #8A7565; font-size: 11.5px; text-transform: uppercase;">Total / Saldo</th>
                                <th style="color: #8A7565; font-size: 11.5px; text-transform: uppercase;">Estado</th>
                                <th style="color: #8A7565; font-size: 11.5px; text-transform: uppercase;">Abonar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pedidos as $p): ?>
                                <?php $saldo = max(0, $p['total'] - $p['abonado']); ?>
                                <tr>
                                    <td>
                                        <strong style="color: #4A2F20;"><?= htmlspecialchars($p['cliente']) ?></strong>
                                        <?php if ($p['telefono']): ?>
                                            <div class="small text-muted"><?= htmlspecialchars($p['telefono']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="color: #4A2F20;"><?= htmlspecialchars($p['descripcion']) ?></td>
                                    <td class="text-nowrap small text-muted"><?= date('d/m/Y H:i', strtotime($p['fecha_entrega'])) ?></td>
                                    <td class="text-end text-nowrap">
                                        <div style="color: #4A2F20; font-weight: 600;">$<?= number_format($p['total'], 2) ?></div>
                                        <div class="small <?= $saldo > 0 ? 'text-danger' : 'text-success' ?>">Saldo: $<?= number_format($saldo, 2) ?></div>
                                    </td>
                                    <td>
                                        <form method="post" action="pedidos.php" class="m-0">
                                            <input type="hidden" name="accion" value="cambiar_estado">
                                            <input type="hidden" name="pedido_id" value="<?= (int)$p['id'] ?>">
                                            <select name="estado" class="form-select form-select-sm js-auto-submit" style="border-radius: 8px; font-size: 12.5px;">
                                                <option value="pendiente" <?= $p['estado'] === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                                                <option value="en_produccion" <?= $p['estado'] === 'en_produccion' ? 'selected' : '' ?>>En producción</option>
                                                <option value="listo" <?= $p['estado'] === 'listo' ? 'selected' : '' ?>>Listo</option>
                                                <option value="entregado" <?= $p['estado'] === 'entregado' ? 'selected' : '' ?>>Entregado</option>
                                                <option value="cancelado" <?= $p['estado'] === 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td>
                                        <?php if ($saldo > 0 && $p['estado'] !== 'cancelado'): ?>
                                            <form method="post" action="pedidos.php" class="d-flex gap-1 align-items-center m-0">
                                                <input type="hidden" name="accion" value="abonar">
                                                <input type="hidden" name="pedido_id" value="<?= (int)$p['id'] ?>">
                                                <input type="number" step="0.01" min="0.01" max="<?= $saldo ?>" name="monto" class="form-control form-control-sm" style="width: 80px; border-radius: 8px;" placeholder="$" required>
                                                <button type="submit" class="btn btn-sm btn-outline-secondary" style="border-radius: 8px;">+</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">Pagado</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <?php include_once __DIR__ . '/../Layouts/footer.php'; ?>
    </div>
</div>

<script src="/panaderia/Assets/js/pedidos.js"></script>