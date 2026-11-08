<?php
/**
 * Vista: Acceso restringido (403) - Panadería Don Leo
 * Ubicación: /panaderia/src/Views/Public/sin_permiso.php
 * La muestra soloAdministrador() (Config/current_user.php) cuando un usuario
 * sin rol de administrador intenta abrir una sección de administración.
 */
if (realpath($_SERVER["SCRIPT_FILENAME"]) === __FILE__) {
    header("Location: /panaderia/Controllers/DashboardController.php");
    exit;
}

include_once __DIR__ . '/../Layouts/header.php';
?>

<style>
    body.sin-permiso {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 24px 16px;
        background:
            linear-gradient(180deg, rgba(20, 12, 7, 0.82), rgba(20, 12, 7, 0.92)),
            url('/panaderia/Assets/img/bg-panaderia.jpg') center / cover no-repeat;
    }
    .sp-card {
        width: 100%;
        max-width: 440px;
        padding: 40px 34px 32px;
        text-align: center;
        background: var(--surface);
        border-top: 3px solid var(--accent);
        box-shadow: 0 30px 70px rgba(0, 0, 0, 0.5);
        animation: entrarEscala .5s cubic-bezier(.2, .7, .2, 1) both;
    }
    .sp-icon {
        position: relative;
        width: 76px;
        height: 76px;
        margin: 0 auto 22px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #F7E6E2;
        color: var(--danger);
        font-size: 38px;
    }
    .sp-icon::after { content: ""; position: absolute; inset: -7px; border: 1px solid rgba(163, 58, 44, 0.3); }
    .sp-kicker { font-size: 10.5px; font-weight: 700; letter-spacing: 0.24em; text-transform: uppercase; color: var(--danger); }
    .sp-card h1 { font-family: var(--font-head); font-size: 30px; font-weight: 700; color: var(--ink); margin: 8px 0 12px; }
    .sp-card p { color: var(--muted); font-size: 14.5px; line-height: 1.6; margin: 0 0 26px; }
</style>

<script>document.body.className = 'sin-permiso';</script>

<main class="sp-card" role="alert">
    <div class="sp-icon"><i class="ph-duotone ph-lock-key"></i></div>
    <span class="sp-kicker">Error 403 · Acceso restringido</span>
    <h1>Sección solo para administradores</h1>
    <p>
        Hola, <strong><?= htmlspecialchars($nombreUsuario ?? 'usuario') ?></strong>. Tu cuenta no tiene permiso para entrar aquí.
        Si necesitas acceso, pídeselo a un administrador de la panadería.
    </p>
    <a href="/panaderia/Controllers/DashboardController.php" class="btn btn-brand w-100">
        <i class="ph-duotone ph-house"></i>Volver al menú
    </a>
</main>

</body>
</html>
