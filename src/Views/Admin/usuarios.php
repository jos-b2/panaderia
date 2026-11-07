<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/panaderia/Config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/panaderia/Config/current_user.php';

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
                $stmtCheck = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = ?");
                $stmtCheck->execute([$usuario]);

                if ($stmtCheck->fetch()) {
                    $mensaje = "El usuario '{$usuario}' ya existe.";
                    $tipo_alerta = 'danger';
                } else {
                    $hash = password_hash($clave, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, usuario, clave, rol) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$nombre, $usuario, $hash, $rol]);
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
                $hash = password_hash($nueva_clave, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE usuarios SET clave = ?, debe_cambiar = 0 WHERE id = ?");
                $stmt->execute([$hash, $id_usuario]);
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

$stmtUsuarios = $pdo->query("SELECT id, nombre, usuario, rol, activo FROM usuarios ORDER BY id ASC");
$lista_usuarios = $stmtUsuarios->fetchAll();

$usuario_actual_id = $_SESSION['usuario_id'] ?? ($_SESSION['id'] ?? 0);
$usuario_actual_nick = $_SESSION['usuario'] ?? '';

$pagina_activa = 'usuarios';
include_once __DIR__ . '/../Layouts/header.php';
?>

<link rel="stylesheet" href="/panaderia/Assets/css/siderbar.css">
<link rel="stylesheet" href="/panaderia/Assets/css/usuarios.css">

<div class="d-flex">
    <?php include_once __DIR__ . '/../Layouts/sidebar.php'; ?>

    <div class="main flex-grow-1">
        <div class="usr-header-section">
            <div class="d-flex justify-content-between align-items-baseline flex-wrap gap-2">
                <h2 class="fw-bold m-0" style="color: #6B4226; font-size: 1.5rem;">Usuarios</h2>
                <div class="text-muted small">
                    <?php 
                        $meses = ["", "ene", "feb", "mar", "abr", "may", "jun", "jul", "ago", "sep", "oct", "nov", "dic"];
                        echo date('j') . ' ' . $meses[(int)date('n')] . ' ' . date('Y');
                    ?>
                </div>
            </div>
        </div>

        <?php if ($mensaje !== ''): ?>
            <div class="alert alert-<?= $tipo_alerta ?> alert-dismissible fade show py-2 px-3 mb-0" role="alert" style="font-size: 13.5px; border-radius: 10px;">
                <?= htmlspecialchars($mensaje) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar" style="padding: 0.65rem;"></button>
            </div>
        <?php endif; ?>

        <div class="usr-grid-top">
            <div class="usr-card">
                <h3 class="usr-card-title">Nuevo usuario</h3>
                
                <form method="post" action="usuarios.php">
                    <input type="hidden" name="accion" value="crear">
                    <div class="row g-2 mb-2">
                        <div class="col-md-7">
                            <label class="usr-form-label">Nombre completo</label>
                            <input type="text" name="nombre" class="form-control usr-input" required>
                        </div>
                        <div class="col-md-5">
                            <label class="usr-form-label">Rol</label>
                            <select name="rol" class="form-select usr-input">
                                <option value="empleado">Empleado (opera el sistema)</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="usr-form-label">Usuario</label>
                            <input type="text" name="usuario" class="form-control usr-input" required>
                        </div>
                        <div class="col-md-6">
                            <label class="usr-form-label">Clave inicial</label>
                            <input type="password" name="clave" class="form-control usr-input" required>
                        </div>
                    </div>

                    <button type="submit" class="btn text-white px-3 py-2 usr-btn-submit">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        Crear usuario
                    </button>
                </form>
            </div>

            <div class="usr-card d-flex flex-column justify-content-center">
                <h3 class="usr-card-title">Roles de acceso</h3>
                <div class="text-muted usr-roles-list">
                    <div>
                        <strong style="color: #4A2F20;">Empleado:</strong> registra producción, inventario, pedidos y arqueos.
                    </div>
                    <div>
                        <strong style="color: #4A2F20;">Administrador:</strong> además gestiona usuarios, respaldos, anula tandas y elimina registros.
                    </div>
                </div>
            </div>
        </div>

        <div class="usr-card usr-table-card">
            <h3 class="usr-card-title">Usuarios registrados</h3>
            
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Usuario</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th>Restablecer clave</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($lista_usuarios)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">No hay usuarios registrados en la base de datos.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($lista_usuarios as $u): ?>
                                <?php $es_actual = ($u['id'] == $usuario_actual_id || ($usuario_actual_nick !== '' && $u['usuario'] === $usuario_actual_nick)); ?>
                                <tr>
                                    <td class="text-nowrap">
                                        <strong style="color: #4A2F20;"><?= htmlspecialchars($u['nombre']) ?></strong>
                                        <?php if ($es_actual): ?>
                                            <span class="badge bg-info-subtle text-info border border-info-subtle ms-1 px-2 py-0.5" style="font-size: 11px;">Usted</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="color: #4A2F20;"><?= htmlspecialchars($u['usuario']) ?></td>
                                    <td style="color: #4A2F20;"><?= $u['rol'] === 'admin' ? 'Admin' : 'Empleado' ?></td>
                                    <td>
                                        <?php if ($u['activo']): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" style="font-size: 11.5px;">Activo</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1" style="font-size: 11.5px;">Inactivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="post" action="usuarios.php" class="d-flex gap-2 align-items-center m-0">
                                            <input type="hidden" name="accion" value="cambiar_clave">
                                            <input type="hidden" name="id_usuario" value="<?= (int)$u['id'] ?>">
                                            <input type="password" name="nueva_clave" class="form-control usr-table-input" placeholder="Nueva clave" required>
                                            <button type="submit" class="btn btn-sm btn-outline-secondary px-3 py-1" style="border-radius: 8px; font-size: 12.5px;">Cambiar</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php include_once __DIR__ . '/../Layouts/footer.php'; ?>
    </div>
</div>

<script src="/panaderia/Assets/js/usuarios.js"></script>