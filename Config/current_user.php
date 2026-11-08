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
