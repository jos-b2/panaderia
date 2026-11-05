<?php
/**
 * Vista de Recetas y Productos - Panadería Don Leo
 * Ubicación: /panaderia/src/Views/Admin/recetas.php
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Cargar datos del usuario global y sesión
require_once $_SERVER['DOCUMENT_ROOT'] . '/panaderia/config/current_user.php';

// 2. Definir pestaña activa para iluminar "Recetas" en el sidebar
$pagina_activa = 'recetas';

// 3. Incluir el Header global
include_once __DIR__ . '/../Layouts/header.php';
?>

<!-- Hojas de estilos: Sidebar oficial y el CSS específico de Recetas -->
<link rel="stylesheet" href="/panaderia/Assets/css/siderbar.css">
<link rel="stylesheet" href="/panaderia/Assets/css/recetas.css">

<div class="d-flex">
    
    <!-- Sidebar Modular -->
    <?php include_once __DIR__ . '/../Layouts/sidebar.php'; ?>

    <!-- Contenedor Principal (Idéntico al Panel y a Insumos) -->
    <div class="main flex-grow-1 p-4">
        
        <!-- Cabecera de la sección -->
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

        <!-- Botón superior -->
        <div class="mb-4">
            <a class="ins-btn ins-btn-primary" href="recetas.php?nuevo=1" style="background: #C98A3B; color: #2d1b10; border-radius: 10px; padding: 9px 18px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                Nuevo producto
            </a>
        </div>

        <!-- Cuadrícula de Tarjetas de Recetas (2 columnas) -->
        <div class="rec-grid mb-4">
            
            <!-- Tarjeta 1: Pan dulce -->
            <div class="rec-card">
                <div class="rec-card-header">
                    <h3 class="rec-product-title">Pan dulce</h3>
                    <div class="rec-price-tag"><strong>$0.25</strong> / pieza</div>
                </div>

                <div class="rec-details-grid mb-3">
                    <div class="rec-row-info">
                        <span class="text-muted">Rinde</span>
                        <span><strong>8 piezas</strong> por lb de harina de trigo</span>
                    </div>
                    <div class="rec-row-info">
                        <span class="text-muted">Costo estimado</span>
                        <span><strong>$0.17</strong> por pieza</span>
                    </div>
                    <div class="rec-row-info">
                        <span class="text-muted">Margen</span>
                        <span class="text-success fw-bold">30%</span>
                    </div>
                </div>

                <hr style="border-color: #EADFCE; opacity: 1; margin: 14px 0;">

                <ul class="rec-ingredients-list mb-4">
                    <li>1 lb de harina de trigo <span class="rec-base-label">(base)</span></li>
                    <li>0.3 lb de azúcar</li>
                    <li>2 unidad de huevos</li>
                    <li>0.03 lb de levadura</li>
                    <li>0.25 lb de manteca vegetal</li>
                    <li>0.02 lb de sal</li>
                </ul>

                <div class="rec-card-actions">
                    <a href="#" class="btn btn-sm px-3 py-1.5" style="background: #C98A3B; color: #2d1b10; font-weight: 600; border-radius: 8px;">Editar</a>
                    <a href="#" class="btn btn-sm btn-outline-danger px-3 py-1.5" style="border-radius: 8px; color: #b3392f; border-color: #eec5c2;">Desactivar</a>
                </div>
            </div>

            <!-- Tarjeta 2: Pan francés -->
            <div class="rec-card">
                <div class="rec-card-header">
                    <h3 class="rec-product-title">Pan francés</h3>
                    <div class="rec-price-tag"><strong>$0.12</strong> / pieza</div>
                </div>

                <div class="rec-details-grid mb-3">
                    <div class="rec-row-info">
                        <span class="text-muted">Rinde</span>
                        <span><strong>14 piezas</strong> por lb de harina de trigo</span>
                    </div>
                    <div class="rec-row-info">
                        <span class="text-muted">Costo estimado</span>
                        <span><strong>$0.04</strong> por pieza</span>
                    </div>
                    <div class="rec-row-info">
                        <span class="text-muted">Margen</span>
                        <span class="text-success fw-bold">63%</span>
                    </div>
                </div>

                <hr style="border-color: #EADFCE; opacity: 1; margin: 14px 0;">

                <ul class="rec-ingredients-list mb-4">
                    <li>1 lb de harina de trigo <span class="rec-base-label">(base)</span></li>
                    <li>0.04 lb de azúcar</li>
                    <li>0.02 lb de levadura</li>
                    <li>0.06 lb de manteca vegetal</li>
                    <li>0.03 lb de sal</li>
                </ul>

                <div class="rec-card-actions">
                    <a href="#" class="btn btn-sm px-3 py-1.5" style="background: #C98A3B; color: #2d1b10; font-weight: 600; border-radius: 8px;">Editar</a>
                    <a href="#" class="btn btn-sm btn-outline-danger px-3 py-1.5" style="border-radius: 8px; color: #b3392f; border-color: #eec5c2;">Desactivar</a>
                </div>
            </div>

        </div>

        <!-- Footer oficial integrado unificado -->
        <?php include_once __DIR__ . '/../Layouts/footer.php'; ?>

    </div>
</div>

<script src="/panaderia/Assets/js/recetas.js"></script>