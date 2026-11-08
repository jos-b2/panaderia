<?php
/**
 * Vista: Pedidos - Panadería Don Leo
 * Ubicación: /panaderia/src/Views/Admin/pedidos.php
 * La prepara Controllers/PedidosController.php
 */
// Si se abre esta vista directamente (dirección vieja), ir a su controlador
if (realpath($_SERVER["SCRIPT_FILENAME"]) === __FILE__) {
    header("Location: /panaderia/Controllers/PedidosController.php");
    exit;
}

include_once __DIR__ . '/../Layouts/header.php';
?>

<link rel="stylesheet" href="/panaderia/Assets/css/siderbar.css?v=4">
<link rel="stylesheet" href="/panaderia/Assets/css/pedidos.css?v=4">

<div class="d-flex">
    <?php include_once __DIR__ . '/../Layouts/sidebar.php'; ?>

    <div class="main">
        <div class="page-head ped-header-section mb-4">
            <div class="d-flex justify-content-between align-items-baseline flex-wrap gap-2">
                <h2 class="fw-bold page-title">Pedidos y encargos</h2>
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

        <div class="ped-actions-bar mb-4">
            <div>
                <a class="ins-btn ins-btn-primary btn-accent" href="<?= $mostrar_nuevo ? 'PedidosController.php' : 'PedidosController.php?nuevo=1' ?>" style="display: inline-flex;">
                    <i class="ph <?= $mostrar_nuevo ? 'ph-x' : 'ph-plus' ?>"></i>
                    <?= $mostrar_nuevo ? 'Cerrar formulario' : 'Nuevo pedido' ?>
                </a>
            </div>

            <form method="get" action="PedidosController.php" class="ped-search-group m-0">
                <select name="filtro" class="form-select ped-select js-auto-submit" aria-label="Filtrar pedidos">
                    <option value="activos" <?= $filtro === 'activos' ? 'selected' : '' ?>>Activos</option>
                    <option value="todos" <?= $filtro === 'todos' ? 'selected' : '' ?>>Todos</option>
                    <option value="pendientes" <?= $filtro === 'pendientes' ? 'selected' : '' ?>>Pendientes</option>
                    <option value="entregados" <?= $filtro === 'entregados' ? 'selected' : '' ?>>Entregados</option>
                </select>

                <input type="text" name="buscar" value="<?= htmlspecialchars($buscar) ?>" class="form-control ped-input" placeholder="Buscar cliente o pedido">

                <button type="submit" class="btn ped-btn-search"><i class="ph-duotone ph-magnifying-glass"></i>Buscar</button>
            </form>
        </div>

        <?php if ($mostrar_nuevo): ?>
            <div class="ped-card mb-4">
                <h3 class="ped-card-title">Registrar nuevo pedido</h3>
                <form method="post" action="PedidosController.php">
                    <input type="hidden" name="accion" value="crear">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Nombre del cliente</label>
                            <input type="text" name="cliente_nombre" class="form-control ped-input" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Teléfono</label>
                            <input type="text" name="cliente_telefono" class="form-control ped-input">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold">Fecha y hora de entrega</label>
                            <input type="datetime-local" name="fecha_entrega" class="form-control ped-input" required>
                        </div>
                    </div>
                    <!-- Productos del pedido: una línea por pan -->
                    <div class="ped-items mb-3" data-precios='<?= htmlspecialchars(json_encode(array_column($productosActivos, 'precio', 'id')), ENT_QUOTES) ?>'>
                        <div class="ped-items-head">
                            <span class="form-label mb-0">Productos del pedido</span>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnAgregarItem"><i class="ph ph-plus"></i>Agregar producto</button>
                        </div>
                        <div id="listaItems">
                            <div class="ped-item">
                                <select name="item_producto[]" class="form-select" required>
                                    <?php foreach ($productosActivos as $pr): ?>
                                        <option value="<?= (int)$pr['id'] ?>"><?= htmlspecialchars($pr['nombre']) ?> · $<?= number_format((float)$pr['precio'], 2) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="number" name="item_cantidad[]" class="form-control" min="1" placeholder="Cantidad" required>
                                <span class="ped-item-subtotal">$0.00</span>
                                <button type="button" class="btn btn-sm btn-outline-danger js-quitar-item" title="Quitar"><i class="ph-duotone ph-trash"></i></button>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Descripción <span class="text-lowercase">(opcional)</span></label>
                            <input type="text" name="descripcion" id="descPedido" class="form-control ped-input" placeholder="Se arma sola con los productos">
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <label class="form-label small fw-semibold">Total ($)</label>
                            <input type="number" step="0.01" min="0" name="total" id="totalPedido" class="form-control ped-input" value="0.00" required>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <label class="form-label small fw-semibold">Abono inicial ($)</label>
                            <input type="number" step="0.01" min="0" name="abono_inicial" class="form-control ped-input" value="0.00">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-brand"><i class="ph-duotone ph-floppy-disk"></i>Guardar pedido</button>
                </form>

                <!-- Plantilla de línea de producto (la usa pedidos.js) -->
                <template id="tplItem">
                    <div class="ped-item">
                        <select name="item_producto[]" class="form-select" required>
                            <?php foreach ($productosActivos as $pr): ?>
                                <option value="<?= (int)$pr['id'] ?>"><?= htmlspecialchars($pr['nombre']) ?> · $<?= number_format((float)$pr['precio'], 2) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="number" name="item_cantidad[]" class="form-control" min="1" placeholder="Cantidad" required>
                        <span class="ped-item-subtotal">$0.00</span>
                        <button type="button" class="btn btn-sm btn-outline-danger js-quitar-item" title="Quitar"><i class="ph-duotone ph-trash"></i></button>
                    </div>
                </template>
            </div>
        <?php endif; ?>

        <!-- Pan disponible hoy: cuánto alcanza para los encargos y cuánto queda para vender -->
        <section class="ped-card ped-pan">
            <header class="ped-pan-head">
                <div>
                    <h3 class="ped-card-title mb-1">Pan disponible hoy</h3>
                    <small class="text-muted">Sobrante de ayer + tandas listas hoy, menos lo ya entregado o vendido.</small>
                </div>
                <a href="HornearController.php" class="btn btn-sm btn-outline-secondary"><i class="ph-duotone ph-oven"></i>Ver hornos</a>
            </header>

            <div class="table-responsive">
                <table class="table align-middle mb-0 ped-pan-tabla">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th class="text-end">Disponible</th>
                            <th class="text-end">En proceso</th>
                            <th class="text-end">Para encargos</th>
                            <th class="text-end">Para vender</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($disponibilidad['productos'] as $dp): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($dp['nombre']) ?></strong></td>
                                <td class="text-end"><?= number_format($dp['disponible']) ?></td>
                                <td class="text-end text-muted"><?= $dp['en_proceso'] > 0 ? '+' . number_format($dp['en_proceso']) : '—' ?></td>
                                <td class="text-end"><?= number_format($dp['para_encargos']) ?></td>
                                <td class="text-end">
                                    <?php if ($dp['para_vender'] >= 0): ?>
                                        <span class="ped-vender"><?= number_format($dp['para_vender']) ?></span>
                                    <?php else: ?>
                                        <span class="ped-faltan">Faltan <?= number_format(-$dp['para_vender']) ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="ped-encargos">
                <h4><i class="ph-duotone ph-clipboard-text"></i>Encargos para hoy</h4>
                <?php if (empty($disponibilidad['encargos'])): ?>
                    <p class="text-muted mb-0">No hay encargos pendientes para hoy. Todo el pan disponible queda para vender.</p>
                <?php else: ?>
                    <ul>
                        <?php foreach ($disponibilidad['encargos'] as $e): ?>
                            <?php $atrasado = strtotime($e['fecha_entrega']) < strtotime('today'); ?>
                            <li class="<?= $e['cubierto'] ? 'is-cubierto' : 'is-falta' ?>">
                                <span class="ped-hora"><?= $atrasado ? 'Atrasado' : date('g:i a', strtotime($e['fecha_entrega'])) ?></span>
                                <div>
                                    <strong><?= htmlspecialchars($e['cliente']) ?></strong>
                                    <small><?= htmlspecialchars($e['resumen']) ?></small>
                                </div>
                                <?php if ($e['cubierto']): ?>
                                    <span class="ped-chip is-ok"><i class="ph-duotone ph-check-circle"></i>Cubierto</span>
                                <?php else: ?>
                                    <span class="ped-chip is-falta"><i class="ph-duotone ph-warning"></i>Faltan <?= htmlspecialchars(implode(', ', $e['faltan'])) ?></span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </section>

        <!-- Ventas por encargo registradas hoy (pedidos entregados) -->
        <section class="ped-card ped-ventas">
            <header class="ped-pan-head">
                <div>
                    <h3 class="ped-card-title mb-1">Ventas de hoy por encargo</h3>
                    <small class="text-muted">Al marcar un pedido como <strong>Entregado</strong> se registra aquí como venta.</small>
                </div>
                <?php $totVentas = array_sum(array_column($ventasHoy, 'total')); $pzVentas = array_sum(array_column($ventasHoy, 'piezas')); ?>
                <span class="ped-ventas-total"><?= number_format($pzVentas) ?> pzs · $<?= number_format($totVentas, 2) ?></span>
            </header>
            <?php if (empty($ventasHoy)): ?>
                <p class="text-muted mb-0"><i class="ph-duotone ph-receipt me-1"></i>Todavía no se ha entregado ningún pedido hoy.</p>
            <?php else: ?>
                <ul class="ped-ventas-lista">
                    <?php foreach ($ventasHoy as $v): ?>
                        <li>
                            <span class="ped-hora"><?= date('g:i a', strtotime($v['entregado_en'])) ?></span>
                            <div>
                                <strong>#<?= (int)$v['id'] ?> · <?= htmlspecialchars($v['cliente']) ?></strong>
                                <small><?= htmlspecialchars($v['productos'] ?: 'Sin productos registrados') ?></small>
                            </div>
                            <span class="ped-ventas-monto">$<?= number_format((float)$v['total'], 2) ?></span>
                            <?php if ($v['saldo'] > 0): ?>
                                <span class="ped-chip is-falta"><i class="ph-duotone ph-warning"></i>Debe $<?= number_format($v['saldo'], 2) ?></span>
                            <?php else: ?>
                                <span class="ped-chip is-ok"><i class="ph-duotone ph-check-circle"></i>Pagado</span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <div class="ped-card mb-4">
            <h3 class="ped-card-title">Listado</h3>
            
            <?php if (empty($pedidos)): ?>
                <div class="text-center py-5">
                    <div class="text-muted mb-3 empty-state"><i class="ph-duotone ph-clipboard-text"></i>No hay pedidos con este filtro.</div>
                    <div class="text-muted d-inline-flex align-items-center gap-2">
                        <i class="ph-duotone ph-lock-key"></i>
                        Los datos de clientes son confidenciales y solo se muestran a usuarios con sesión iniciada.
                    </div>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>Pedido</th>
                                <th>Entrega</th>
                                <th class="text-end">Total / Saldo</th>
                                <th>Estado</th>
                                <th>Abonar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pedidos as $p): ?>
                                <?php $saldo = max(0, $p['total'] - $p['abonado']); ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($p['cliente']) ?></strong>
                                        <?php if ($p['telefono']): ?>
                                            <div class="small text-muted"><?= htmlspecialchars($p['telefono']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($p['descripcion']) ?>
                                        <?php if (!empty($p['items_texto']) && $p['items_texto'] !== $p['descripcion']): ?>
                                            <small class="d-block text-muted"><i class="ph-duotone ph-bread me-1"></i><?= htmlspecialchars($p['items_texto']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-nowrap small text-muted"><?= date('d/m/Y H:i', strtotime($p['fecha_entrega'])) ?></td>
                                    <td class="text-end text-nowrap">
                                        <div>$<?= number_format($p['total'], 2) ?></div>
                                        <div class="small <?= $saldo > 0 ? 'text-danger' : 'text-success' ?>">Saldo: $<?= number_format($saldo, 2) ?></div>
                                    </td>
                                    <td>
                                        <form method="post" action="PedidosController.php" class="m-0">
                                            <input type="hidden" name="accion" value="cambiar_estado">
                                            <input type="hidden" name="pedido_id" value="<?= (int)$p['id'] ?>">
                                            <input type="hidden" name="cobrar_saldo" value="0" class="js-cobrar">
                                            <select name="estado" class="form-select form-select-sm js-estado" data-actual="<?= htmlspecialchars($p['estado']) ?>" data-saldo="<?= number_format($saldo, 2, '.', '') ?>" data-cliente="<?= htmlspecialchars($p['cliente']) ?>">
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
                                            <form method="post" action="PedidosController.php" class="d-flex gap-1 align-items-center m-0">
                                                <input type="hidden" name="accion" value="abonar">
                                                <input type="hidden" name="pedido_id" value="<?= (int)$p['id'] ?>">
                                                <input type="number" step="0.01" min="0.01" max="<?= $saldo ?>" name="monto" class="form-control form-control-sm" style="width: 80px;" placeholder="$" required>
                                                <button type="submit" class="btn btn-sm btn-outline-secondary">+</button>
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

<script src="/panaderia/Assets/js/pedidos.js?v=3"></script>
