<?php
/**
 * Configuración de Conexión a la Base de Datos - Panadería de Don Leo
 * Ubicación: /panaderia/config/db.php
 */

// Zona horaria local (UTC-6). Debe coincidir con la de MySQL/Windows para que
// "hoy" sea el mismo día en PHP (date) y en las consultas (CURDATE).
date_default_timezone_set('America/El_Salvador');

$host = 'localhost';
$dbname = 'panaderia_don_leo';
$username = 'root';
$password = ''; // Por defecto en WampServer suele estar vacío

try {
    // Crear instancia de PDO para MySQL
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // Configurar atributos y manejo de errores
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Si hay un error de conexión, detener la ejecución y mostrar mensaje claro
    http_response_code(500);
    echo "<div style='font-family:sans-serif; text-align:center; margin-top:50px;'>
            <h2 style='color: #d9534f;'>Error de Conexión a la Base de Datos</h2>
            <p>No se pudo conectar a la base de datos <strong>{$dbname}</strong>.</p>
            <p style='color: #666; font-size: 0.9em;'>Detalle: " . htmlspecialchars($e->getMessage()) . "</p>
          </div>";
    exit;
}