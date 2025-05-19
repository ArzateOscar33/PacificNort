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
                $respuesta = array('msg' => 'todo los campos son requeridos', 'icono' => 'warning');
            } else {
                $data = $this->model->getUsuario($_POST['email']);
                if (empty($data)) {
                    $respuesta = array('msg' => 'el correo no existe', 'icono' => 'warning');
                } else {
                    if (password_verify($_POST['clave'], $data['clave'])) {
                        // Generar token único
                        $token = bin2hex(random_bytes(32));
                        require_once 'Models/SesionModel.php';
                        $sesionModel = new SesionModel();
                        $sesionModel->guardarToken($data['id'], $token);
                        $_SESSION['email'] = $data['correo'];
                        $_SESSION['nombre_usuario'] = $data['first_name'];
                        $_SESSION['apellido_usuario'] = $data['last_name'];
                        $_SESSION['id_usuario'] = $data['id'];
                        $_SESSION['rol_usuario'] = $this->model->getRolUsuario($data['id']);
                        $_SESSION['session_token'] = $token; // Guardar en sesión también
                        $respuesta = array('msg' => 'datos correcto', 'icono' => 'success');
                    } else {
                        $respuesta = array('msg' => 'contraseña incorrecta', 'icono' => 'warning');
                    }
                }
            }
        } else {
            $respuesta = array('msg' => 'error desconocido', 'icono' => 'error');
        }
        echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
        die();
    }
    private function verificarRol($rolPermitido)
    {
        if ($_SESSION['rol_usuario'] != $rolPermitido && $_SESSION['rol_usuario'] != 1) {
            header('Location: ' . BASE_URL . 'admin');
            exit;
        }
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
 
        $data['title'] = 'Panel Administrativo';
 

        $this->views->getView('admin', "register", $data);
    }
 
 
    public function salir()
    {
        session_start();
        require_once 'Models/SesionModel.php';
        $sesionModel = new SesionModel();

        if (isset($_SESSION['id_usuario'])) {
            $sesionModel->limpiarToken($_SESSION['id_usuario']);
        }

        session_unset(); // Limpia datos
        $_SESSION['msg_error'] = 'Has cerrado sesión correctamente.';
        header('Location: ' . BASE_URL . 'admin');
        exit;
    }

     public function registrar()
    {
        if (isset($_POST['nombre'])) { 
            $nombre = $_POST['nombre'];
            $apellido = $_POST['apellido'];
            $correo = $_POST['correo'];
            $clave = $_POST['clave'];
            $phone = $_POST['phone'];
            $role_id = 4; 
    
            if (empty($nombre) || empty($apellido) || empty($role_id)) {
                $respuesta = array('msg' => 'Todos los campos son requeridos', 'icono' => 'warning');
            } else {
                if (empty($id)) {
                    $result = $this->model->verificarCorreo($correo);
                    if (empty($result)) {
                        $hash = password_hash($clave, PASSWORD_DEFAULT);
                        $data = $this->model->registrar( $nombre, $apellido, $correo, $hash, $phone, $role_id);
                        if ($data > 0) {
                            $respuesta = array('msg' => 'Usuario registrado', 'icono' => 'success');
                        } else {
                            $respuesta = array('msg' => 'Error al registrar', 'icono' => 'error');
                        }
                    } else {
                        $respuesta = array('msg' => 'Correo ya existe', 'icono' => 'warning');
                    }
                } //else {
                    // Puedes agregar aquí la lógica para modificar el rol también si lo deseas
                    //$data = $this->model->modificar( $nombre, $apellido, $correo, $phone, $role_id, $id);
                    //if ($data == 1) {
                     //   $respuesta = array('msg' => 'Usuario modificado', 'icono' => 'success');
                    //} else {
                       // $respuesta = array('msg' => 'Error al modificar', 'icono' => 'error');
                   // }
                //}
            }
            echo json_encode($respuesta);
        }
        die();
    }

 
 
}
