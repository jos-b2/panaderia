<?php
/**
 * Sesión y usuario actual - Panadería Don Leo
 * Ubicación: /panaderia/Config/current_user.php
 *
 * Lo incluye cada controlador protegido. Deja disponibles:
 *   $pdo, $usuario_id, $userData, $nombreUsuario, $rolUsuario
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: /panaderia/Controllers/AuthController.php");
    exit;
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../Models/UsuarioModel.php';

$usuario_id = $_SESSION['user_id'];

$userData = (new UsuarioModel($pdo))->obtenerConPerfil($usuario_id);

// Variables que usan el sidebar y las vistas
$nombreUsuario = ($userData['nombre_personalizado'] ?? '') ?: ($userData['nombre'] ?? 'Administrador');
$rolUsuario    = strtoupper($userData['rol'] ?? 'EMPLEADO');

// Guardar en sesión para el sidebar
$_SESSION['user_nombre'] = $nombreUsuario;
$_SESSION['user_rol']    = $rolUsuario;
$_SESSION['user_foto']   = $userData['foto_url'] ?? '';

// Foto de perfil real (busca el archivo aunque la ruta guardada no coincida); "" si no tiene
require_once __DIR__ . '/../Models/PerfilModel.php';
$fotoUsuario = PerfilModel::rutaImagen($userData['foto_url'] ?? '', 'perfil', $usuario_id);

/**
 * Corta el acceso si el usuario actual no es administrador.
 * Se llama al inicio de los controladores exclusivos de administración
 * (Usuarios, Respaldos), antes de procesar formularios o descargas.
 * El rol se lee de la base en cada petición: si se lo quitan a alguien,
 * pierde el acceso de inmediato.
 */
function soloAdministrador() {
    global $rolUsuario, $nombreUsuario;
    if ($rolUsuario !== 'ADMIN') {
        http_response_code(403);
        require __DIR__ . '/../src/Views/Public/sin_permiso.php';
        exit;
    }
}
