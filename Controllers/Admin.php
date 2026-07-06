<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

class Admin extends Controller
{
    public function __construct()
    {
        parent::__construct();
        session_start();
    }
    private function mailer(): PHPMailer
    {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = HOST_SMTP;
        $mail->SMTPAuth   = true;
        $mail->Username   = USER_SMTP;
        $mail->Password   = PASS_SMTP;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = PUERTO_SMTP;

        $mail->CharSet = 'UTF-8';
        $mail->isHTML(true);

        return $mail;
    }
    private function enviarCorreoRegistroUsuario(string $correoUsuario, string $nombre): void
    {
        $mail = $this->mailer();
        $mail->setFrom(USER_SMTP, 'PacificNort Suite');
        $mail->addAddress($correoUsuario, $nombre);

        $mail->Subject = 'Solicitud recibida - PacificNort Suite';

        $mail->Body = '
      <div style="max-width:600px;margin:0 auto;font-family:\'Segoe UI\',sans-serif;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,0.06);">
        <div style="background:#0f172a;padding:22px 24px;color:#e2e8f0;">
          <h2 style="margin:0;font-size:18px;">Solicitud recibida</h2>
          <div style="margin-top:6px;font-size:13px;color:rgba(226,232,240,.8);">PacificNort Suite | Portal Cliente</div>
        </div>
        <div style="padding:24px;color:#0f172a;">
          <p style="margin-top:0;font-size:15px;">Hola <b>' . htmlspecialchars($nombre) . '</b>,</p>

          <p style="font-size:15px;line-height:1.6;">
            Recibimos tu solicitud de acceso al Portal Cliente.
            En breve, nuestro equipo se comunicará contigo para <b>vincular tu usuario</b> con un cliente registrado y activar tu acceso.
          </p>

          <div style="border:1px solid rgba(15,23,42,.10);background:#f8fafc;border-radius:10px;padding:14px 16px;margin:18px 0;">
            <div style="font-weight:700;margin-bottom:6px;">Estado actual</div>
            <div style="font-size:14px;color:#334155;">Pendiente de vinculación por administrador.</div>
          </div>

          <p style="font-size:14px;color:#475569;line-height:1.6;margin-bottom:0;">
            Si no solicitaste esta cuenta, ignora este correo.
          </p>

          <p style="margin-top:18px;font-size:15px;">
            Saludos,<br><b>Equipo PacificNort</b>
          </p>
        </div>
        <div style="background:#f1f5f9;padding:14px 18px;text-align:center;font-size:12px;color:#64748b;">
          © ' . date("Y") . ' PacificNort. Mensaje automático, no respondas a este correo.
        </div>
      </div>';

        $mail->AltBody = "Hola {$nombre}. Recibimos tu solicitud de acceso. Tu cuenta está pendiente de vinculación por un administrador. En breve nos comunicaremos contigo.";

        $mail->send();
    }
    private function enviarCorreoRegistroAdmin(string $correoUsuario, string $nombre, string $telefono = ''): void
    {
        $mail = $this->mailer();
        $mail->setFrom(USER_SMTP, 'PacificNort Suite');
        $mail->addAddress(USER_SMTP, 'Soporte PacificNort');

        $mail->Subject = 'Nuevo registro de Portal Cliente - acción requerida';

        $tel = trim($telefono) !== '' ? htmlspecialchars($telefono) : 'No proporcionado';

        $mail->Body = '
      <div style="max-width:640px;margin:0 auto;font-family:\'Segoe UI\',sans-serif;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,0.06);">
        <div style="background:#0f172a;padding:22px 24px;color:#e2e8f0;">
          <h2 style="margin:0;font-size:18px;">Nuevo usuario cliente registrado</h2>
          <div style="margin-top:6px;font-size:13px;color:rgba(226,232,240,.8);">Acción requerida: contactar y vincular</div>
        </div>
        <div style="padding:24px;color:#0f172a;">
          <p style="margin-top:0;font-size:15px;">
            Se registró un nuevo usuario con rol <b>Cliente</b> y quedó en estado <b>Pendiente de vinculación</b>.
          </p>

          <table style="width:100%;border-collapse:collapse;font-size:14px;">
            <tr>
              <td style="padding:10px;border:1px solid rgba(15,23,42,.10);background:#f8fafc;width:160px;"><b>Nombre</b></td>
              <td style="padding:10px;border:1px solid rgba(15,23,42,.10);">' . htmlspecialchars($nombre) . '</td>
            </tr>
            <tr>
              <td style="padding:10px;border:1px solid rgba(15,23,42,.10);background:#f8fafc;"><b>Correo</b></td>
              <td style="padding:10px;border:1px solid rgba(15,23,42,.10);">
                <a href="mailto:' . htmlspecialchars($correoUsuario) . '">' . htmlspecialchars($correoUsuario) . '</a>
              </td>
            </tr>
            <tr>
              <td style="padding:10px;border:1px solid rgba(15,23,42,.10);background:#f8fafc;"><b>Teléfono</b></td>
              <td style="padding:10px;border:1px solid rgba(15,23,42,.10);">' . $tel . '</td>
            </tr>
          </table>

          <p style="margin-top:16px;font-size:14px;color:#475569;">
            Siguiente paso: contactar al usuario y vincularlo a un cliente desde el panel admin.
          </p>
        </div>
        <div style="background:#f1f5f9;padding:14px 18px;text-align:center;font-size:12px;color:#64748b;">
          © ' . date("Y") . ' PacificNort.
        </div>
      </div>';

        $mail->AltBody = "Nuevo usuario cliente registrado.\nNombre: {$nombre}\nCorreo: {$correoUsuario}\nTeléfono: {$telefono}\nAcción: contactar y vincular a un cliente.";

        $mail->send();
    }

