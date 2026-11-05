<?php
/**
 * Controlador de Perfil con Transacción y Respuesta AJAX - Panadería Don Leo
 * Ubicación: /panaderia/Controllers/PerfilController.php
 */

session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Acceso no autorizado');
}

$usuario_id =$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nuevo_nombre = trim($_POST['nombre'] ?? '');
    
    // Directorios base de subida
    $dirPerfil = __DIR__ . '/../Assets/img/perfil/';$dirBanner = __DIR__ . '/../Assets/img/banner/';

    if (!is_dir($dirPerfil)) mkdir($dirPerfil, 0777, true);
    if (!is_dir($dirBanner)) mkdir($dirBanner, 0777, true);

    try {
        // Iniciar transacción PDO
        $pdo->beginTransaction();

        // 1. Actualizar el nombre en la tabla principal 'usuarios'
        if (!empty($nuevo_nombre)) {
            $stmtUser =$pdo->prepare("UPDATE usuarios SET nombre = :nombre WHERE id = :id");
            $stmtUser->execute([':nombre' => $nuevo_nombre, ':id' =>$usuario_id]);
            
            $_SESSION['user_nombre'] =$nuevo_nombre;
        }

        // Obtener rutas actuales de la tabla 'perfil'
        $stmtCheck =$pdo->prepare("SELECT foto_url, banner_url FROM perfil WHERE usuario_id = :usuario_id");
        $stmtCheck->execute([':usuario_id' =>$usuario_id]);
        $perfilActual =$stmtCheck->fetch();

        // 2. Procesar Foto de Perfil si se subió una nueva
        $foto_url =$perfilActual['foto_url'] ?? null;
        if (isset($_FILES['foto_archivo']) && $_FILES['foto_archivo']['error'] === UPLOAD_ERR_OK) {$fileTmpPath = $_FILES['foto_archivo']['tmp_name'];$fileExtension = strtolower(pathinfo($_FILES['foto_archivo']['name'], PATHINFO_EXTENSION));$allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

            if (in_array($fileExtension, $allowedExtensions)) {$nuevoNombreArchivo = $usuario_id . '.' .$fileExtension;
                $destinoFinal = $dirPerfil .$nuevoNombreArchivo;

                // Eliminar formatos anteriores del mismo ID
                foreach ($allowedExtensions as$ext) {
                    $archivoAntiguo =$dirPerfil . $usuario_id . '.' .$ext;
                    if (file_exists($archivoAntiguo)) unlink($archivoAntiguo);
                }

                if (move_uploaded_file($fileTmpPath,$destinoFinal)) {
                    $foto_url = '/panaderia/Assets/img/perfil/' .$nuevoNombreArchivo;
                }
            }
        }

        // 3. Procesar Banner si se subió uno nuevo
        $banner_url =$perfilActual['banner_url'] ?? null;
        if (isset($_FILES['banner_archivo']) && $_FILES['banner_archivo']['error'] === UPLOAD_ERR_OK) {$fileTmpPath = $_FILES['banner_archivo']['tmp_name'];$fileExtension = strtolower(pathinfo($_FILES['banner_archivo']['name'], PATHINFO_EXTENSION));$allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

            if (in_array($fileExtension, $allowedExtensions)) {$nuevoNombreBanner = $usuario_id . '.' .$fileExtension;
                $destinoFinalBanner = $dirBanner .$nuevoNombreBanner;

                foreach ($allowedExtensions as$ext) {
                    $archivoAntiguoBanner =$dirBanner . $usuario_id . '.' .$ext;
                    if (file_exists($archivoAntiguoBanner)) unlink($archivoAntiguoBanner);
                }

                if (move_uploaded_file($fileTmpPath,$destinoFinalBanner)) {
                    $banner_url = '/panaderia/Assets/img/banner/' .$nuevoNombreBanner;
                }
            }
        }

        // 4. Guardar o actualizar en la tabla 'perfil'
        $stmtExist =$pdo->prepare("SELECT id FROM perfil WHERE usuario_id = :usuario_id");
        $stmtExist->execute([':usuario_id' =>$usuario_id]);
        
        if ($stmtExist->fetch()) {
            $stmtUpdate =$pdo->prepare("UPDATE perfil SET nombre_personalizado = :nombre, foto_url = :foto, banner_url = :banner WHERE usuario_id = :usuario_id");
            $stmtUpdate->execute([
                ':nombre'     => $nuevo_nombre,
                ':foto'       => $foto_url,
                ':banner'     => $banner_url,
                ':usuario_id' => $usuario_id
            ]);
        } else {
            $stmtInsert =$pdo->prepare("INSERT INTO perfil (usuario_id, nombre_personalizado, foto_url, banner_url) VALUES (:usuario_id, :nombre, :foto, :banner)");
            $stmtInsert->execute([
                ':usuario_id' => $usuario_id,
                ':nombre'     => $nuevo_nombre,
                ':foto'       => $foto_url,
                ':banner'     => $banner_url
            ]);
        }

        // Confirmar transacción PDO
        $pdo->commit();

        // Responder con JSON para procesarlo vía AJAX en el cliente
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'message' => 'Perfil actualizado con éxito',
            'nombre' => $nuevo_nombre
        ]);
        exit;

    } catch (PDOException $e) {$pdo->rollBack();
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
        exit;
    }
}