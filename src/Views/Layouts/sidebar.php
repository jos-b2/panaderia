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

<link rel="stylesheet" href="/panaderia/Assets/css/siderbar.css?v=4">

<?php
// Opciones del menú: [clave de página, controlador, ícono Phosphor, texto]
$menu_principal = [
    ['menu',       'DashboardController.php',  'ph-house',          'Menú'],
    ['panel',      'PanelController.php',      'ph-squares-four',   'Panel'],
    ['insumos',    'InsumosController.php',    'ph-package',        'Materia prima'],
    ['recetas',    'RecetasController.php',    'ph-book-open-text', 'Recetas'],
    ['produccion', 'ProduccionController.php', 'ph-fire',           'Producción'],
    ['pedidos',    'PedidosController.php',    'ph-clipboard-text', 'Pedidos'],
    ['arqueo',     'ArqueoController.php',     'ph-cash-register',  'Arqueo diario'],
    ['reportes',   'ReportesController.php',   'ph-chart-line-up',  'Reportes'],
];
$menu_admin = [
    ['usuarios',   'UsuariosController.php',   'ph-users-three',    'Usuarios'],
    ['respaldo',   'RespaldoController.php',   'ph-database',       'Respaldos'],
];

$enlace_menu = function ($item) use ($pagina_activa) {
    [$clave, $url, $icono, $texto] = $item;
    $activo = $pagina_activa === $clave;
    // La opción activa usa el ícono relleno; las demás, duotono
    $estilo = $activo ? 'ph-fill' : 'ph-duotone';
    echo '<a href="' . $url . '" class="' . ($activo ? 'on' : '') . '" title="' . $texto . '">'
       . '<i class="' . $estilo . ' ' . $icono . ' ico"></i><span>' . $texto . '</span></a>';
};
?>

<!-- Barra superior (solo en tablet y celular) -->
<header class="mobile-topbar">
    <button class="mobile-burger" id="burger" aria-label="Abrir menú">
        <i class="ph ph-list"></i>
    </button>
    <span class="mobile-topbar-title">Panadería Don Leo</span>
</header>

<aside class="side" id="side">
    <div class="brand">
        <div class="brand-mark" id="brandToggle" title="Contraer / Expandir menú">
            <i class="ph-duotone ph-bread"></i>
        </div>
        <div class="brand-text">
            <strong>Don Leo</strong>
            <small>Panadería artesanal</small>
        </div>
    </div>

    <nav>
        <?php foreach ($menu_principal as $item) $enlace_menu($item); ?>

        <?php if ($rol_usuario === 'admin'): ?>
            <div class="side-section-label">Administración</div>
            <?php foreach ($menu_admin as $item) $enlace_menu($item); ?>
        <?php endif; ?>
    </nav>

    <div class="side-foot">
        <div class="side-user-box">
            <div class="side-avatar">
                <?php if ($tiene_foto): ?>
                    <img src="<?php echo htmlspecialchars($ruta_encontrada); ?>" alt="Perfil">
                <?php else: ?>
                    <span><?php echo strtoupper(substr($nombre_usuario, 0, 1)); ?></span>
                <?php endif; ?>
            </div>

            <div class="side-user-info">
                <div class="side-user-name"><?php echo htmlspecialchars($nombre_usuario); ?></div>
                <span class="side-user-role"><?php echo htmlspecialchars($rol_usuario); ?></span>
            </div>

            <button type="button" onclick="confirmarSalida()" title="Cerrar sesión" aria-label="Cerrar sesión" class="side-logout">
                <i class="ph-duotone ph-sign-out"></i>
            </button>
        </div>
    </div>
</aside>

<div class="scrim" id="scrim"></div>

<script src="/panaderia/Assets/js/siderbar.js?v=3"></script>