    public function index()
    {
        if (!empty($_SESSION['nombre_usuario'])) {
            header('Location: ' . BASE_URL . 'admin/home');
            exit;
        }
        $data['title'] = 'Acceso al sistema';
        $this->views->getView('admin', "login", $data);
    }

    public function validar()
    {
        if (isset($_POST['email']) && isset($_POST['clave'])) {
            if (empty($_POST['email']) || empty($_POST['clave'])) {
                $respuesta = array('msg' => 'Todos los campos son requeridos', 'icono' => 'warning');
            } else {
                $data = $this->model->getUsuario($_POST['email']);
                if (empty($data)) {
                    $respuesta = array('msg' => 'El correo no existe', 'icono' => 'warning');
                } else {
                    if (password_verify($_POST['clave'], $data['clave'])) {
                        $token = bin2hex(random_bytes(32));
                        require_once 'Models/SesionModel.php';
                        $sesionModel = new SesionModel();
                        $sesionModel->guardarToken($data['id_usuario'], $token);

                        $_SESSION['email'] = $data['correo'];
                        $_SESSION['nombre_usuario'] = $data['nombre'];
                        $_SESSION['id_usuario'] = $data['id_usuario'];
                        $_SESSION['rol_usuario'] = $this->model->getRolUsuario($data['id_usuario']);
                        $_SESSION['session_token'] = $token;
                        // ✅ NUEVO: cliente_id en sesión (si aplica)
                        $_SESSION['cliente_id'] = isset($data['cliente_id']) ? (int)$data['cliente_id'] : 0;
                        // ✅ NUEVO: URL destino por rol
                        $redirect = BASE_URL . 'admin/home';
                        if ((int)$_SESSION['rol_usuario'] === 3) {
                            $redirect = BASE_URL . 'PortalClientes';
                            if ((int)$_SESSION['cliente_id'] <= 0) {
                                $redirect = BASE_URL . 'PortalClientes/pendiente';
                            }
                        }

                        $respuesta = array('msg' => 'Acceso correcto', 'icono' => 'success');
                    } else {
                        $respuesta = array('msg' => 'Contraseña incorrecta', 'icono' => 'warning');
                    }
                }
            }
        } else {
            $respuesta = array('msg' => 'Error desconocido', 'icono' => 'error');
        }

        echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function home()
    {
        if (empty($_SESSION['nombre_usuario'])) {
            header('Location: ' . BASE_URL . 'admin');
            exit;
        }

        // ✅ Bloqueo clientes
        if ((int)$_SESSION['rol_usuario'] === 3) {
            header('Location: ' . BASE_URL . 'PortalClientes');
            exit;
        }

        $data['title'] = 'Panel Administrativo';
        $this->views->getView('admin/administracion', "index", $data);
    }


    public function registro()
    {
        $data['title'] = 'Registro de Usuario';
        $this->views->getView('admin', "register", $data);
    }

    public function registrar()
    {
        if (isset($_POST['nombre']) && isset($_POST['correo']) && isset($_POST['clave'])) {
            $nombre = $_POST['nombre'];
            $apellido = $_POST['apellido'];
            $correo = $_POST['correo'];
            $clave = $_POST['clave'];
            $telefono = $_POST['telefono'];
            $puesto_id = 2; // <- debe estar en el formulario
            $departamento_id = 2; // <- en la base de datos creamos un departamento para clientes
            $rol_id = 3; // <- le damos el rol de cliente si se registro desde la pagina 


            if (empty($nombre) || empty($correo) || empty($clave) || empty($puesto_id) || empty($departamento_id) || empty($telefono)) {
                $respuesta = array('msg' => 'Todos los campos son requeridos', 'icono' => 'warning');
            } else {
                $result = $this->model->verificarCorreo($correo);
                if (empty($result)) {
                    $hash = password_hash($clave, PASSWORD_DEFAULT);
                    $data = $this->model->registrar($nombre, $apellido, $correo, $hash, $telefono, $puesto_id, $departamento_id, $rol_id);
                    if ($data > 0) {
                        // ✅ Registrar ok
                        $respuesta = array('msg' => 'Usuario registrado correctamente. Revisa tu correo.', 'icono' => 'success');

                        // ✅ Enviar correos (no bloquear si fallan)
                        try {
                            $this->enviarCorreoRegistroUsuario($correo, $nombre);
                        } catch (Throwable $e) {
                            error_log("Registro mail usuario ERROR: " . $e->getMessage());
                            // No rompas el registro
                        }

                        try {
                            $this->enviarCorreoRegistroAdmin($correo, $nombre, $telefono);
                        } catch (Throwable $e) {
                            error_log("Registro mail admin ERROR: " . $e->getMessage());
                        }
                    } else {
                        $respuesta = array('msg' => 'Error al registrar usuario', 'icono' => 'error');
                    }
                } else {
                    $respuesta = array('msg' => 'El correo ya está registrado', 'icono' => 'warning');
                }
            }

            echo json_encode($respuesta);
        }
        die();
    }

    public function salir()
    {
        session_start();
        require_once 'Models/SesionModel.php';
        $sesionModel = new SesionModel();

        if (isset($_SESSION['id_usuario'])) {
            $sesionModel->limpiarToken($_SESSION['id_usuario']);
        }

        session_unset();
        $_SESSION['msg_error'] = 'Has cerrado sesión correctamente.';
        header('Location: ' . BASE_URL . 'admin');
        exit;
    }

    private function verificarRol($rolPermitido)
    {
        if ($_SESSION['rol_usuario'] != $rolPermitido && $_SESSION['rol_usuario'] != 1) {
            header('Location: ' . BASE_URL . 'admin');
            exit;
        }
    }
}
