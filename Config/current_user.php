<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: /panaderia/src/Views/Public/login.php");
    exit;
}

// Ruta absoluta segura para la base de datos sin importar el controlador que lo llame
require_once $_SERVER['DOCUMENT_ROOT'] . '/panaderia/config/db.php';

$usuario_id = $_SESSION['user_id'];

// Consultar los datos de usuario y perfil de forma global
$stmtUser = $pdo->prepare("
    SELECT U.nombre, U.rol, P.nombre_personalizado, P.foto_url 
    FROM usuarios U 
    LEFT JOIN perfil P ON U.id = P.usuario_id 
    WHERE U.id = :id
");
$stmtUser->execute([':id' => $usuario_id]);
$userData = $stmtUser->fetch(PDO::FETCH_ASSOC);

// Variables exactas que tu sidebar necesita y reconoce
$nombreUsuario = $userData['nombre_personalizado'] ?: ($userData['nombre'] ?? 'Administrador');
$rolUsuario    = strtoupper($userData['rol'] ?? 'EMPLEADO');

// Guardar en sesión para blindarlo ante cualquier cambio de vista
$_SESSION['user_nombre'] = $nombreUsuario;
$_SESSION['user_rol']    = $rolUsuario;
$_SESSION['user_foto']   = $userData['foto_url'] ?? '';