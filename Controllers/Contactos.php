<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

class Contactos extends Controller
{
    public function __construct()
    {
        parent::__construct();
        session_start();
    }

    public function index()
    {
        if (
            isset($_POST['name'], $_POST['email'], $_POST['message'], $_POST['subject']) &&
            !empty($_POST['name']) && !empty($_POST['email']) &&
            !empty($_POST['message']) && !empty($_POST['subject'])
        ) {
            $nombre = strip_tags($_POST['name']);
            $correoUsuario = strip_tags($_POST['email']);
            $asunto = strip_tags($_POST['subject']);
            $mensaje = strip_tags($_POST['message']);

            try {
                // 1️⃣ Enviar mensaje al correo de la empresa
                $mailEmpresa = new PHPMailer(true);
                $mailEmpresa->isSMTP();
                $mailEmpresa->Host       = HOST_SMTP;
                $mailEmpresa->SMTPAuth   = true;
                $mailEmpresa->Username   = USER_SMTP;
                $mailEmpresa->Password   = PASS_SMTP;
                $mailEmpresa->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mailEmpresa->Port       = PUERTO_SMTP;

                $mailEmpresa->setFrom(USER_SMTP, 'Formulario de Contacto');
                $mailEmpresa->addAddress(USER_SMTP); // tu correo empresarial

                $mailEmpresa->isHTML(true);
                $mailEmpresa->Subject = 'Nuevo mensaje de contacto: ' . $asunto;
                $mailEmpresa->Body = '
                            <div
                                style="max-width:600px; margin:0 auto; font-family:\'Segoe UI\', sans-serif; background:#ffffff; border-radius:10px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.05);">
                                <div style="background-color:#1c1e74; padding:25px; text-align:center; color:white;">
                                    <img src="https://cdn-icons-png.flaticon.com/128/3135/3135673.png" alt="Contacto" width="50" height="50"
                                        style="margin-bottom:10px;" />
                                    <h2 style="margin:0; font-size:20px;">Nuevo mensaje desde el formulario de contacto</h2>
                                </div>
                                <div style="padding:30px; color:#333;">
                                    <p style="font-size:16px;"><strong>Nombre:</strong> ' . htmlspecialchars($nombre) . '</p>
                                    <p style="font-size:16px;"><strong>Correo:</strong> <a href="mailto:' . htmlspecialchars($correoUsuario) . '"
                                            style="color:#1c1e74;">' . htmlspecialchars($correoUsuario) . '</a></p>
                                    <p style="font-size:16px;"><strong>Asunto:</strong> ' . htmlspecialchars($asunto) . '</p>
                                    <p style="font-size:16px;"><strong>Mensaje:</strong></p>
                                    <div
                                        style="border:1px solid #ddd; padding:15px; border-radius:8px; background-color:#f9f9f9; font-size:15px; line-height:1.5;">
                                        ' . nl2br(htmlspecialchars($mensaje)) . '
                                    </div>
                                </div>
                                <div style="background-color:#f1f1f1; text-align:center; padding:15px; font-size:12px; color:#555;">
                                    Este mensaje fue generado automáticamente desde el sitio web de <strong>' . TITLE . '</strong>.
                                </div>
                            </div>
                            ';

                $mailEmpresa->AltBody = "Mensaje de: {$nombre}\nCorreo: {$correoUsuario}\n\n{$mensaje}";
                $mailEmpresa->send();

                // 2️⃣ Enviar correo de respuesta automática al usuario
                $mailUsuario = new PHPMailer(true);
                $mailUsuario->isSMTP();
                $mailUsuario->Host       = HOST_SMTP;
                $mailUsuario->SMTPAuth   = true;
                $mailUsuario->Username   = USER_SMTP;
                $mailUsuario->Password   = PASS_SMTP;
                $mailUsuario->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mailUsuario->Port       = PUERTO_SMTP;

                $mailUsuario->setFrom(USER_SMTP, TITLE);
                $mailUsuario->addAddress($correoUsuario, $nombre);

                $mailUsuario->isHTML(true);
                $mailUsuario->Subject = 'Gracias por contactarnos - ' . TITLE;
              $mailUsuario->Body = '
                        <div
                            style="max-width:600px; margin:0 auto; font-family:\'Segoe UI\', sans-serif; background:#f9f9f9; border-radius:10px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.1);">
                            <div style="background-color:#1c1e74; padding:30px; text-align:center;">
                                <img src="https://cdn-icons-png.flaticon.com/128/561/561127.png" alt="Ícono de correo" width="64" height="64"
                                    style="margin-bottom:10px;" />
                                <h1 style="color:white; margin:0; font-size:24px;">Gracias por tu mensaje</h1>
                            </div>
                            <div style="padding:30px; background-color:#ffffff; color:#333;">
                                <h2 style="margin-top:0;">Hola, ' . htmlspecialchars($nombre) . '.</h2>
                                <p style="font-size:16px; line-height:1.6;">
                                    Hemos recibido tu mensaje correctamente.<br>
                                    Uno de nuestros asesores te responderá lo antes posible.<br><br>
                                    Si deseas más información, puedes visitar nuestro sitio web o responder directamente a este correo.
                                </p>
                                <p style="font-size:16px;">Asunto: <strong>' . htmlspecialchars($asunto) . '</strong></p>
                                <p style="margin:30px 0 0;">Saludos cordiales,<br><strong>' . TITLE . '</strong></p>
                            </div>
                            <div style="background-color:#1c1e74; color:#ffffff; text-align:center; padding:20px; font-size:14px;">
                                <p style="margin: 0 0 10px;">Síguenos en nuestras redes sociales:</p>
                                <a href="https://www.facebook.com/pacificnort" target="_blank" style="margin:0 10px;">
                                    <img src="https://cdn-icons-png.flaticon.com/24/733/733547.png" alt="Facebook" width="24" height="24" />
                                </a>
                                <a href="https://www.linkedin.com/company/pacificnort" target="_blank" style="margin:0 10px;">
                                    <img src="https://cdn-icons-png.flaticon.com/24/1384/1384014.png" alt="LinkedIn" width="24" height="24" />
                                </a>
                                <br><br>
                                © ' . date("Y") . ' ' . TITLE . '. Todos los derechos reservados.
                            </div>
                        </div>
                        ';


                $mailUsuario->AltBody = "Gracias por contactarnos. Pronto nos comunicaremos contigo.";

                $mailUsuario->send();

                $mensaje = array('msg' => 'Mensaje enviado correctamente. Revisa tu correo.', 'icono' => 'success');
            } catch (Exception $e) {
                $mensaje = array('msg' => 'Error al enviar correo: ' . $e->getMessage(), 'icono' => 'error');
            }
        } else {
            $mensaje = array('msg' => 'Todos los campos son requeridos', 'icono' => 'warning');
        }

        echo json_encode($mensaje, JSON_UNESCAPED_UNICODE);
        die();
    }
}
