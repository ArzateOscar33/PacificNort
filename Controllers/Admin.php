<?php
class Admin extends Controller
{
    public function __construct()
    {
        parent::__construct();
        session_start();
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
            $apellido=$_POST['apellido'];
            $correo = $_POST['correo'];
            $clave = $_POST['clave'];
            $telefono=$_POST['telefono'];
            $puesto_id = 2; // <- debe estar en el formulario
            $departamento_id =2; // <- en la base de datos creamos un departamento para clientes
            $rol_id = 3; // <- le damos el rol de cliente si se registro desde la pagina 
            

            if (empty($nombre) || empty($correo) || empty($clave) || empty($puesto_id) || empty($departamento_id)|| empty($telefono)) {
                $respuesta = array('msg' => 'Todos los campos son requeridos', 'icono' => 'warning');
            } else {
                $result = $this->model->verificarCorreo($correo);
                if (empty($result)) {
                    $hash = password_hash($clave, PASSWORD_DEFAULT);
                    $data = $this->model->registrar($nombre,$apellido, $correo, $hash,$telefono, $puesto_id, $departamento_id, $rol_id);
                    if ($data > 0) {
                        $respuesta = array('msg' => 'Usuario registrado correctamente', 'icono' => 'success');
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
