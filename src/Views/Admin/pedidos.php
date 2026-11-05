<?php
/**
 * Vista de Pedidos y Encargos - Panadería Don Leo
 * Ubicación: /panaderia/src/Views/Admin/pedidos.php
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Cargar datos del usuario global y sesión
require_once $_SERVER['DOCUMENT_ROOT'] . '/panaderia/config/current_user.php';

// 2. Definir pestaña activa para iluminar "Pedidos" en el sidebar
$pagina_activa = 'pedidos';

// 3. Incluir el Header global
include_once __DIR__ . '/../Layouts/header.php';
?>

<!-- Hojas de estilos: Sidebar oficial y el CSS específico de Pedidos -->
<link rel="stylesheet" href="/panaderia/Assets/css/siderbar.css">
<link rel="stylesheet" href="/panaderia/Assets/css/pedidos.css">

<div class="d-flex">
    
    <!-- Sidebar Modular -->
    <?php include_once __DIR__ . '/../Layouts/sidebar.php'; ?>

    <!-- Contenedor Principal (Idéntico a las vistas anteriores) -->
    <div class="main flex-grow-1 p-4">
        
        <!-- Cabecera de la sección -->
        <div class="ped-header-section mb-4">
            <div class="d-flex justify-content-between align-items-baseline flex-wrap gap-2">
                <div>
                    <h2 class="fw-bold" style="color: #6B4226;">Pedidos y encargos</h2>
                </div>
                <div class="text-muted small">
                    <?php 
                        $meses = ["", "ene", "feb", "mar", "abr", "may", "jun", "jul", "ago", "sep", "oct", "nov", "dic"];
                        echo date('j') . ' ' . $meses[(int)date('n')] . ' ' . date('Y');
                    ?>
                </div>
            </div>
        </div>

        <!-- Barra de Acciones Superiores (Botón Nuevo, Filtro y Buscador) -->
        <div class="ped-actions-bar mb-4">
            <div>
                <a class="ins-btn ins-btn-primary" href="pedidos.php?nuevo=1" style="background: #C98A3B; color: #2d1b10; border-radius: 10px; padding: 9px 18px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    Nuevo pedido
                </a>
            </div>

            <div class="ped-search-group">
                <select class="form-select ped-select" aria-label="Filtrar pedidos">
                    <option value="activos" selected>Activos</option>
                    <option value="todos">Todos</option>
                    <option value="pendientes">Pendientes</option>
                    <option value="entregados">Entregados</option>
                </select>

                <input type="text" class="form-control ped-input" placeholder="Buscar cliente o pedido">

                <button type="button" class="btn ped-btn-search">Buscar</button>
            </div>
        </div>

        <!-- Tarjeta Principal: Listado de Pedidos -->
        <div class="ped-card mb-4">
            <h3 class="ped-card-title">Listado</h3>
            
            <div class="text-center py-5">
                <div class="text-muted mb-3" style="font-size: 15px;">No hay pedidos con este filtro.</div>
                
                <div class="text-muted d-inline-flex align-items-center gap-2" style="font-size: 12.5px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    Los datos de clientes son confidenciales y solo se muestran a usuarios con sesión iniciada.
                </div>
            </div>
        </div>

        <!-- Footer oficial integrado unificado -->
        <?php include_once __DIR__ . '/../Layouts/footer.php'; ?>

    </div>
</div>

<script src="/panaderia/Assets/js/pedidos.js"></script>