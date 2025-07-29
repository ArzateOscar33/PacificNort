<?php
class Controller{
    //esta linea no lo veras en el video es necesario para solucionar las advertencias
      protected $views, $model;
    //
    public function __construct()
    {
        $this->views = new Views();
        $this->cargarModel();
    }
    public function cargarModel()
    {
        $model = get_class($this)."Model";
        $ruta = "Models/".$model.".php";
        if (file_exists($ruta)) {
            require_once $ruta;
            $this->model = new $model();
        }
    }
    protected function validarSesionUnica()
    {
        if (empty($_SESSION['id_usuario']) || empty($_SESSION['session_token'])) {
            session_start();
            session_unset();
            $_SESSION['msg_error'] = 'Sesión inválida o no iniciada.';
            header('Location: ' . BASE_URL . 'admin');
            exit;
        }
    
        require_once 'Models/SesionModel.php';
        $sesionModel = new SesionModel();
    
        $userId = $_SESSION['id_usuario'];
        $sessionToken = $_SESSION['session_token'];
        $result = $sesionModel->verificarToken($userId);
    
        if (!$result || $result['session_token'] !== $sessionToken) {
            session_start();             // asegúrate que hay sesión
            session_unset();             // limpia pero mantiene activa
            $_SESSION['msg_error'] = 'Tu sesión fue cerrada porque se inició en otro dispositivo.';
            header('Location: ' . BASE_URL . 'admin');
            exit;
        }
    }
    
    protected function validarSesionInactividad($timeout = 900) // 900s = 15 min
{
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
        session_start();
        session_unset();
        $_SESSION['msg_error'] = 'Tu sesión ha expirado por inactividad.';
        header('Location: ' . BASE_URL . 'admin');
        exit;
    }

    $_SESSION['last_activity'] = time(); // Reinicia el temporizador de inactividad
}
    
}
 
?>