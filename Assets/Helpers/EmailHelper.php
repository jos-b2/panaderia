<?php
/**
 * Ubicación: /panaderia/Helpers/EmailHelper.php
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Cargar el autoloader de Composer (ajusta las rutas según Dónde esté ubicado tu Helpers respecto a la raíz)
require_once $_SERVER['DOCUMENT_ROOT'] . '/panaderia/vendor/autoload.php';

class EmailHelper {
    public static function enviarRecuperacionPassword($correoDestino, $nombreUsuario, $nuevaContrasena) {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'emergamer503gh@gmail.com';
            $mail->Password   = 'qxztyoonkefvicgw';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom('emergamer503gh@gmail.com', 'Panadería Don Leo');
            $mail->addAddress($correoDestino, $nombreUsuario);

            $mail->isHTML(true);
            $mail->Subject = 'Recuperación de contraseña - Panadería Don Leo';
            $mail->Body    = "
                <div style='font-family: Arial, sans-serif; background-color: #FBF6EE; padding: 20px; border-radius: 10px; color: #4A2F20;'>
                    <h2 style='color: #6B4226;'>Panadería Don Leo</h2>
                    <p>Hola <strong>" . htmlspecialchars($nombreUsuario) . "</strong>,</p>
                    <p>Has solicitado restablecer tu contraseña. Hemos generado una clave temporal para que puedas ingresar al sistema:</p>
                    <div style='background: #FFFDF9; border: 1px solid #EADFCE; padding: 15px; text-align: center; font-size: 20px; font-weight: bold; letter-spacing: 2px; color: #C98A3B; margin: 15px 0;'>
                        " . htmlspecialchars($nuevaContrasena) . "
                    </div>
                    <p>Te recomendamos iniciar sesión y cambiar tu contraseña lo antes posible.</p>
                    <hr style='border: none; border-top: 1px solid #EADFCE; margin: 20px 0;'>
                    <small style='color: #8A7565;'>Este es un mensaje automático, no respondas a este correo.</small>
                </div>
            ";

            return $mail->send();
        } catch (Exception $e) {
            error_log("Error al enviar correo: " . $mail->ErrorInfo);
            return false;
        }
    }
}