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

    /**
     * URL pública de la foto o banner de un usuario. Usa la ruta guardada si el archivo existe;
     * si no, busca {usuario_id}.{jpg|jpeg|png|webp} en Assets/img/{carpeta}. Devuelve "" si no hay.
     */
    public static function rutaImagen($urlGuardada, $carpeta, $usuario_id) {
        $raiz = realpath(__DIR__ . "/..");
        if ($urlGuardada && file_exists($raiz . str_replace("/panaderia", "", $urlGuardada))) {
            return $urlGuardada;
        }
        foreach (["jpg", "jpeg", "png", "webp"] as $ext) {
            if (file_exists("{$raiz}/Assets/img/{$carpeta}/{$usuario_id}.{$ext}")) {
                return "/panaderia/Assets/img/{$carpeta}/{$usuario_id}.{$ext}";
            }
        }
        return "";
    }

    public function obtenerPorUsuario($usuario_id) {
        $stmt = $this->pdo->prepare("SELECT foto_url, banner_url FROM perfil WHERE usuario_id = :usuario_id");
        $stmt->execute([':usuario_id' => $usuario_id]);
        return $stmt->fetch() ?: [];
    }

    /**
     * Actividad registrada por el usuario: tandas (no anuladas), pedidos y movimientos de inventario.
     */
    public function estadisticas($usuario_id) {
        $contar = function ($sql) use ($usuario_id) {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$usuario_id]);
            return (int)$stmt->fetchColumn();
        };

        return [
            'tandas'      => $contar("SELECT COUNT(*) FROM producciones WHERE usuario_id = ? AND anulada = 0"),
            'pedidos'     => $contar("SELECT COUNT(*) FROM pedidos WHERE usuario_id = ?"),
            'movimientos' => $contar("SELECT COUNT(*) FROM movimientos WHERE usuario_id = ?"),
        ];
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
