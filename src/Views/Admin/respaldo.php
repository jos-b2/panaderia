<?php
/**
 * Vista: Respaldos - Panadería Don Leo
 * Ubicación: /panaderia/src/Views/Admin/respaldo.php
 * La prepara Controllers/RespaldoController.php
 */
// Si se abre esta vista directamente (dirección vieja), ir a su controlador
if (realpath($_SERVER["SCRIPT_FILENAME"]) === __FILE__) {
    header("Location: /panaderia/Controllers/RespaldoController.php");
    exit;
}

include_once __DIR__ . '/../Layouts/header.php';
?>

<link rel="stylesheet" href="/panaderia/Assets/css/siderbar.css">
<link rel="stylesheet" href="/panaderia/Assets/css/respaldo.css">

<div class="d-flex">
    <?php include_once __DIR__ . '/../Layouts/sidebar.php'; ?>

    <div class="main flex-grow-1 p-4">
        <div class="res-header-section mb-4">
            <div class="d-flex justify-content-between align-items-baseline flex-wrap gap-2">
                <h2 class="fw-bold" style="color: #6B4226;">Respaldos</h2>
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

        <div class="res-grid-top mb-4">
            <div class="res-card">
                <h3 class="res-card-title">Copia de seguridad</h3>
                <p class="text-muted mb-4" style="font-size: 14px; line-height: 1.6;">
                    Puede generar un respaldo completo de la base de datos en formato <strong>.sql</strong> en cualquier momento y descargarlo para resguardo.
                </p>
                <form method="post" action="RespaldoController.php" id="formRespaldo">
                    <button type="submit" id="btnCrearRespaldo" class="btn text-white px-4 py-2.5" style="background: #4A2F20; border-radius: 10px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>
                        Crear respaldo ahora
                    </button>
                </form>
            </div>

            <div class="res-card h-100">
                <h3 class="res-card-title">Cómo restaurar</h3>
                <ol class="text-muted ps-3 mb-3" style="font-size: 14px; line-height: 1.6;">
                    <li>Abra <strong>phpMyAdmin</strong> y seleccione la base de datos <code style="background: #f3e4cf; color: #4A2F20; padding: 2px 6px; border-radius: 4px;">panaderia_don_leo</code>.</li>
                    <li>Entre a la pestaña <strong>Importar</strong> y seleccione el archivo <code style="background: #f3e4cf; color: #4A2F20; padding: 2px 6px; border-radius: 4px;">.sql</code> descargado.</li>
                    <li>Presione <strong>Continuar</strong> para restaurar los datos.</li>
                </ol>
                <div class="text-muted" style="font-size: 12.5px;">
                    Recomendación: descargue respaldos periódicamente a una memoria USB.
                </div>
            </div>
        </div>

        <div class="res-card mb-4">
            <h3 class="res-card-title">Respaldos disponibles</h3>
            
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="color: #8A7565; font-size: 11.5px; text-transform: uppercase;">Archivo</th>
                            <th style="color: #8A7565; font-size: 11.5px; text-transform: uppercase;">Fecha</th>
                            <th style="color: #8A7565; font-size: 11.5px; text-transform: uppercase;">Tamaño</th>
                            <th class="text-end" style="color: #8A7565; font-size: 11.5px; text-transform: uppercase;">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($listaRespaldos)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">Aún no hay respaldos generados.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($listaRespaldos as $r): ?>
                                <tr>
                                    <td><strong style="color: #4A2F20; font-family: monospace; font-size: 13.5px;"><?= htmlspecialchars($r['archivo']) ?></strong></td>
                                    <td style="color: #4A2F20;"><?= htmlspecialchars($r['fecha']) ?></td>
                                    <td style="color: #4A2F20;"><?= htmlspecialchars($r['tam']) ?></td>
                                    <td class="text-end">
                                        <a href="RespaldoController.php?descargar=<?= urlencode($r['archivo']) ?>" class="btn btn-sm btn-outline-secondary px-3 py-1" style="border-radius: 8px; font-size: 12.5px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                            Descargar
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php include_once __DIR__ . '/../Layouts/footer.php'; ?>
    </div>
</div>

<script src="/panaderia/Assets/js/respaldo.js"></script>
