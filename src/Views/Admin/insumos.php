<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/panaderia/config/current_user.php';

$pagina_activa = 'insumos';
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

        <div class="ins-actions-bar d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div class="d-flex gap-2 align-items-center">
                <a class="ins-btn ins-btn-primary" href="insumos.php?nuevo=1">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    Nuevo insumo
                </a>
                <a class="ins-btn ins-btn-line" href="insumos.php<?= ($verInactivos ?? false) ? '' : '?inactivos=1' ?>">
                    <?= ($verInactivos ?? false) ? 'Ocultar inactivos' : 'Mostrar inactivos' ?>
                </a>
            </div>
            <div class="text-muted" style="font-size: 14.5px;">
                Valor del inventario: <strong style="color: #6B4226;">$108.00</strong>
            </div>
        </div>

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
                            <?php
                            $insumosEjemplo = [
                                ['nombre' => 'Azúcar', 'stock' => '30 lb', 'pct' => 75, 'min' => 10, 'costo' => '$0.55', 'estado' => 'Normal'],
                                ['nombre' => 'Harina de trigo', 'stock' => '100 lb', 'pct' => 90, 'min' => 25, 'costo' => '$0.45', 'estado' => 'Normal'],
                                ['nombre' => 'Huevos', 'stock' => '60 unidad', 'pct' => 60, 'min' => 30, 'costo' => '$0.20', 'estado' => 'Normal'],
                                ['nombre' => 'Levadura', 'stock' => '3 lb', 'pct' => 40, 'min' => 1, 'costo' => '$3.50', 'estado' => 'Normal'],
                                ['nombre' => 'Manteca vegetal', 'stock' => '20 lb', 'pct' => 50, 'min' => 5, 'costo' => '$1.10', 'estado' => 'Normal'],
                                ['nombre' => 'Sal', 'stock' => '10 lb', 'pct' => 70, 'min' => 2, 'costo' => '$0.20', 'estado' => 'Normal'],
                            ];

                            foreach ($insumosEjemplo as $item):
                            ?>
                            <tr>
                                <td><strong style="color: #4A2F20;"><?= htmlspecialchars($item['nombre']) ?></strong></td>
                                <td>
                                    <div style="margin-bottom: 3px; font-weight: 500;"><?= htmlspecialchars($item['stock']) ?></div>
                                    <div class="ins-bar"><i style="width: <?= $item['pct'] ?>%"></i></div>
                                </td>
                                <td class="text-center"><?= $item['min'] ?></td>
                                <td class="text-center"><?= $item['costo'] ?></td>
                                <td><span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><?= $item['estado'] ?></span></td>
                                <td class="text-end">
                                    <div class="d-flex gap-1 justify-content-end">
                                        <a href="#" class="btn btn-sm" style="background: #C98A3B; color: #2d1b10; font-weight: 600; font-size: 12px;">Movimiento</a>
                                        <a href="#" class="btn btn-sm btn-outline-secondary" style="font-size: 12px;">Editar</a>
                                        <a href="#" class="btn btn-sm btn-outline-danger ins-btn-danger" style="font-size: 12px;">Desactivar</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="ins-tab-content" id="tab-movimientos">
            <div class="card p-3 mb-4" style="background: #FFFDF9; border: 1px solid #EADFCE; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                <div class="text-muted text-center py-5" style="font-size: 14.5px;">
                    Sin movimientos todavía.
                </div>
            </div>
        </div>

        <?php include_once __DIR__ . '/../Layouts/footer.php'; ?>

    </div>
</div>

<script src="/panaderia/Assets/js/insumos.js"></script>