<?php
/**
 * Vista: Login - Panadería Don Leo
 * La muestra Controllers/AuthController.php (GET)
 * login.js usa: .form-box.login form, input[type=text], input[type=password] y .btn
 */
if (realpath($_SERVER["SCRIPT_FILENAME"]) === __FILE__) {
    header("Location: /panaderia/Controllers/AuthController.php");
    exit;
}
?>
<?php include_once __DIR__ . '/../Layouts/header.php'; ?>

<link rel="stylesheet" href="/panaderia/Assets/css/login.css?v=5">

<script>
    document.body.className = 'login-body';
</script>

<div class="login-shell">

    <!-- Mitad visual: foto de la panadería -->
    <section class="login-visual" aria-hidden="true">
        <!-- Pan flotando en el centro -->
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

    <!-- Mitad del formulario -->
    <section class="form-box login">
        <form action="/panaderia/Controllers/AuthController.php" method="POST">
            <div class="login-brand">
                <span class="login-brand-mark"><i class="ph-duotone ph-bread"></i></span>
                <span class="login-brand-name">Don Leo</span>
            </div>

            <h1>Bienvenido</h1>
            <p class="login-sub">Ingresa tus credenciales para continuar.</p>

            <label class="login-label" for="loginUsuario">Usuario</label>
            <div class="input-box">
                <input type="text" id="loginUsuario" placeholder="Tu nombre de usuario" autocomplete="username" required>
                <i class="ph-duotone ph-user"></i>
            </div>

            <label class="login-label" for="loginClave">Contraseña</label>
            <div class="input-box">
                <input type="password" id="loginClave" placeholder="••••••••" autocomplete="current-password" required>
                <i class="ph-duotone ph-lock-key"></i>
            </div>

            <button type="submit" class="btn">Entrar al sistema</button>

            <p class="login-foot">&copy; 2026 Panadería de Don Leo &middot; Sistema de gestión</p>
        </form>
    </section>

</div>

<script src="/panaderia/Assets/js/login.js"></script>
