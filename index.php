<?php
/**
 * Punto de entrada principal - Panadería de Don Leo
 * Ubicación: /panaderia/index.php
 * Propósito: Redirigir automáticamente al usuario al formulario de inicio de sesión.
 */

// Iniciar sesión global del sistema
session_start();

// Redirigir al login (lo muestra AuthController)
header("Location: Controllers/AuthController.php");
exit();