<?php
/**
 * Vista: Usuarios - Panadería Don Leo
 * Ubicación: /panaderia/src/Views/Admin/usuarios.php
 * La prepara Controllers/UsuariosController.php
 */

// Si se abre esta vista directamente (dirección vieja), ir a su controlador
if (realpath($_SERVER["SCRIPT_FILENAME"]) === __FILE__) {
    header("Location: /panaderia/Controllers/UsuariosController.php");
    exit;
}

// Inicializar variables por defecto para evitar advertencias del editor
$mensaje = $mensaje ?? '';
$tipo_alerta = $tipo_alerta ?? '';
$resumen = $resumen ?? ['total' => 0, 'admins' => 0, 'empleados' => 0, 'activos' => 0];
$lista_usuarios = $lista_usuarios ?? [];
$usuario_actual_id = $usuario_actual_id ?? 0;
$usuario_actual_nick = $usuario_actual_nick ?? '';

include_once __DIR__ . '/../Layouts/header.php';
?>

<link rel="stylesheet" href="/panaderia/Assets/css/siderbar.css?v=4">
<link rel="stylesheet" href="/panaderia/Assets/css/usuarios.css?v=4">

<div class="d-flex">
    <?php include_once __DIR__ . '/../Layouts/sidebar.php'; ?>

    <div class="main">
        <div class="page-head usr-header-section">
            <div class="d-flex justify-content-between align-items-baseline flex-wrap gap-2">
                <div>
                    <h2 class="fw-bold m-0 page-title">Usuarios</h2>
                    <p class="mb-0">Gestiona quién puede entrar al sistema y qué puede hacer.</p>
                </div>
                <div class="text-muted small">
                    <?php 
                        $meses = ["", "ene", "feb", "mar", "abr", "may", "jun", "jul", "ago", "sep", "oct", "nov", "dic"];
                        echo date('j') . ' ' . $meses[(int)date('n')] . ' ' . date('Y');
                    ?>
                </div>
            </div>
        </div>

        <?php if ($mensaje !== ''): ?>
            <div class="alert alert-<?= $tipo_alerta ?> alert-dismissible fade show py-2 px-3 mb-0" role="alert">
                <?= htmlspecialchars($mensaje) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        <?php endif; ?>

        <!-- Resumen del equipo -->
        <div class="usr-stats">
            <div class="usr-stat">
                <span class="usr-stat-icon"><i class="ph-duotone ph-users-three"></i></span>
                <div><strong><?= $resumen['total'] ?></strong><small>Usuarios</small></div>
            </div>
            <div class="usr-stat">
                <span class="usr-stat-icon is-dark"><i class="ph-duotone ph-crown"></i></span>
                <div><strong><?= $resumen['admins'] ?></strong><small>Administradores</small></div>
            </div>
            <div class="usr-stat">
                <span class="usr-stat-icon"><i class="ph-duotone ph-chef-hat"></i></span>
                <div><strong><?= $resumen['empleados'] ?></strong><small>Empleados</small></div>
            </div>
            <div class="usr-stat">
                <span class="usr-stat-icon is-green"><i class="ph-duotone ph-seal-check"></i></span>
                <div><strong><?= $resumen['activos'] ?></strong><small>Activos</small></div>
            </div>
        </div>

        <div class="usr-layout">

            <!-- Directorio del equipo -->
            <section class="usr-card usr-directory">
                <div class="usr-directory-head">
                    <h3 class="usr-card-title mb-0">Equipo de la panadería</h3>

                    <div class="usr-tools">
                        <label class="usr-search">
                            <i class="ph ph-magnifying-glass"></i>
                            <input type="search" id="usrBuscar" placeholder="Buscar nombre o usuario" autocomplete="off">
                        </label>
                        <div class="usr-filter" role="group" aria-label="Filtrar por rol">
                            <button type="button" class="active" data-rol="todos">Todos</button>
                            <button type="button" data-rol="admin">Admin</button>
                            <button type="button" data-rol="empleado">Empleados</button>
                        </div>
                    </div>
                </div>

                <?php if (empty($lista_usuarios)): ?>
                    <div class="empty-state py-5"><i class="ph-duotone ph-users-three"></i>No hay usuarios registrados.</div>
                <?php else: ?>
                    <div class="usr-grid" id="usrGrid">
                        <?php foreach ($lista_usuarios as $u): ?>
                            <?php
                                $es_actual = ($u['id'] == $usuario_actual_id || ($usuario_actual_nick !== '' && $u['usuario'] === $usuario_actual_nick));
                                $esAdmin = $u['rol'] === 'admin';
                            ?>
                            <article class="usr-person <?= $esAdmin ? 'is-admin' : 'is-empleado' ?> <?= $u['activo'] ? '' : 'is-inactive' ?>"
                                     data-rol="<?= $esAdmin ? 'admin' : 'empleado' ?>"
                                     data-texto="<?= htmlspecialchars(mb_strtolower($u['nombre'] . ' ' . $u['usuario'])) ?>">

                                <div class="usr-person-top">
                                    <div class="usr-avatar">
                                        <?php if (!empty($u['foto'])): ?>
                                            <img src="<?= htmlspecialchars($u['foto']) ?>" alt="">
                                        <?php else: ?>
                                            <span><?= htmlspecialchars($u['inicial']) ?></span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="usr-person-id">
                                        <h4><?= htmlspecialchars($u['nombre']) ?></h4>
                                        <span class="usr-handle">@<?= htmlspecialchars($u['usuario']) ?></span>
                                    </div>

                                    <?php if ($es_actual): ?>
                                        <span class="usr-you" title="Es tu cuenta">Tú</span>
                                    <?php endif; ?>
                                </div>

                                <ul class="usr-person-meta">
                                    <li class="usr-role">
                                        <i class="ph-duotone <?= $esAdmin ? 'ph-crown' : 'ph-chef-hat' ?>"></i><?= $esAdmin ? 'Administrador' : 'Empleado' ?>
                                    </li>
                                    <li class="<?= $u['activo'] ? 'usr-on' : 'usr-off' ?>">
                                        <i class="ph-fill ph-circle"></i><?= $u['activo'] ? 'Activo' : 'Inactivo' ?>
                                    </li>
                                    <?php if (!empty($u['desde'])): ?>
                                        <li><i class="ph-duotone ph-calendar-blank"></i>Desde <?= $u['desde'] ?></li>
                                    <?php endif; ?>
                                </ul>

                                <!-- Restablecer clave (se despliega al pulsar) -->
                                <button type="button" class="usr-reset-toggle" aria-expanded="false">
                                    <i class="ph-duotone ph-key"></i>Restablecer clave<i class="ph ph-caret-down usr-caret"></i>
                                </button>

                                <form method="post" action="UsuariosController.php" class="usr-reset-form">
                                    <input type="hidden" name="accion" value="cambiar_clave">
                                    <input type="hidden" name="id_usuario" value="<?= (int)$u['id'] ?>">
                                    <div class="usr-pass">
                                        <input type="password" name="nueva_clave" class="form-control form-control-sm" placeholder="Nueva clave" required>
                                        <button type="button" class="usr-eye" title="Mostrar u ocultar"><i class="ph-duotone ph-eye"></i></button>
                                    </div>
                                    <button type="submit" class="btn btn-sm btn-brand"><i class="ph-duotone ph-floppy-disk"></i>Guardar</button>
                                </form>
                            </article>
                        <?php endforeach; ?>
                    </div>
                    <div class="empty-state py-4 d-none" id="usrSinResultados"><i class="ph-duotone ph-magnifying-glass"></i>Ningún usuario coincide con la búsqueda.</div>
                <?php endif; ?>
            </section>

            <!-- Columna lateral: alta de usuario + roles -->
            <aside class="usr-aside">
                <section class="usr-card usr-new">
                    <div class="usr-new-head">
                        <span class="usr-new-icon"><i class="ph-duotone ph-user-plus"></i></span>
                        <div>
                            <h3 class="usr-card-title mb-0">Nuevo usuario</h3>
                            <small>Crea un acceso para alguien del equipo.</small>
                        </div>
                    </div>

                    <form method="post" action="UsuariosController.php">
                        <input type="hidden" name="accion" value="crear">

                        <label class="form-label" for="nuNombre">Nombre completo</label>
                        <input type="text" id="nuNombre" name="nombre" class="form-control mb-3" placeholder="Ej. María López" required>

                        <label class="form-label" for="nuCorreo">Correo electrónico</label>
                        <input type="email" id="nuCorreo" name="correo" class="form-control mb-3" placeholder="Ej. maria@correo.com" required>

                        <div class="row g-3 mb-3">
                            <div class="col-sm-6 col-xl-12 col-xxl-6">
                                <label class="form-label" for="nuUsuario">Usuario</label>
                                <input type="text" id="nuUsuario" name="usuario" class="form-control" placeholder="Ej. maria" required>
                            </div>
                            <div class="col-sm-6 col-xl-12 col-xxl-6">
                                <label class="form-label" for="nuClave">Clave inicial</label>
                                <div class="usr-pass">
                                    <input type="password" id="nuClave" name="clave" class="form-control" required>
                                    <button type="button" class="usr-eye" title="Mostrar u ocultar"><i class="ph-duotone ph-eye"></i></button>
                                </div>
                            </div>
                        </div>

                        <span class="form-label">Rol</span>
                        <div class="usr-role-pick">
                            <label>
                                <input type="radio" name="rol" value="empleado" checked>
                                <span><i class="ph-duotone ph-chef-hat"></i>Empleado</span>
                            </label>
                            <label>
                                <input type="radio" name="rol" value="admin">
                                <span><i class="ph-duotone ph-crown"></i>Administrador</span>
                            </label>
                        </div>

                        <button type="submit" class="btn btn-brand w-100 mt-3">
                            <i class="ph-duotone ph-user-plus"></i>Crear usuario
                        </button>
                    </form>
                </section>

                <section class="usr-card usr-roles">
                    <h3 class="usr-card-title">Roles de acceso</h3>
                    <div class="usr-role-info">
                        <span class="usr-role-badge"><i class="ph-duotone ph-chef-hat"></i></span>
                        <div>
                            <strong>Empleado</strong>
                            <p>Registra producción, inventario, pedidos y arqueos.</p>
                        </div>
                    </div>
                    <div class="usr-role-info">
                        <span class="usr-role-badge is-dark"><i class="ph-duotone ph-crown"></i></span>
                        <div>
                            <strong>Administrador</strong>
                            <p>Además gestiona usuarios, respaldos, anula tandas y elimina registros.</p>
                        </div>
                    </div>
                </section>
            </aside>

        </div>

        <?php include_once __DIR__ . '/../Layouts/footer.php'; ?>
    </div>
</div>

<script src="/panaderia/Assets/js/usuarios.js?v=2"></script>