<?php
/**
 * Vista de Usuarios - Panadería Don Leo
 * Ubicación: /panaderia/src/Views/Admin/usuarios.php
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Cargar datos del usuario global y sesión
require_once $_SERVER['DOCUMENT_ROOT'] . '/panaderia/config/current_user.php';

// 2. Definir pestaña activa para iluminar "Usuarios" en el sidebar
$pagina_activa = 'usuarios';

// 3. Incluir el Header global
include_once __DIR__ . '/../Layouts/header.php';
?>

<!-- Hojas de estilos: Sidebar oficial y el CSS específico de Usuarios -->
<link rel="stylesheet" href="/panaderia/Assets/css/siderbar.css">
<link rel="stylesheet" href="/panaderia/Assets/css/usuarios.css">

<div class="d-flex">
    
    <!-- Sidebar Modular -->
    <?php include_once __DIR__ . '/../Layouts/sidebar.php'; ?>

    <!-- Contenedor Principal (Idéntico a las vistas anteriores) -->
    <div class="main flex-grow-1 p-4">
        
        <!-- Cabecera de la sección -->
        <div class="usr-header-section mb-4">
            <div class="d-flex justify-content-between align-items-baseline flex-wrap gap-2">
                <div>
                    <h2 class="fw-bold" style="color: #6B4226;">Usuarios</h2>
                </div>
                <div class="text-muted small">
                    <?php 
                        $meses = ["", "ene", "feb", "mar", "abr", "may", "jun", "jul", "ago", "sep", "oct", "nov", "dic"];
                        echo date('j') . ' ' . $meses[(int)date('n')] . ' ' . date('Y');
                    ?>
                </div>
            </div>
        </div>

        <!-- Cuadrícula Superior (Formulario de Nuevo Usuario y Roles de Acceso) -->
        <div class="usr-grid-top mb-4">
            
            <!-- Columna Izquierda: Formulario Nuevo usuario -->
            <div class="usr-card">
                <h3 class="usr-card-title">Nuevo usuario</h3>
                
                <form method="post" action="usuarios.php">
                    <div class="mb-3">
                        <label class="usr-form-label">Nombre completo</label>
                        <input type="text" name="nombre" class="form-control usr-input" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="usr-form-label">Usuario</label>
                            <input type="text" name="usuario" class="form-control usr-input" required>
                        </div>
                        <div class="col-md-6">
                            <label class="usr-form-label">Clave inicial</label>
                            <input type="password" name="clave" class="form-control usr-input" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="usr-form-label">Rol</label>
                        <select name="rol" class="form-select usr-input">
                            <option value="empleado">Empleado (opera el sistema)</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </div>

                    <button type="submit" class="btn text-white px-4 py-2" style="background: #4A2F20; border-radius: 10px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        Crear usuario
                    </button>
                </form>
            </div>

            <!-- Columna Derecha: Roles de acceso -->
            <div class="usr-card h-100">
                <h3 class="usr-card-title">Roles de acceso</h3>
                <div class="text-muted" style="font-size: 14px; line-height: 1.6; display: flex; flex-direction: column; gap: 12px;">
                    <div>
                        <strong style="color: #4A2F20;">Empleado:</strong> registra producción, inventario, pedidos y arqueos.
                    </div>
                    <div>
                        <strong style="color: #4A2F20;">Administrador:</strong> además gestiona usuarios, respaldos, anula tandas y elimina registros.
                    </div>
                </div>
            </div>

        </div>

        <!-- Tarjeta Inferior: Usuarios registrados -->
        <div class="usr-card mb-4">
            <h3 class="usr-card-title">Usuarios registrados</h3>
            
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="color: #8A7565; font-size: 11.5px; text-transform: uppercase;">Nombre</th>
                            <th style="color: #8A7565; font-size: 11.5px; text-transform: uppercase;">Usuario</th>
                            <th style="color: #8A7565; font-size: 11.5px; text-transform: uppercase;">Rol</th>
                            <th style="color: #8A7565; font-size: 11.5px; text-transform: uppercase;">Estado</th>
                            <th style="color: #8A7565; font-size: 11.5px; text-transform: uppercase;">Restablecer clave</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <strong style="color: #4A2F20;">Administrador</strong>
                                <span class="badge bg-info-subtle text-info border border-info-subtle ms-1 px-2 py-0.5" style="font-size: 11px;">Usted</span>
                            </td>
                            <td style="color: #4A2F20;">admin</td>
                            <td style="color: #4A2F20;">Admin</td>
                            <td>
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" style="font-size: 11.5px;">Activo</span>
                            </td>
                            <td>
                                <div class="d-flex gap-2 align-items-center">
                                    <input type="password" class="form-control usr-table-input" placeholder="Nueva clave">
                                    <button type="button" class="btn btn-sm btn-outline-secondary px-3 py-1" style="border-radius: 8px; font-size: 12.5px;">Cambiar</button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Footer oficial integrado unificado -->
        <?php include_once __DIR__ . '/../Layouts/footer.php'; ?>

    </div>
</div>

<script src="/panaderia/Assets/js/usuarios.js"></script>