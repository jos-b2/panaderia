<?php
/**
 * Modelo de Usuarios - Panadería Don Leo
 * Ubicación: /panaderia/Models/UsuarioModel.php
 */

class UsuarioModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function buscarActivoPorUsuario($usuario) {
        $stmt = $this->pdo->prepare("SELECT id, nombre, usuario, correo, clave, rol FROM usuarios WHERE usuario = :usuario AND activo = 1 LIMIT 1");
        $stmt->execute([':usuario' => $usuario]);
        return $stmt->fetch();
    }

    // Nuevo método para buscar usuario por correo (utilizado en la recuperación de contraseña)
    public function obtenerPorCorreo($correo) {
        $stmt = $this->pdo->prepare("SELECT id, nombre, usuario, correo, rol, activo FROM usuarios WHERE correo = :correo AND activo = 1 LIMIT 1");
        $stmt->execute([':correo' => $correo]);
        return $stmt->fetch();
    }

    public function obtenerConPerfil($id) {
        $stmt = $this->pdo->prepare("
            SELECT U.nombre, U.usuario, U.correo, U.rol, U.activo, U.creado, P.nombre_personalizado, P.foto_url, P.banner_url
            FROM usuarios U
            LEFT JOIN perfil P ON U.id = P.usuario_id
            WHERE U.id = :id
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: [];
    }

    public function listar() {
        return $this->pdo->query("
            SELECT U.id, U.nombre, U.usuario, U.correo, U.rol, U.activo, U.creado, P.foto_url
            FROM usuarios U
            LEFT JOIN perfil P ON P.usuario_id = U.id
            ORDER BY U.id ASC
        ")->fetchAll();
    }

    public function existe($usuario) {
        $stmt = $this->pdo->prepare("SELECT id FROM usuarios WHERE usuario = ?");
        $stmt->execute([$usuario]);
        return (bool)$stmt->fetch();
    }

    // Actualizado para recibir y guardar el correo electrónico
    public function crear($nombre, $usuario, $correo, $clave, $rol) {
        $hash = password_hash($clave, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("INSERT INTO usuarios (nombre, usuario, correo, clave, rol) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nombre, $usuario, $correo, $hash, $rol]);
    }

    /** Cambio de clave hecho por el administrador: también quita la obligación de cambiarla. */
    public function cambiarClave($id, $clave) {
        $hash = password_hash($clave, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("UPDATE usuarios SET clave = ?, debe_cambiar = 0 WHERE id = ?");
        $stmt->execute([$hash, $id]);
    }

    /** Cifra una clave que estaba guardada en texto plano. */
    public function guardarHash($id, $clave) {
        $stmt = $this->pdo->prepare("UPDATE usuarios SET clave = :nuevaClave WHERE id = :id");
        $stmt->execute([':nuevaClave' => password_hash($clave, PASSWORD_DEFAULT), ':id' => $id]);
    }

    public function actualizarNombre($id, $nombre) {
        $stmt = $this->pdo->prepare("UPDATE usuarios SET nombre = :nombre WHERE id = :id");
        $stmt->execute([':nombre' => $nombre, ':id' => $id]);
    }
}