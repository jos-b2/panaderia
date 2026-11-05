<?php
/**
 * Vista de Respaldos - Panadería Don Leo
 * Ubicación: /panaderia/src/Views/Admin/respaldo.php
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Cargar datos del usuario global y sesión
require_once $_SERVER['DOCUMENT_ROOT'] . '/panaderia/config/current_user.php';

// 2. Definir pestaña activa para iluminar "Respaldo" en el sidebar
$pagina_activa = 'respaldo';

// 3. Incluir el Header global
include_once __DIR__ . '/../Layouts/header.php';
?>

<!-- Hojas de estilos: Sidebar oficial y el CSS específico de Respaldos -->
<link rel="stylesheet" href="/panaderia/Assets/css/siderbar.css">
<link rel="stylesheet" href="/panaderia/Assets/css/respaldo.css">

<div class="d-flex">
    
    <!-- Sidebar Modular -->
    <?php include_once __DIR__ . '/../Layouts/sidebar.php'; ?>

    <!-- Contenedor Principal (Idéntico a las vistas anteriores) -->
    <div class="main flex-grow-1 p-4">
        
        <!-- Cabecera de la sección -->
        <div class="res-header-section mb-4">
            <div class="d-flex justify-content-between align-items-baseline flex-wrap gap-2">
                <div>
                    <h2 class="fw-bold" style="color: #6B4226;">Respaldos</h2>
                </div>
                <div class="text-muted small">
                    <?php 
                        $meses = ["", "ene", "feb", "mar", "abr", "may", "jun", "jul", "ago", "sep", "oct", "nov", "dic"];
                        echo date('j') . ' ' . $meses[(int)date('n')] . ' ' . date('Y');
                    ?>
                </div>
            </div>
        </div>

        <!-- Cuadrícula Superior (Copia de seguridad y Cómo restaurar) -->
        <div class="res-grid-top mb-4">
            
            <!-- Columna Izquierda: Copia de seguridad -->
            <div class="res-card">
                <h3 class="res-card-title">Copia de seguridad</h3>
                <p class="text-muted mb-4" style="font-size: 14px; line-height: 1.6;">
                    El sistema crea automáticamente <strong>un respaldo diario</strong> al primer ingreso del día y conserva los últimos 30. También puede crear uno manual en cualquier momento.
                </p>
                <form method="post" action="respaldo.php">
                    <button type="submit" class="btn text-white px-4 py-2.5" style="background: #4A2F20; border-radius: 10px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>
                        Crear respaldo ahora
                    </button>
                </form>
            </div>

            <!-- Columna Derecha: Cómo restaurar -->
            <div class="res-card h-100">
                <h3 class="res-card-title">Cómo restaurar</h3>
                <ol class="text-muted ps-3 mb-3" style="font-size: 14px; line-height: 1.6;">
                    <li>Cierre el sistema (detenga el servidor).</li>
                    <li>Copie el archivo de respaldo a la carpeta <code style="background: #f3e4cf; color: #4A2F20; padding: 2px 6px; border-radius: 4px;">data/</code> con el nombre <code style="background: #f3e4cf; color: #4A2F20; padding: 2px 6px; border-radius: 4px;">panaderia.sqlite</code>.</li>
                    <li>Inicie el sistema de nuevo.</li>
                </ol>
                <div class="text-muted" style="font-size: 12.5px;">
                    Recomendación: descargue respaldos periódicamente a una memoria USB.
                </div>
            </div>

        </div>

        <!-- Tarjeta Inferior: Respaldos disponibles -->
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
                        <?php 
                        $respaldosEjemplo = [
                            ['archivo' => 'panaderia_2026-11-04_080844_auto.sqlite', 'fecha' => '2026-11-04 08:08', 'tam' => '100 KB'],
                            ['archivo' => 'panaderia_2026-11-03_124456_auto.sqlite', 'fecha' => '2026-11-03 12:44', 'tam' => '100 KB'],
                            ['archivo' => 'panaderia_2026-11-02_193739_auto.sqlite', 'fecha' => '2026-11-02 19:37', 'tam' => '100 KB'],
                        ];
                        foreach ($respaldosEjemplo as $r):
                        ?>
                        <tr>
                            <td><strong style="color: #4A2F20; font-family: monospace; font-size: 13.5px;"><?= $r['archivo'] ?></strong></td>
                            <td style="color: #4A2F20;"><?= $r['fecha'] ?></td>
                            <td style="color: #4A2F20;"><?= $r['tam'] ?></td>
                            <td class="text-end">
                                <a href="#" class="btn btn-sm btn-outline-secondary px-3 py-1" style="border-radius: 8px; font-size: 12.5px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                    Descargar
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Footer oficial integrado unificado -->
        <?php include_once __DIR__ . '/../Layouts/footer.php'; ?>

    </div>
</div>

<script src="/panaderia/Assets/js/respaldo.js"></script>