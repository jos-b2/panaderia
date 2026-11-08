<?php
/**
 * Vista: ventana de Perfil - Panadería Don Leo
 * Ubicación: /panaderia/src/Views/Public/perfil.php
 * La prepara Controllers/PerfilController.php (GET). Estilos en Assets/css/dashboard.css
 *
 * IDs que usa dashboard.js: modalBannerDiv, modalAvatarImg, modalNombreText,
 * btnEditIcon, btnEditText, secEditPerfil, formEditarPerfil
 */
if (realpath($_SERVER["SCRIPT_FILENAME"]) === __FILE__) {
    header("Location: /panaderia/Controllers/DashboardController.php");
    exit;
}
?>

<article class="pf-card">

    <button type="button" class="pf-close" onclick="cerrarPerfilModal()" aria-label="Cerrar perfil">&times;</button>

    <!-- Portada -->
    <div id="modalBannerDiv" class="pf-cover" style="background-image: url('<?= htmlspecialchars($bannerPerfil) ?>');">
        <span class="pf-cover-kicker">Panadería Don Leo &middot; Perfil</span>
    </div>

    <!-- Identidad -->
    <header class="pf-head">
        <div class="pf-avatar <?= $fotoPerfil ? 'has-photo' : '' ?>">
            <img id="modalAvatarImg" src="<?= htmlspecialchars($fotoPerfil) ?>" alt="Foto de perfil">
            <span class="pf-initial"><?= htmlspecialchars($inicial) ?></span>
        </div>

        <div class="pf-identity">
            <span class="pf-role"><i class="ph-duotone <?= $rolUsuario === 'Administrador' ? 'ph-crown' : 'ph-user' ?>"></i><?= $rolUsuario ?></span>
            <h3 id="modalNombreText" class="pf-name"><?= htmlspecialchars($nombreUsuario) ?></h3>
            <div class="pf-handle">
                <?php if ($loginUsuario !== ''): ?>
                    <span>@<?= htmlspecialchars($loginUsuario) ?></span>
                <?php endif; ?>
                <?php if ($miembroDesde !== ''): ?>
                    <span><i class="ph-duotone ph-calendar-blank"></i>Miembro desde el <?= $miembroDesde ?></span>
                <?php endif; ?>
            </div>
        </div>

        <button type="button" class="pf-edit-btn" onclick="toggleEdicionPerfil()">
            <i id="btnEditIcon" class="ph-duotone ph-pencil-simple me-1"></i>
            <span id="btnEditText">Editar perfil</span>
        </button>
    </header>

    <div class="pf-body">

        <!-- Actividad real del usuario -->
        <section class="pf-section">
            <h4 class="pf-section-title">Actividad registrada</h4>
            <div class="pf-stats">
                <div class="pf-stat">
                    <span class="pf-stat-icon"><i class="ph-duotone ph-fire"></i></span>
                    <strong><?= number_format($stats['tandas']) ?></strong>
                    <small>Tandas horneadas</small>
                </div>
                <div class="pf-stat">
                    <span class="pf-stat-icon"><i class="ph-duotone ph-clipboard-text"></i></span>
                    <strong><?= number_format($stats['pedidos']) ?></strong>
                    <small>Pedidos registrados</small>
                </div>
                <div class="pf-stat">
                    <span class="pf-stat-icon"><i class="ph-duotone ph-package"></i></span>
                    <strong><?= number_format($stats['movimientos']) ?></strong>
                    <small>Movimientos de inventario</small>
                </div>
            </div>
        </section>

        <!-- Datos de la cuenta -->
        <section class="pf-section">
            <h4 class="pf-section-title">Datos de la cuenta</h4>
            <dl class="pf-info">
                <div><dt>Usuario</dt><dd><?= htmlspecialchars($loginUsuario ?: '—') ?></dd></div>
                <div><dt>Rol</dt><dd><?= $rolUsuario ?></dd></div>
                <div>
                    <dt>Estado</dt>
                    <dd class="<?= $activo ? 'pf-ok' : 'pf-off' ?>"><i class="ph-fill ph-circle"></i><?= $activo ? 'Activo' : 'Inactivo' ?></dd>
                </div>
                <div><dt>ID</dt><dd>#<?= (int)$usuario_id ?></dd></div>
            </dl>
        </section>

        <!-- Edición (se despliega con "Editar perfil") -->
        <section id="secEditPerfil" class="edit-profile-section">
            <form id="formEditarPerfil" class="pf-form" action="/panaderia/Controllers/PerfilController.php" method="POST" enctype="multipart/form-data">
                <h4 class="pf-section-title">Personalizar perfil</h4>

                <label class="form-label" for="pfNombre">Nombre visible</label>
                <input type="text" id="pfNombre" name="nombre" class="form-control mb-3" value="<?= htmlspecialchars($nombreUsuario) ?>" required>

                <div class="pf-uploads">
                    <label class="pf-drop">
                        <input type="file" name="foto_archivo" accept="image/png, image/jpeg, image/webp" data-preview="avatar">
                        <i class="ph-duotone ph-user-circle-plus"></i>
                        <span class="pf-drop-title">Foto de perfil</span>
                        <span class="pf-drop-name">JPG, PNG o WEBP</span>
                    </label>
                    <label class="pf-drop">
                        <input type="file" name="banner_archivo" accept="image/png, image/jpeg, image/webp" data-preview="banner">
                        <i class="ph-duotone ph-image"></i>
                        <span class="pf-drop-title">Portada</span>
                        <span class="pf-drop-name">JPG, PNG o WEBP</span>
                    </label>
                </div>

                <div class="pf-form-actions">
                    <button type="button" class="btn btn-outline-secondary" onclick="abrirPerfilModal()">Cancelar</button>
                    <button type="submit" class="btn btn-brand"><i class="ph ph-check"></i>Guardar cambios</button>
                </div>
            </form>
        </section>

    </div>
</article>
