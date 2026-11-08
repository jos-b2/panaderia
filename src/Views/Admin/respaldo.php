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

<link rel="stylesheet" href="/panaderia/Assets/css/siderbar.css?v=4">
<link rel="stylesheet" href="/panaderia/Assets/css/respaldo.css?v=2">

<div class="d-flex">
    <?php include_once __DIR__ . '/../Layouts/sidebar.php'; ?>

    <div class="main">
        <div class="page-head res-header-section mb-4">
            <div class="d-flex justify-content-between align-items-baseline flex-wrap gap-2">
                <h2 class="fw-bold page-title">Respaldos</h2>
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

        <div class="res-grid-top mb-4">
            <div class="res-card">
                <h3 class="res-card-title">Copia de seguridad</h3>
                <p class="text-muted mb-4">
                    Puede generar un respaldo completo de la base de datos en formato <strong>.sql</strong> en cualquier momento y descargarlo para resguardo.
                </p>
                <form method="post" action="RespaldoController.php" id="formRespaldo">
                    <button type="submit" id="btnCrearRespaldo" class="btn text-white px-4 py-2.5 btn-brand" style="display: inline-flex;">
                        <i class="ph-duotone ph-database"></i>
                        Crear respaldo ahora
                    </button>
                </form>
            </div>

            <div class="res-card h-100">
                <h3 class="res-card-title">Cómo restaurar</h3>
                <ol class="text-muted ps-3 mb-3">
                    <li>Abra <strong>phpMyAdmin</strong> y seleccione la base de datos <code>panaderia_don_leo</code>.</li>
                    <li>Entre a la pestaña <strong>Importar</strong> y seleccione el archivo <code>.sql</code> descargado.</li>
                    <li>Presione <strong>Continuar</strong> para restaurar los datos.</li>
                </ol>
                <div class="text-muted">
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
                            <th>Archivo</th>
                            <th>Fecha</th>
                            <th>Tamaño</th>
                            <th class="text-end">Acción</th>
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
                                    <td><strong><?= htmlspecialchars($r['archivo']) ?></strong></td>
                                    <td><?= htmlspecialchars($r['fecha']) ?></td>
                                    <td><?= htmlspecialchars($r['tam']) ?></td>
                                    <td class="text-end">
                                        <a href="RespaldoController.php?descargar=<?= urlencode($r['archivo']) ?>" class="btn btn-sm btn-outline-secondary px-3 py-1" style="display: inline-flex;">
                                            <i class="ph-duotone ph-download-simple"></i>
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
