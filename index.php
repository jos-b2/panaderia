<?php
/**
 * Punto de entrada principal - Panadería de Don Leo
 * Ubicación: /panaderia/index.php
 * Propósito: Redirigir automáticamente al usuario al formulario de inicio de sesión.
 */

// Iniciar sesión global del sistema
session_start();

// Redirigir directamente a la vista de login ubicada en la carpeta Pública
header("Location: src/Views/Public/login.php");
exit();