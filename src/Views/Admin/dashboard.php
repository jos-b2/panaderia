<?php
/**
 * Vista: Menú principal - Panadería Don Leo
 * Ubicación: /panaderia/src/Views/Admin/dashboard.php
 * La prepara Controllers/DashboardController.php
 */
// Si se abre esta vista directamente (dirección vieja), ir a su controlador
if (realpath($_SERVER["SCRIPT_FILENAME"]) === __FILE__) {
    header("Location: /panaderia/Controllers/DashboardController.php");
    exit;
}

include_once __DIR__ . '/../Layouts/header.php';
?>

<!-- Importación directa del CSS del Dashboard -->
<link rel="stylesheet" href="/panaderia/Assets/css/dashboard.css?v=8">

<script>
    document.body.className = 'dashboard-bg';
</script>

<!-- Barra superior -->
<nav class="menu-topbar">
    <a class="menu-brand" href="DashboardController.php">
        <span class="menu-brand-mark"><i class="ph-duotone ph-bread"></i></span>
        <span>Panadería Don Leo</span>
    </a>

    <div class="menu-actions">
        <!-- Usuario: abre la ventana de perfil (abrirPerfilModal) -->
        <button type="button" onclick="abrirPerfilModal()" class="menu-user" title="Ver perfil">
            <i class="ph-duotone ph-user-circle"></i>
            <div class="menu-user-text lh-1">
                <div class="menu-user-name fw-bold"><?php echo htmlspecialchars($nombreUsuario); ?></div>
                <span class="menu-user-role"><?php echo htmlspecialchars($rolUsuario); ?></span>
            </div>
        </button>

        <!-- Salir (confirmarSalida) -->
        <button type="button" onclick="confirmarSalida()" class="menu-logout" title="Cerrar sesión">
            <i class="ph-duotone ph-sign-out"></i>
            <span>Salir</span>
        </button>
    </div>
</nav>

<!-- Contenido central -->
<main class="dashboard-main-container">

    <header class="menu-hero">
        <span class="menu-hero-kicker">Sistema de gestión</span>
        <h1 class="dashboard-hero-title">Panadería de Don Leo</h1>
        <p class="dashboard-hero-subtitle">Selecciona un módulo para comenzar</p>
    </header>

    <!-- Cuadrícula de módulos -->
    <div class="menu-grid">
        
        <!-- Panel / Resumen Operativo -->
        <div class="menu-cell">
            <a href="PanelController.php" class="menu-link">
                <div class="card-menu-item">
                    <div class="card-icon">
                        <i class="ph-duotone ph-squares-four"></i>
                    </div>
                    <div class="card-title">PANEL</div>
                    <div class="card-desc">Resumen general del negocio</div>
                </div>
            </a>
        </div>

        <!-- Materia Prima / Inventario -->
        <div class="menu-cell">
            <a href="InsumosController.php" class="menu-link">
                <div class="card-menu-item">
                    <div class="card-icon">
                        <i class="ph-duotone ph-package"></i>
                    </div>
                    <div class="card-title">MATERIA PRIMA</div>
                    <div class="card-desc">Control de stock de insumos</div>
                </div>
            </a>
        </div>

        <!-- Recetas -->
        <div class="menu-cell">
            <a href="RecetasController.php" class="menu-link">
                <div class="card-menu-item">
                    <div class="card-icon">
                        <i class="ph-duotone ph-book-open-text"></i>
                    </div>
                    <div class="card-title">RECETAS</div>
                    <div class="card-desc">Cálculo de insumos por tanda</div>
                </div>
            </a>
        </div>

        <!-- Producción -->
        <div class="menu-cell">
            <a href="ProduccionController.php" class="menu-link">
                <div class="card-menu-item">
                    <div class="card-icon">
                        <i class="ph-duotone ph-fire"></i>
                    </div>
                    <div class="card-title">PRODUCCIÓN</div>
                    <div class="card-desc">Registro de horneadas diarias</div>
                </div>
            </a>
        </div>

        <!-- Pedidos -->
        <div class="menu-cell">
            <a href="PedidosController.php" class="menu-link">
                <div class="card-menu-item">
                    <div class="card-icon">
                        <i class="ph-duotone ph-clipboard-text"></i>
                    </div>
                    <div class="card-title">PEDIDOS</div>
                    <div class="card-desc">Encargos y abonos de clientes</div>
                </div>
            </a>
        </div>

        <!-- Arqueo Diario -->
        <div class="menu-cell">
            <a href="ArqueoController.php" class="menu-link">
                <div class="card-menu-item">
                    <div class="card-icon">
                        <i class="ph-duotone ph-cash-register"></i>
                    </div>
                    <div class="card-title">ARQUEO DIARIO</div>
                    <div class="card-desc">Cuadrante horneado vs. vendido</div>
                </div>
            </a>
        </div>

        <!-- Reportes -->
        <div class="menu-cell">
            <a href="ReportesController.php" class="menu-link">
                <div class="card-menu-item">
                    <div class="card-icon">
                        <i class="ph-duotone ph-chart-line-up"></i>
                    </div>
                    <div class="card-title">REPORTES</div>
                    <div class="card-desc">Gráficas, PDF y Excel</div>
                </div>
            </a>
        </div>

        <?php if ($rolUsuario === 'ADMIN'): ?>
            <!-- Usuarios -->
            <div class="menu-cell">
                <a href="UsuariosController.php" class="menu-link">
                    <div class="card-menu-item">
                        <div class="card-icon">
                            <i class="ph-duotone ph-users-three"></i>
                        </div>
                        <div class="card-title">USUARIOS</div>
                        <div class="card-desc">Gestión de accesos y roles</div>
                    </div>
                </a>
            </div>

            <!-- Respaldos -->
            <div class="menu-cell">
                <a href="RespaldoController.php" class="menu-link">
                    <div class="card-menu-item">
                        <div class="card-icon">
                            <i class="ph-duotone ph-database"></i>
                        </div>
                        <div class="card-title">RESPALDOS</div>
                        <div class="card-desc">Copia de seguridad de la base de datos</div>
                    </div>
                </a>
            </div>
        <?php endif; ?>

    </div>
</main>

<!-- Contenedor del Modal de Perfil (Recibe el HTML de PerfilController.php) -->
<div id="perfilModal" class="profile-modal-overlay" style="display: none;">
    <div id="perfilModalContent" class="w-100 d-flex justify-content-center"></div>
</div>

<!-- Archivo JS externo -->
<script src="/panaderia/Assets/js/dashboard.js?v=3"></script>

<?php include_once __DIR__ . '/../Layouts/footer.php'; ?>
