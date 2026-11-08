<?php
/**
 * Controlador de Perfil - Panadería Don Leo
 * Ubicación: /panaderia/Controllers/PerfilController.php
 *
 * GET  -> devuelve el contenido de la ventana de perfil (lo carga dashboard.js)
 * POST -> guarda nombre, foto y banner y responde JSON
 */

session_start();
require_once __DIR__ . '/../Config/db.php';
require_once __DIR__ . '/../Models/UsuarioModel.php';
require_once __DIR__ . '/../Models/PerfilModel.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Acceso no autorizado');
}

$usuario_id = $_SESSION['user_id'];

// ---------- GET: mostrar la ventana de perfil ----------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    try {
        $userData = (new UsuarioModel($pdo))->obtenerConPerfil($usuario_id);
        $stats    = (new PerfilModel($pdo))->estadisticas($usuario_id);
    } catch (PDOException $e) {
        $userData = [];
        $stats    = ['tandas' => 0, 'pedidos' => 0, 'movimientos' => 0];
    }

    $buscarImagen = fn($url, $carpeta) => PerfilModel::rutaImagen($url, $carpeta, $usuario_id);

    $meses = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

    $nombreUsuario = ($userData['nombre_personalizado'] ?? '') ?: ($userData['nombre'] ?? ($_SESSION['user_nombre'] ?? 'Usuario'));
    $loginUsuario  = $userData['usuario'] ?? '';
    $rolUsuario    = ($userData['rol'] ?? '') === 'admin' ? 'Administrador' : 'Empleado';
    $activo        = (int)($userData['activo'] ?? 1) === 1;
    $inicial       = mb_strtoupper(mb_substr($nombreUsuario, 0, 1));
    $fotoPerfil    = $buscarImagen($userData['foto_url'] ?? '', 'perfil');
    $bannerPerfil  = $buscarImagen($userData['banner_url'] ?? '', 'banner') ?: '/panaderia/Assets/img/bg-panaderia.jpg';
    $miembroDesde  = '';
    if (!empty($userData['creado'])) {
        $ts = strtotime($userData['creado']);
        $miembroDesde = date('j', $ts) . ' de ' . $meses[(int)date('n', $ts)] . ' de ' . date('Y', $ts);
    }

    require __DIR__ . '/../src/Views/Public/perfil.php';
    exit;
}

// ---------- POST: guardar cambios ----------
$perfilModel = new PerfilModel($pdo);
$nuevo_nombre = trim($_POST['nombre'] ?? '');

$dirPerfil = __DIR__ . '/../Assets/img/perfil/';
$dirBanner = __DIR__ . '/../Assets/img/banner/';
if (!is_dir($dirPerfil)) mkdir($dirPerfil, 0777, true);
if (!is_dir($dirBanner)) mkdir($dirBanner, 0777, true);

/**
 * Guarda la imagen subida como {usuario_id}.{ext} en $dir, borrando las de otras extensiones.
 * Devuelve la URL pública, o $urlActual si no se subió nada válido.
 */
function subirImagen($campo, $dir, $urlBase, $usuario_id, $urlActual) {
    if (!isset($_FILES[$campo]) || $_FILES[$campo]['error'] !== UPLOAD_ERR_OK) {
        return $urlActual;
    }

    $permitidas = ['jpg', 'jpeg', 'png', 'webp'];
    $ext = strtolower(pathinfo($_FILES[$campo]['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $permitidas)) {
        return $urlActual;
    }

    foreach ($permitidas as $e) {
        $antiguo = $dir . $usuario_id . '.' . $e;
        if (file_exists($antiguo)) unlink($antiguo);
    }

    $nombreArchivo = $usuario_id . '.' . $ext;
    if (move_uploaded_file($_FILES[$campo]['tmp_name'], $dir . $nombreArchivo)) {
        return $urlBase . $nombreArchivo;
    }
    return $urlActual;
}

header('Content-Type: application/json');

try {
    $perfilActual = $perfilModel->obtenerPorUsuario($usuario_id);

    $foto_url   = subirImagen('foto_archivo', $dirPerfil, '/panaderia/Assets/img/perfil/', $usuario_id, $perfilActual['foto_url'] ?? null);
    $banner_url = subirImagen('banner_archivo', $dirBanner, '/panaderia/Assets/img/banner/', $usuario_id, $perfilActual['banner_url'] ?? null);

    $perfilModel->guardar($usuario_id, $nuevo_nombre, $foto_url, $banner_url);

    if (!empty($nuevo_nombre)) {
        $_SESSION['user_nombre'] = $nuevo_nombre;
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Perfil actualizado con éxito',
        'nombre' => $nuevo_nombre
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
exit;
