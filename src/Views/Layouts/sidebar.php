<?php
$pagina_activa  = $pagina_activa ?? 'panel';
$nombre_usuario = $_SESSION['user_nombre'] ?? ($nombreUsuario ?? 'Administrador');
$rol_usuario    = strtolower($_SESSION['user_rol'] ?? ($rolUsuario ?? 'EMPLEADO'));
$usuario_id     = $_SESSION['user_id'] ?? 0;

$foto_url = $_SESSION['user_foto'] ?? '';
$ruta_encontrada = '';

if (!empty($foto_url)) {
    $ruta_fisica = $_SERVER['DOCUMENT_ROOT'] . $foto_url;
    if (file_exists($ruta_fisica)) {
        $ruta_encontrada = $foto_url;
    }
}

if (empty($ruta_encontrada) && $usuario_id) {
    $extensiones = ['jpg', 'jpeg', 'png', 'webp'];
    foreach ($extensiones as $ext) {
        $ruta_relativa = "/panaderia/Assets/img/perfil/{$usuario_id}.{$ext}";
        $ruta_fisica_convencion = $_SERVER['DOCUMENT_ROOT'] . $ruta_relativa;
        
        if (file_exists($ruta_fisica_convencion)) {
            $ruta_encontrada = $ruta_relativa;
            break;
        }
    }
}

$tiene_foto = !empty($ruta_encontrada);
?>

<link rel="stylesheet" href="/panaderia/Assets/css/siderbar.css">

<button class="mobile-burger" id="burger" aria-label="Abrir menú">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
</button>

<aside class="side" id="side">
    <div class="brand">
        <div class="brand-mark" id="brandToggle" title="Contraer / Expandir menú" style="cursor: pointer;">
            <img src="/panaderia/Assets/img/pan_orneado.png" alt="Panadería Don Leo">
        </div>
        <div class="brand-text">
            <strong>Panaderia Don Leo</strong>
            <small>Panel de Control</small>
        </div>
    </div>

    <nav>
        <a href="dashboard.php" class="<?php echo ($pagina_activa === 'menu') ? 'on' : ''; ?>" title="Menú">
            <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            <span>Menú</span>
        </a>

        <a href="panel.php" class="<?php echo ($pagina_activa === 'panel') ? 'on' : ''; ?>" title="Panel">
            <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            <span>Panel</span>
        </a>

        <a href="insumos.php" class="<?php echo ($pagina_activa === 'insumos') ? 'on' : ''; ?>" title="Materia prima">
            <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8l-9-5-9 5v8l9 5 9-5z"/><path d="M3 8l9 5 9-5M12 13v8"/></svg>
            <span>Materia prima</span>
        </a>

        <a href="recetas.php" class="<?php echo ($pagina_activa === 'recetas') ? 'on' : ''; ?>" title="Recetas">
            <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h12a3 3 0 0 1 3 3v13H7a3 3 0 0 1-3-3z"/><path d="M8 4v13"/></svg>
            <span>Recetas</span>
        </a>

        <a href="produccion.php" class="<?php echo ($pagina_activa === 'produccion') ? 'on' : ''; ?>" title="Producción">
            <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2s5 4 5 9a5 5 0 0 1-10 0c0-2 1-3 2-4 0 2 1 3 2 3 0-3 1-5 1-8z"/></svg>
            <span>Producción</span>
        </a>

        <a href="pedidos.php" class="<?php echo ($pagina_activa === 'pedidos') ? 'on' : ''; ?>" title="Pedidos">
            <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="12" height="17" rx="2"/><path d="M9 4V3h6v1M9 10h6M9 14h6"/></svg>
            <span>Pedidos</span>
        </a>

        <a href="arqueo.php" class="<?php echo ($pagina_activa === 'arqueo') ? 'on' : ''; ?>" title="Arqueo diario">
            <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v18M5 21h14M5 7h14"/><path d="M5 7l-3 7a3 3 0 0 0 6 0zM19 7l-3 7a3 3 0 0 0 6 0z"/></svg>
            <span>Arqueo diario</span>
        </a>

        <?php if ($rol_usuario === 'admin'): ?>
            <div class="side-section-label" style="padding: 10px 12px 4px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; color: #d9c3a5; font-weight: bold;">Administración</div>

            <a href="usuarios.php" class="<?php echo ($pagina_activa === 'usuarios') ? 'on' : ''; ?>" title="Usuarios">
                <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.5"/><path d="M2 20a7 7 0 0 1 14 0M17 4a3.5 3.5 0 0 1 0 7M22 20a7 7 0 0 0-4-6"/></svg>
                <span>Usuarios</span>
            </a>
            
            <a href="respaldo.php" class="<?php echo ($pagina_activa === 'respaldo') ? 'on' : ''; ?>" title="Respaldos">
                <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="8" ry="3"/><path d="M4 5v6c0 1.7 3.6 3 8 3s8-1.3 8-3V5M4 11v6c0 1.7 3.6 3 8 3s8-1.3 8-3v-6"/></svg>
                <span>Respaldos</span>
            </a>
        <?php endif; ?>
    </nav>

    <div class="side-foot">
        <div class="side-user-box d-flex align-items-center gap-3 px-2 py-2 mb-1" style="background: rgba(255,255,255,0.06); border-radius: 10px;">
            <div style="width: 40px; height: 40px; border-radius: 50%; overflow: hidden; background: #D4A373; flex-shrink: 0; display: flex; align-items: center; justify-content: center; border: 1.5px solid rgba(255,255,255,0.15);">
                <?php if ($tiene_foto): ?>
                    <img src="<?php echo htmlspecialchars($ruta_encontrada); ?>" alt="Perfil" style="width: 100%; height: 100%; object-fit: cover;">
                <?php else: ?>
                    <span style="font-weight: 700; color: #2d1b10; font-size: 16px;">
                        <?php echo strtoupper(substr($nombre_usuario, 0, 1)); ?>
                    </span>
                <?php endif; ?>
            </div>

            <div class="side-user-info" style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; line-height: 1.3;">
                <div style="font-size: 14.5px; font-weight: 700; color: #ffffff;"><?php echo htmlspecialchars($nombre_usuario); ?></div>
                <span style="font-size: 11px; color: #D4A373; text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em;"><?php echo htmlspecialchars($rol_usuario); ?></span>
            </div>
        </div>

        <button onclick="confirmarSalida()" title="Cerrar sesión" class="w-100 btn btn-sm d-flex align-items-center justify-content-center gap-2 py-2" style="background: rgba(239, 68, 68, 0.15); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 8px; font-size: 13px; transition: background 0.2s;">
            <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            <span>Cerrar sesión</span>
        </button>
    </div>
</aside>

<div class="scrim" id="scrim"></div>

<script src="/panaderia/Assets/js/siderbar.js"></script>