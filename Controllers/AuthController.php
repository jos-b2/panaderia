<?php
/**
 * Controlador de Autenticación y Recuperación - Panadería Don Leo
 * Ubicación: /panaderia/Controllers/AuthController.php
 *
 * GET  -> muestra el formulario de login
 * POST -> valida credenciales o procesa la recuperación de contraseña (JSON)
 */

session_start();
require_once __DIR__ . '/../Config/db.php';
require_once __DIR__ . '/../Models/UsuarioModel.php';
require_once __DIR__ . '/../Assets/Helpers/EmailHelper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    require __DIR__ . '/../src/Views/Public/login.php';
    exit;
}

header('Content-Type: application/json');

$accion = $_POST['accion'] ?? 'login';

// ==========================================
// ACCIÓN: RECUPERAR CONTRASEÑA
// ==========================================
if ($accion === 'recuperar') {
    $correo = trim($_POST['correo'] ?? '');

    if (empty($correo)) {
        echo json_encode(['status' => 'error', 'message' => 'Por favor, ingresa tu correo electrónico.']);
        exit;
    }

    try {
        $usuarioModel = new UsuarioModel($pdo);
        
        // Buscamos al usuario por su correo corporativo o personal registrado
        $user = method_exists($usuarioModel, 'obtenerPorCorreo') ? $usuarioModel->obtenerPorCorreo($correo) : null;
        
        if (!$user) {
            echo json_encode(['status' => 'error', 'message' => 'El correo no se encuentra registrado en el sistema.']);
            exit;
        }

        $id_usuario = $user['id'] ?? $user['id_usuario'] ?? null;
        $nombre = $user['nombre'] ?? 'Usuario';

        if ($id_usuario) {
            // Generar una contraseña temporal segura
            $nuevaContrasena = bin2hex(random_bytes(4)); // Ej: a1b2c3d4
            
            // Actualizar el hash de la nueva contraseña temporal en la base de datos
            $usuarioModel->guardarHash($id_usuario, $nuevaContrasena);

            // Enviar correo mediante el Helper seguro fuera de la carpeta public
            $enviado = EmailHelper::enviarRecuperacionPassword($correo, $nombre, $nuevaContrasena);

            if ($enviado) {
                echo json_encode([
                    'status'  => 'success', 
                    'message' => 'Se han enviado las instrucciones y la contraseña temporal a tu correo electrónico.'
                ]);
            } else {
                echo json_encode([
                    'status'  => 'error', 
                    'message' => 'No se pudo enviar el correo de recuperación. Revisa la configuración del servidor SMTP.'
                ]);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No se pudo obtener el identificador del usuario.']);
        }
        exit;

    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error en el servidor: ' . $e->getMessage()]);
        exit;
    }
}

// ==========================================
// ACCIÓN: LOGIN TRADICIONAL
// ==========================================
$usuario = trim($_POST['usuario'] ?? '');
$clave   = trim($_POST['clave'] ?? '');

if (empty($usuario) || empty($clave)) {
    echo json_encode(['status' => 'error', 'message' => 'Por favor, completa todos los campos.']);
    exit;
}

try {
    $usuarioModel = new UsuarioModel($pdo);
    $user = $usuarioModel->buscarActivoPorUsuario($usuario);

    if (!$user) {
        echo json_encode(['status' => 'error', 'message' => 'Usuario o contraseña incorrectos.']);
        exit;
    }

    $loginExitoso = false;

    // 1. Verificación mediante hash seguro
    if (password_verify($clave, $user['clave'])) {
        $loginExitoso = true;
    }
    // 2. Soporte para contraseña en texto plano (modificada manualmente en la BD)
    else if ($clave === $user['clave']) {
        $loginExitoso = true;
        // Auto-cifrar la clave en la BD
        $usuarioModel->guardarHash($user['id'], $clave);
    }

    if ($loginExitoso) {
        $_SESSION['user_id']     = $user['id'];
        $_SESSION['user_nombre'] = $user['nombre'];
        $_SESSION['user_rol']    = $user['rol'];

        echo json_encode([
            'status'   => 'success',
            'message'  => '¡Bienvenido al sistema!',
            'redirect' => '/panaderia/Controllers/DashboardController.php'
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Usuario o contraseña incorrectos.']);
    }
    exit;

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error en el servidor: ' . $e->getMessage()]);
    exit;
}