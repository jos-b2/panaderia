<?php include_once __DIR__ . '/../Layouts/header.php'; ?>

<!-- Enlazamos tu archivo CSS con su nombre exacto -->
<link rel="stylesheet" href="/panaderia/Assets/css/login.css">
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

<script>
    // Asigna tu clase para el fondo con la imagen de la panadería
    document.body.className = 'login-body';
</script>

<div class="container">
    <!-- Formulario de Login -->
    <div class="form-box login">
        <form action="../Admin/dashboard.php" method="POST">
            <h1>Iniciar Sesión</h1>
            
            <div class="input-box">
                <input type="text" placeholder="Usuario" required>
                <i class='bx bxs-user'></i>
            </div>
            
            <div class="input-box">
                <input type="password" placeholder="Contraseña" required>
                <i class='bx bxs-lock-alt'></i>
            </div>

            <!-- Botón de acceso -->
            <button type="submit" class="btn">Entrar</button>
        </form>
    </div>

    <!-- Panel Lateral Fijo de Presentación con imagen PNG grande y sin recortes -->
    <div class="toggle-box">
        <div class="toggle-panel">
            <img src="/panaderia/Assets/img/pan_orneado.png" alt="Pan Horneado" class="pan-img-preview mb-3">
            <h1>Panadería Don Leo</h1>
            <p>Sistema de gestión interna, recetas y control de producción artesanal.</p>
        </div>
    </div>
</div>

<script src="/panaderia/Assets/js/login.js"></script>