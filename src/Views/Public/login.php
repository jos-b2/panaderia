<?php
/**
 * Vista: Login - Panadería Don Leo
 * La muestra Controllers/AuthController.php (GET)
 */
if (realpath($_SERVER["SCRIPT_FILENAME"]) === __FILE__) {
    header("Location: /panaderia/Controllers/AuthController.php");
    exit;
}
?>
<?php include_once __DIR__ . '/../Layouts/header.php'; ?>

<link rel="stylesheet" href="/panaderia/Assets/css/login.css?v=6">

<script>
    document.body.className = 'login-body';
</script>

<div class="login-shell">

    <section class="login-visual" aria-hidden="true">
        <div class="login-hero-pan">
            <span class="pan-halo"></span>
            <img src="/panaderia/Assets/img/pan_orneado.png" alt="" class="pan-flotante">
            <span class="pan-sombra"></span>
        </div>

        <div class="login-visual-inner">
            <span class="login-kicker">Panadería artesanal</span>
            <h2 class="login-quote">El arte del pan,<br><em>bien administrado.</em></h2>
            <p class="login-lead">Recetas, producción, pedidos y caja del día, en un solo lugar.</p>
        </div>

        <ul class="login-features">
            <li><i class="ph-duotone ph-book-open-text"></i>Recetas</li>
            <li><i class="ph-duotone ph-fire"></i>Producción</li>
            <li><i class="ph-duotone ph-clipboard-text"></i>Pedidos</li>
            <li><i class="ph-duotone ph-cash-register"></i>Arqueo</li>
        </ul>
    </section>

    <section class="form-box login">
        <!-- FORMULARIO DE INICIO DE SESIÓN -->
        <form id="formLogin" action="/panaderia/Controllers/AuthController.php" method="POST">
            <div class="login-brand">
                <span class="login-brand-mark"><i class="ph-duotone ph-bread"></i></span>
                <span class="login-brand-name">Don Leo</span>
            </div>

            <h1>Bienvenido</h1>
            <p class="login-sub">Ingresa tus credenciales para continuar.</p>

            <label class="login-label" for="loginUsuario">Usuario</label>
            <div class="input-box">
                <input type="text" id="loginUsuario" name="usuario" placeholder="Tu nombre de usuario" autocomplete="username" required>
                <i class="ph-duotone ph-user"></i>
            </div>

            <label class="login-label" for="loginClave">Contraseña</label>
            <div class="input-box">
                <input type="password" id="loginClave" name="clave" placeholder="••••••••" autocomplete="current-password" required>
                <i class="ph-duotone ph-lock-key"></i>
            </div>

            <div style="text-align: right; margin-top: -10px; margin-bottom: 20px;">
                <a href="#" id="linkOlvido" style="font-size: 13px; color: var(--accent); text-decoration: none; font-weight: 600;">¿Olvidaste tu contraseña?</a>
            </div>

            <button type="submit" class="btn">Entrar al sistema</button>

            <p class="login-foot">&copy; 2026 Panadería de Don Leo &middot; Sistema de gestión</p>
        </form>

        <!-- FORMULARIO DE RECUPERACIÓN (Oculto por defecto) -->
        <form id="formRecuperar" action="/panaderia/Controllers/AuthController.php" method="POST" style="display: none;">
            <input type="hidden" name="accion" value="recuperar">
            <div class="login-brand">
                <span class="login-brand-mark"><i class="ph-duotone ph-bread"></i></span>
                <span class="login-brand-name">Don Leo</span>
            </div>

            <h1>Recuperar</h1>
            <p class="login-sub">Ingresa tu correo para restablecer tu clave.</p>

            <label class="login-label" for="recuperarCorreo">Correo electrónico</label>
            <div class="input-box">
                <input type="email" id="recuperarCorreo" name="correo" placeholder="correo@ejemplo.com" autocomplete="email" required>
                <i class="ph-duotone ph-envelope"></i>
            </div>

            <button type="submit" class="btn">Enviar instrucciones</button>

            <div style="text-align: center; margin-top: 16px;">
                <a href="#" id="linkVolver" style="font-size: 13px; color: var(--muted); text-decoration: none; font-weight: 600;">← Volver al inicio de sesión</a>
            </div>

            <p class="login-foot">&copy; 2026 Panadería de Don Leo &middot; Sistema de gestión</p>
        </form>
    </section>

</div>

<script src="/panaderia/Assets/js/login.js"></script>