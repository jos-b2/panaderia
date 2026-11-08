<?php
/**
 * Controlador de Autenticación - Panadería Don Leo
 * Ubicación: /panaderia/Controllers/AuthController.php
 *
 * GET  -> muestra el formulario de login
 * POST -> valida credenciales y responde JSON (lo llama login.js)
 */

session_start();
require_once __DIR__ . '/../Config/db.php';
require_once __DIR__ . '/../Models/UsuarioModel.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    require __DIR__ . '/../src/Views/Public/login.php';
    exit;
}

header('Content-Type: application/json');

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
