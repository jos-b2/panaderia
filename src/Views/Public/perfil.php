<?php
/**
 * Componente / Vista de Perfil - Panadería Don Leo
 * Ubicación: /panaderia/src/Views/Public/perfil.php
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    exit('Acceso no autorizado');
}

require_once __DIR__ . '/../../../config/db.php';
$usuario_id =$_SESSION['user_id'];

try {
    $stmt =$pdo->prepare("
        SELECT U.nombre, U.rol, P.nombre_personalizado, P.foto_url, P.banner_url 
        FROM usuarios U 
        LEFT JOIN perfil P ON U.id = P.usuario_id 
        WHERE U.id = :id
    ");
    $stmt->execute([':id' =>$usuario_id]);
    $userData =$stmt->fetch();
} catch (PDOException $e) {$userData = [];
}

$nombreUsuario = htmlspecialchars($userData['nombre_personalizado'] ?: ($_SESSION['user_nombre'] ?? 'Administrador'));
$rolUsuario    = htmlspecialchars(strtoupper($userData['rol'] ?? 'ADMIN'));
$fotoPerfil    =$userData['foto_url'] ?: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=400&auto=format&fit=crop';
$bannerPerfil  =$userData['banner_url'] ?: 'https://images.unsplash.com/photo-1545703549-7bdb1d01b734?q=80&w=600&auto=format&fit=crop';
?>

<div class="profile-card-modal-extended p-4">
    <!-- Botón de Cierre Superior -->
    <button type="button" class="profile-close-btn" onclick="cerrarPerfilModal()">&times;</button>

    <div class="row g-4 align-items-center">
        
        <!-- COLUMNA IZQUIERDA: Tarjeta Personal -->
        <div class="col-12 col-md-5">
            <div class="profile-left-card">
                <!-- Banner y Foto con IDs para actualización en tiempo real -->
                <div id="modalBannerDiv" class="profile-banner-modal" style="background-image: url('<?php echo htmlspecialchars($bannerPerfil); ?>');">
                    <img id="modalAvatarImg" src="<?php echo htmlspecialchars($fotoPerfil); ?>" alt="Avatar" class="profile-avatar-modal">
                </div>

                <!-- Datos Principales -->
                <div class="text-center pt-5 px-3 pb-4">
                    <h4 id="modalNombreText" class="fw-bold mt-2 mb-1" style="color: #6B4226;"><?php echo htmlspecialchars($nombreUsuario); ?></h4>
                    <span class="badge rounded-pill px-3 py-1 my-1" style="background-color: #D4A373; color: #6B4226; font-weight: 700;">
                        <?php echo $rolUsuario; ?>
                    </span>

                    <div class="d-flex justify-content-around my-3 py-2 border-top border-bottom" style="border-color: #e0d6c3 !important;">
                        <div>
                            <small class="text-muted d-block" style="font-size: 0.75rem;">ID Usuario</small>
                            <span class="fw-bold" style="color: #6B4226;">#<?php echo $usuario_id; ?></span>
                        </div>
                        <div class="border-start" style="border-color: #e0d6c3 !important;"></div>
                        <div>
                            <small class="text-muted d-block" style="font-size: 0.75rem;">Estado</small>
                            <span class="fw-bold" style="color: #606C38;"><i class="fa-solid fa-circle me-1" style="font-size: 0.5rem;"></i>Activo</span>
                        </div>
                    </div>

                    <!-- Botón para desplegar/ocultar edición -->
                    <button type="button" onclick="toggleEdicionPerfil()" class="btn w-100 py-2 shadow-sm fw-bold border-0" style="background-color: #6B4226; color: #FEFAE0; border-radius: 1rem; transition: background 0.2s;">
                        <i id="btnEditIcon" class="fa-solid fa-pen-to-square me-1"></i>
                        <span id="btnEditText">Editar Perfil</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- COLUMNA DERECHA: Rendimiento y Formulario Desplegable -->
        <div class="col-12 col-md-7 d-flex flex-column justify-content-center">
            
            <div>
                <h5 class="fw-bold mb-3" style="color: #6B4226;">
                    <i class="fa-solid fa-chart-line me-2" style="color: #D4A373;"></i>Resumen de Rendimiento
                </h5>

                <!-- 2 Cards de Rendimiento -->
                <div class="row g-3">
                    <div class="col-12 col-sm-6">
                        <div class="metric-card-custom">
                            <div class="metric-icon-box">
                                <i class="fa-solid fa-fire-burner"></i>
                            </div>
                            <div>
                                <span class="d-block text-muted small fw-semibold" style="font-size: 0.75rem;">Producción Diaria</span>
                                <span class="fs-5 fw-bold" style="color: #6B4226;">12 TANDAS</span>
                                <small class="text-success d-block" style="font-size: 0.65rem; font-weight: 600;">
                                    <i class="fa-solid fa-arrow-up me-1"></i>+15% esta semana
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6">
                        <div class="metric-card-custom">
                            <div class="metric-icon-box">
                                <i class="fa-solid fa-clipboard-check"></i>
                            </div>
                            <div>
                                <span class="d-block text-muted small fw-semibold" style="font-size: 0.75rem;">Encargos Listos</span>
                                <span class="fs-5 fw-bold" style="color: #6B4226;">28 PEDIDOS</span>
                                <small class="text-success d-block" style="font-size: 0.65rem; font-weight: 600;">
                                    <i class="fa-solid fa-check me-1"></i>100% Entregados
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN OCULTA: Formulario con ID para interceptar envío con JS -->
            <div id="secEditPerfil" class="edit-profile-section">
                <div class="p-3 rounded-4" style="background-color: #FFFFFF; border: 1px solid #D4A373;">
                    <h6 class="fw-bold mb-3" style="color: #6B4226; font-size: 0.95rem;">
                        <i class="fa-solid fa-sliders me-2"></i>Personalizar Datos Visuales
                    </h6>

                    <form id="formEditarPerfil" action="../../../Controllers/PerfilController.php" method="POST" enctype="multipart/form-data">
                        <div class="row g-2">
                            <div class="col-12">
                                <label class="form-label small fw-bold mb-1" style="color: #6B4226;">Nombre Visual:</label>
                                <input type="text" name="nombre" class="form-control form-control-sm rounded-pill px-3" value="<?php echo htmlspecialchars($nombreUsuario); ?>" required>
                            </div>
                            <div class="col-12 col-sm-6">
                                <label class="form-label small fw-bold mb-1" style="color: #6B4226;">Subir Foto de Perfil:</label>
                                <input type="file" name="foto_archivo" class="form-control form-control-sm rounded-pill px-3" accept="image/png, image/jpeg, image/webp">
                            </div>
                            <div class="col-12 col-sm-6">
                                <label class="form-label small fw-bold mb-1" style="color: #6B4226;">Subir Banner:</label>
                                <input type="file" name="banner_archivo" class="form-control form-control-sm rounded-pill px-3" accept="image/png, image/jpeg, image/webp">
                            </div>
                        </div>

                        <div class="mt-3 text-end">
                            <button type="submit" class="btn btn-sm px-4 fw-bold" style="background-color: #6B4226; color: #FEFAE0; border-radius: 1rem;">
                                Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>

    </div>
</div>