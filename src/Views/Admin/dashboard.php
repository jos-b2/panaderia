<?php
/**
 * Panel Administrativo General (Dashboard) - Panadería Don Leo
 * Ubicación: /panaderia/src/Views/Admin/dashboard.php
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Validar inicio de sesión
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Public/login.php");
    exit;
}

require_once __DIR__ . '/../../../config/db.php';
$usuario_id = $_SESSION['user_id'];

// Consultar nombre del perfil personalizado o usuario
try {
    $stmt = $pdo->prepare("
        SELECT U.nombre, U.rol, P.nombre_personalizado 
        FROM usuarios U 
        LEFT JOIN perfil P ON U.id = P.usuario_id 
        WHERE U.id = :id
    ");
    $stmt->execute([':id' => $usuario_id]);
    $userData = $stmt->fetch();
} catch (PDOException $e) {
    $userData = [];
}

$nombreUsuario = htmlspecialchars($userData['nombre_personalizado'] ?: ($_SESSION['user_nombre'] ?? 'Administrador'));
$rolUsuario    = htmlspecialchars(strtoupper($userData['rol'] ?? 'ADMIN'));

include_once __DIR__ . '/../Layouts/header.php'; 
?>

<!-- Importación directa del CSS del Dashboard -->
<link rel="stylesheet" href="/panaderia/Assets/css/dashboard.css?v=8">

<script>
    document.body.className = 'dashboard-bg';
</script>

<!-- Navbar Superior -->
<nav class="navbar navbar-expand-lg navbar-dark navbar-custom px-4 py-2">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="dashboard.php">
            <span class="fs-5 tracking-wide text-white">Panadería Don Leo</span>
        </a>

        <div class="d-flex align-items-center gap-3">
            <!-- Bloque de Usuario Interactivo (Desencadena abrirPerfilModal) -->
            <div onclick="abrirPerfilModal()" class="d-flex align-items-center gap-2 px-3 py-1 rounded-pill" style="background-color: rgba(107, 66, 38, 0.6); border: 1px solid #D4A373; color: #FEFAE0; cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.03)'" onmouseout="this.style.transform='scale(1)'" title="Ver perfil">
                <i class="fa-solid fa-circle-user fs-5" style="color: #D4A373;"></i>
                <div class="lh-1 text-start">
                    <div class="fw-bold small text-white"><?php echo $nombreUsuario; ?></div>
                    <span style="font-size: 0.65rem; color: #D4A373;"><?php echo $rolUsuario; ?></span>
                </div>
            </div>

            <!-- Botón Salir (Desencadena confirmarSalida) -->
            <button onclick="confirmarSalida()" class="btn btn-sm px-3 rounded-3 fw-semibold d-flex align-items-center gap-2 border-0" style="background-color: #6B4226; border: 1px solid #D4A373 !important; color: #FEFAE0; cursor: pointer;">
                <i class="fa-solid fa-right-from-bracket" style="color: #D4A373;"></i>
                <span>Salir</span>
            </button>
        </div>
    </div>
</nav>

<!-- Contenido Central Limpio -->
<div class="dashboard-main-container text-center">

    <div class="mb-3">
        <h1 class="dashboard-hero-title text-uppercase">PANADERÍA DE DON LEO</h1>
        <p class="dashboard-hero-subtitle">Panel administrativo — Selecciona un módulo de gestión</p>
    </div>

    <!-- Malla de Tarjetas Uniforme con el Sidebar -->
    <div class="row g-3 w-100 justify-content-center" style="max-width: 1050px;">
        
        <!-- Panel / Resumen Operativo -->
        <div class="col-6 col-md-4 col-lg-3">
            <a href="panel.php" class="text-decoration-none">
                <div class="card card-menu-item text-center">
                    <div class="card-icon">
                        <svg class="ico" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l9-8 9 8v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1z"/></svg>
                    </div>
                    <div class="card-title text-uppercase">PANEL</div>
                    <div class="card-desc">Resumen general del negocio</div>
                </div>
            </a>
        </div>

        <!-- Materia Prima / Inventario -->
        <div class="col-6 col-md-4 col-lg-3">
            <a href="insumos.php" class="text-decoration-none">
                <div class="card card-menu-item text-center">
                    <div class="card-icon">
                        <svg class="ico" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8l-9-5-9 5v8l9 5 9-5z"/><path d="M3 8l9 5 9-5M12 13v8"/></svg>
                    </div>
                    <div class="card-title text-uppercase">MATERIA PRIMA</div>
                    <div class="card-desc">Control de stock de insumos</div>
                </div>
            </a>
        </div>

        <!-- Recetas -->
        <div class="col-6 col-md-4 col-lg-3">
            <a href="recetas.php" class="text-decoration-none">
                <div class="card card-menu-item text-center">
                    <div class="card-icon">
                        <svg class="ico" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h12a3 3 0 0 1 3 3v13H7a3 3 0 0 1-3-3z"/><path d="M8 4v13"/></svg>
                    </div>
                    <div class="card-title text-uppercase">RECETAS</div>
                    <div class="card-desc">Cálculo de insumos por tanda</div>
                </div>
            </a>
        </div>

        <!-- Producción -->
        <div class="col-6 col-md-4 col-lg-3">
            <a href="produccion.php" class="text-decoration-none">
                <div class="card card-menu-item text-center">
                    <div class="card-icon">
                        <svg class="ico" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2s5 4 5 9a5 5 0 0 1-10 0c0-2 1-3 2-4 0 2 1 3 2 3 0-3 1-5 1-8z"/></svg>
                    </div>
                    <div class="card-title text-uppercase">PRODUCCIÓN</div>
                    <div class="card-desc">Registro de horneadas diarias</div>
                </div>
            </a>
        </div>

        <!-- Pedidos -->
        <div class="col-6 col-md-4 col-lg-3">
            <a href="pedidos.php" class="text-decoration-none">
                <div class="card card-menu-item text-center">
                    <div class="card-icon">
                        <svg class="ico" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="12" height="17" rx="2"/><path d="M9 4V3h6v1M9 10h6M9 14h6"/></svg>
                    </div>
                    <div class="card-title text-uppercase">PEDIDOS</div>
                    <div class="card-desc">Encargos y abonos de clientes</div>
                </div>
            </a>
        </div>

        <!-- Arqueo Diario -->
        <div class="col-6 col-md-4 col-lg-3">
            <a href="arqueo.php" class="text-decoration-none">
                <div class="card card-menu-item text-center">
                    <div class="card-icon">
                        <svg class="ico" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v18M5 21h14M5 7h14"/><path d="M5 7l-3 7a3 3 0 0 0 6 0zM19 7l-3 7a3 3 0 0 0 6 0z"/></svg>
                    </div>
                    <div class="card-title text-uppercase">ARQUEO DIARIO</div>
                    <div class="card-desc">Cuadrante horneado vs. vendido</div>
                </div>
            </a>
        </div>

        <?php if ($rolUsuario === 'ADMIN'): ?>
            <!-- Usuarios -->
            <div class="col-6 col-md-4 col-lg-3">
                <a href="usuarios.php" class="text-decoration-none">
                    <div class="card card-menu-item text-center">
                        <div class="card-icon">
                            <svg class="ico" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.5"/><path d="M2 20a7 7 0 0 1 14 0M17 4a3.5 3.5 0 0 1 0 7M22 20a7 7 0 0 0-4-6"/></svg>
                        </div>
                        <div class="card-title text-uppercase">USUARIOS</div>
                        <div class="card-desc">Gestión de accesos y roles</div>
                    </div>
                </a>
            </div>

            <!-- Respaldos -->
            <div class="col-6 col-md-4 col-lg-3">
                <a href="respaldo.php" class="text-decoration-none">
                    <div class="card card-menu-item text-center">
                        <div class="card-icon">
                            <svg class="ico" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="8" ry="3"/><path d="M4 5v6c0 1.7 3.6 3 8 3s8-1.3 8-3V5M4 11v6c0 1.7 3.6 3 8 3s8-1.3 8-3v-6"/></svg>
                        </div>
                        <div class="card-title text-uppercase">RESPALDOS</div>
                        <div class="card-desc">Copia de seguridad de la base de datos</div>
                    </div>
                </a>
            </div>
        <?php endif; ?>

    </div>
</div>

<!-- Contenedor del Modal de Perfil (Recibe inyección dinámica desde perfil.php) -->
<div id="perfilModal" class="profile-modal-overlay" style="display: none;">
    <div id="perfilModalContent" class="w-100 d-flex justify-content-center"></div>
</div>

<!-- Archivo JS externo -->
<script src="/panaderia/Assets/js/dashboard.js"></script>

<?php include_once __DIR__ . '/../Layouts/footer.php'; ?>