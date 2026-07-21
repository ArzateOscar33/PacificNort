<?php

use PHPMailer\PHPMailer\PHPMailer;

require_once dirname(__DIR__) . '/vendor/autoload.php';

class Contactos extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        header('Content-Type: application/json; charset=utf-8');

        /*
        |--------------------------------------------------------------------------
        | Validar método HTTP
        |--------------------------------------------------------------------------
        */

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);

            echo json_encode([
                'msg' => 'Método no permitido',
                'icono' => 'error'
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        /*
        |--------------------------------------------------------------------------
        | Obtener y limpiar los campos
        |--------------------------------------------------------------------------
        */

        $nombre = trim(strip_tags($_POST['name'] ?? ''));
        $correoIngresado = trim($_POST['email'] ?? '');
        $asunto = trim(strip_tags($_POST['subject'] ?? ''));
        $mensajeFormulario = trim(strip_tags($_POST['message'] ?? ''));

        /*
        |--------------------------------------------------------------------------
        | Validar campos obligatorios
        |--------------------------------------------------------------------------
        */

        if (
            $nombre === '' ||
            $correoIngresado === '' ||
            $asunto === '' ||
            $mensajeFormulario === ''
        ) {
            http_response_code(422);

            echo json_encode([
                'msg' => 'Todos los campos son requeridos',
                'icono' => 'warning',
                'enviado' => false
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        /*
        |--------------------------------------------------------------------------
        | Validar correo electrónico
        |--------------------------------------------------------------------------
        */

        $correoUsuario = filter_var(
            $correoIngresado,
            FILTER_VALIDATE_EMAIL
        );

        if ($correoUsuario === false) {
            http_response_code(422);

            echo json_encode([
                'msg' => 'El correo electrónico no es válido',
                'icono' => 'warning',
                'enviado' => false
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        /*
        |--------------------------------------------------------------------------
        | Preparar valores seguros para HTML
        |--------------------------------------------------------------------------
        */

        $nombreHtml = htmlspecialchars(
            $nombre,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );

        $correoHtml = htmlspecialchars(
            $correoUsuario,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );

        $asuntoHtml = htmlspecialchars(
            $asunto,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );

        $mensajeHtml = nl2br(
            htmlspecialchars(
                $mensajeFormulario,
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            )
        );

        try {
            /*
            |--------------------------------------------------------------------------
            | 1. Enviar el mensaje a la empresa
            |--------------------------------------------------------------------------
            */

            $mailEmpresa = $this->crearMailer();

            $mailEmpresa->setFrom(
                USER_SMTP,
                'Formulario de Contacto'
            );

            $mailEmpresa->addAddress(
                USER_SMTP,
                TITLE
            );

            $mailEmpresa->addReplyTo(
                $correoUsuario,
                $nombre
            );

            $mailEmpresa->isHTML(true);

            $mailEmpresa->Subject =
                'Nuevo mensaje de contacto: ' . $asunto;

            $mailEmpresa->Body = '
                <div style="
                    max-width:600px;
                    margin:0 auto;
                    font-family:\'Segoe UI\', Arial, sans-serif;
                    background:#ffffff;
                    border-radius:10px;
                    overflow:hidden;
                    box-shadow:0 2px 8px rgba(0,0,0,0.05);
                ">
                    <div style="
                        background-color:#1c1e74;
                        padding:25px;
                        text-align:center;
                        color:white;
                    ">
                        <img
                            src="https://cdn-icons-png.flaticon.com/128/3135/3135673.png"
                            alt="Contacto"
                            width="50"
                            height="50"
                            style="margin-bottom:10px;"
                        >

                        <h2 style="margin:0; font-size:20px;">
                            Nuevo mensaje desde el formulario de contacto
                        </h2>
                    </div>

                    <div style="padding:30px; color:#333;">
                        <p style="font-size:16px;">
                            <strong>Nombre:</strong>
                            ' . $nombreHtml . '
                        </p>

                        <p style="font-size:16px;">
                            <strong>Correo:</strong>

                            <a
                                href="mailto:' . $correoHtml . '"
                                style="color:#1c1e74;"
                            >
                                ' . $correoHtml . '
                            </a>
                        </p>

                        <p style="font-size:16px;">
                            <strong>Asunto:</strong>
                            ' . $asuntoHtml . '
                        </p>

                        <p style="font-size:16px;">
                            <strong>Mensaje:</strong>
                        </p>

                        <div style="
                            border:1px solid #ddd;
                            padding:15px;
                            border-radius:8px;
                            background-color:#f9f9f9;
                            font-size:15px;
                            line-height:1.5;
                        ">
                            ' . $mensajeHtml . '
                        </div>
                    </div>

                    <div style="
                        background-color:#f1f1f1;
                        text-align:center;
                        padding:15px;
                        font-size:12px;
                        color:#555;
                    ">
                        Este mensaje fue generado automáticamente desde el
                        sitio web de <strong>' . TITLE . '</strong>.
                    </div>
                </div>
            ';

            $mailEmpresa->AltBody =
                "Nuevo mensaje desde el formulario de contacto\n\n" .
                "Nombre: {$nombre}\n" .
                "Correo: {$correoUsuario}\n" .
                "Asunto: {$asunto}\n\n" .
                "Mensaje:\n{$mensajeFormulario}";

            $mailEmpresa->send();

            /*
            |--------------------------------------------------------------------------
            | 2. Enviar confirmación al cliente
            |--------------------------------------------------------------------------
            |
            | Si este segundo correo falla, el mensaje principal ya fue recibido
            | por la empresa. Por eso no se devuelve un error total.
            |
            */

            $confirmacionEnviada = true;

            try {
                $mailUsuario = $this->crearMailer();

                $mailUsuario->setFrom(
                    USER_SMTP,
                    TITLE
                );

                $mailUsuario->addAddress(
                    $correoUsuario,
                    $nombre
                );

                $mailUsuario->isHTML(true);

                $mailUsuario->Subject =
                    'Gracias por contactarnos - ' . TITLE;

                $mailUsuario->Body = '
                    <div style="
                        max-width:600px;
                        margin:0 auto;
                        font-family:\'Segoe UI\', Arial, sans-serif;
                        background:#f9f9f9;
                        border-radius:10px;
                        overflow:hidden;
                        box-shadow:0 2px 8px rgba(0,0,0,0.1);
                    ">
                        <div style="
                            background-color:#1c1e74;
                            padding:30px;
                            text-align:center;
                        ">
                            <img
                                src="https://cdn-icons-png.flaticon.com/128/561/561127.png"
                                alt="Correo"
                                width="64"
                                height="64"
                                style="margin-bottom:10px;"
                            >

                            <h1 style="
                                color:white;
                                margin:0;
                                font-size:24px;
                            ">
                                Gracias por tu mensaje
                            </h1>
                        </div>

                        <div style="
                            padding:30px;
                            background-color:#ffffff;
                            color:#333;
                        ">
                            <h2 style="margin-top:0;">
                                Hola, ' . $nombreHtml . '.
                            </h2>

                            <p style="
                                font-size:16px;
                                line-height:1.6;
                            ">
                                Hemos recibido tu mensaje correctamente.
                                <br>

                                Uno de nuestros asesores te responderá
                                lo antes posible.
                                <br><br>

                                Si deseas agregar más información,
                                puedes responder directamente a este correo.
                            </p>

                            <p style="font-size:16px;">
                                Asunto:
                                <strong>' . $asuntoHtml . '</strong>
                            </p>

                            <p style="margin:30px 0 0;">
                                Saludos cordiales,
                                <br>

                                <strong>' . TITLE . '</strong>
                            </p>
                        </div>

                        <div style="
                            background-color:#1c1e74;
                            color:#ffffff;
                            text-align:center;
                            padding:20px;
                            font-size:14px;
                        ">
                            <p style="margin:0 0 10px;">
                                Síguenos en nuestras redes sociales:
                            </p>

                            <a
                                href="https://www.facebook.com/pacificnort"
                                target="_blank"
                                style="margin:0 10px;"
                            >
                                <img
                                    src="https://cdn-icons-png.flaticon.com/24/733/733547.png"
                                    alt="Facebook"
                                    width="24"
                                    height="24"
                                >
                            </a>

                            <a
                                href="https://www.linkedin.com/company/pacificnort"
                                target="_blank"
                                style="margin:0 10px;"
                            >
                                <img
                                    src="https://cdn-icons-png.flaticon.com/24/1384/1384014.png"
                                    alt="LinkedIn"
                                    width="24"
                                    height="24"
                                >
                            </a>

                            <br><br>

                            © ' . date('Y') . ' ' . TITLE . '.
                            Todos los derechos reservados.
                        </div>
                    </div>
                ';

                $mailUsuario->AltBody =
                    "Hola, {$nombre}.\n\n" .
                    "Hemos recibido correctamente tu mensaje sobre: {$asunto}.\n" .
                    "Uno de nuestros asesores te responderá lo antes posible.\n\n" .
                    "Saludos cordiales,\n" .
                    TITLE;

                $mailUsuario->send();
            } catch (\Throwable $e) {
                $confirmacionEnviada = false;

                error_log(
                    'El mensaje de contacto fue recibido, pero falló ' .
                        'el correo de confirmación al cliente: ' .
                        $e->getMessage()
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Respuesta final
            |--------------------------------------------------------------------------
            */

            if ($confirmacionEnviada) {
                $respuesta = [
                    'msg' => 'Mensaje enviado correctamente. Revisa tu correo.',
                    'icono' => 'success',
                    'enviado' => true


                ];
            } else {
                $respuesta = [
                    'msg' => 'Recibimos tu mensaje, pero no pudimos enviar el correo de confirmación.',
                    'icono' => 'warning',
                    'enviado' => true
                ];
            }
        } catch (\Throwable $e) {
            /*
            |--------------------------------------------------------------------------
            | Falló el correo principal enviado a la empresa
            |--------------------------------------------------------------------------
            */

            error_log(
                'Error SMTP en formulario de contacto: ' .
                    $e->getMessage()
            );

            http_response_code(500);

            $respuesta = [
                'msg' => 'No fue posible enviar el correo. Inténtalo nuevamente.',
                'icono' => 'error',
                'enviado' => false
            ];
        }

        echo json_encode(
            $respuesta,
            JSON_UNESCAPED_UNICODE
        );

        exit;
    }

    /**
     * Crea una instancia de PHPMailer con la configuración SMTP.
     */
    private function crearMailer(): PHPMailer
    {
        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->Host = HOST_SMTP;
        $mail->SMTPAuth = true;
        $mail->Username = USER_SMTP;
        $mail->Password = PASS_SMTP;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = PUERTO_SMTP;
        $mail->CharSet = 'UTF-8';
        $mail->Timeout = 30;

        return $mail;
    }
}
