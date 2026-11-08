<?php
/**
 * Modelo de Perfil de Usuario - Panadería Don Leo
 * Ubicación: /panaderia/Models/PerfilModel.php
 */

class PerfilModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function obtenerPorUsuario($usuario_id) {
        $stmt = $this->pdo->prepare("SELECT foto_url, banner_url FROM perfil WHERE usuario_id = :usuario_id");
        $stmt->execute([':usuario_id' => $usuario_id]);
        return $stmt->fetch() ?: [];
    }

    /**
     * Guarda nombre, foto y banner. Si viene nombre, también actualiza el nombre en 'usuarios'.
     */
    public function guardar($usuario_id, $nombre, $foto_url, $banner_url) {
        try {
            $this->pdo->beginTransaction();

            if (!empty($nombre)) {
                $stmtUser = $this->pdo->prepare("UPDATE usuarios SET nombre = :nombre WHERE id = :id");
                $stmtUser->execute([':nombre' => $nombre, ':id' => $usuario_id]);
            }

            $stmtExist = $this->pdo->prepare("SELECT id FROM perfil WHERE usuario_id = :usuario_id");
            $stmtExist->execute([':usuario_id' => $usuario_id]);

            $params = [
                ':usuario_id' => $usuario_id,
                ':nombre'     => $nombre,
                ':foto'       => $foto_url,
                ':banner'     => $banner_url
            ];

            if ($stmtExist->fetch()) {
                $stmt = $this->pdo->prepare("UPDATE perfil SET nombre_personalizado = :nombre, foto_url = :foto, banner_url = :banner WHERE usuario_id = :usuario_id");
            } else {
                $stmt = $this->pdo->prepare("INSERT INTO perfil (usuario_id, nombre_personalizado, foto_url, banner_url) VALUES (:usuario_id, :nombre, :foto, :banner)");
            }
            $stmt->execute($params);

            $this->pdo->commit();
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }
}
