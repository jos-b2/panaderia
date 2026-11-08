<?php
/**
 * Controlador de Usuarios - Panadería Don Leo
 * Ubicación: /panaderia/Controllers/UsuariosController.php
 */

require_once __DIR__ . '/../Config/current_user.php';
// UsuarioModel ya viene cargado desde current_user.php

$usuarioModel = new UsuarioModel($pdo);

$mensaje = '';
$tipo_alerta = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? 'crear';

    if ($accion === 'crear') {
        $nombre = trim($_POST['nombre'] ?? '');
        $usuario = trim($_POST['usuario'] ?? '');
        $clave = $_POST['clave'] ?? '';
        $rol = ($_POST['rol'] ?? 'empleado') === 'admin' ? 'admin' : 'empleado';

        if ($nombre !== '' && $usuario !== '' && $clave !== '') {
            try {
                if ($usuarioModel->existe($usuario)) {
                    $mensaje = "El usuario '{$usuario}' ya existe.";
                    $tipo_alerta = 'danger';
                } else {
                    $usuarioModel->crear($nombre, $usuario, $clave, $rol);
                    $mensaje = "Usuario creado correctamente.";
                    $tipo_alerta = 'success';
                }
            } catch (PDOException $e) {
                $mensaje = "Error al crear usuario: " . $e->getMessage();
                $tipo_alerta = 'danger';
            }
        } else {
            $mensaje = "Todos los campos son obligatorios.";
            $tipo_alerta = 'warning';
        }
    } elseif ($accion === 'cambiar_clave') {
        $id_usuario = (int)($_POST['id_usuario'] ?? 0);
        $nueva_clave = $_POST['nueva_clave'] ?? '';

        if ($id_usuario > 0 && trim($nueva_clave) !== '') {
            try {
                $usuarioModel->cambiarClave($id_usuario, $nueva_clave);
                $mensaje = "Clave actualizada correctamente.";
                $tipo_alerta = 'success';
            } catch (PDOException $e) {
                $mensaje = "Error al actualizar la clave.";
                $tipo_alerta = 'danger';
            }
        } else {
            $mensaje = "Ingresa una nueva clave válida.";
            $tipo_alerta = 'warning';
        }
    }
}

$lista_usuarios = $usuarioModel->listar();

$usuario_actual_id = $usuario_id;
$usuario_actual_nick = $_SESSION['usuario'] ?? '';

$pagina_activa = 'usuarios';

require __DIR__ . '/../src/Views/Admin/usuarios.php';
