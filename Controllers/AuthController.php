<?php
/**
 * Controlador de Autenticación Flexible - Panadería Don Leo
 * Ubicación: /panaderia/Controllers/AuthController.php
 */

session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $clave   = trim($_POST['clave'] ?? '');

    if (empty($usuario) || empty($clave)) {
        echo json_encode(['status' => 'error', 'message' => 'Por favor, completa todos los campos.']);
        exit;
    }

    try {
        // Consultar el usuario activo
        $stmt = $pdo->prepare("SELECT id, nombre, usuario, clave, rol FROM usuarios WHERE usuario = :usuario AND activo = 1 LIMIT 1");
        $stmt->execute([':usuario' => $usuario]);
        $user = $stmt->fetch();

        if (!$user) {
            echo json_encode(['status' => 'error', 'message' => 'Usuario o contraseña incorrectos.']);
            exit;
        }

        $loginExitoso = false;

        // 1. Intento de verificación mediante Hash seguro
        if (password_verify($clave, $user['clave'])) {
            $loginExitoso = true;
        } 
        // 2. Soporte para contraseña en texto plano (Modificada manualmente en BD)
        else if ($clave === $user['clave']) {
            $loginExitoso = true;
            
            // Auto-encriptar la nueva clave en la BD para proteger el sistema
            $nuevoHash = password_hash($clave, PASSWORD_DEFAULT);
            $updateStmt = $pdo->prepare("UPDATE usuarios SET clave = :nuevaClave WHERE id = :id");
            $updateStmt->execute([':nuevaClave' => $nuevoHash, ':id' => $user['id']]);
        }

        if ($loginExitoso) {
            $_SESSION['user_id']     = $user['id'];
            $_SESSION['user_nombre'] = $user['nombre'];
            $_SESSION['user_rol']    = $user['rol'];

            echo json_encode([
                'status'   => 'success',
                'message'  => '¡Bienvenido al sistema!',
                'redirect' => '../Admin/dashboard.php'
            ]);
            exit;
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Usuario o contraseña incorrectos.']);
            exit;
        }

    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error en el servidor: ' . $e->getMessage()]);
        exit;
    }
}