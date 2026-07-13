<?php
class Controller
{
    //esta linea no lo veras en el video es necesario para solucionar las advertencias
    protected $views, $model;
    //
    public function __construct()
    {
        $this->views = new Views();
        $this->cargarModel();
    }

    public function cargarModel(): void
    {
        $modeloSolicitado = get_class($this) . 'Model';
        $directorioModelos = __DIR__ . '/../../Models';

        foreach (glob($directorioModelos . '/*.php') ?: [] as $archivo) {
            $nombreModelo = pathinfo($archivo, PATHINFO_FILENAME);

            if (strcasecmp($nombreModelo, $modeloSolicitado) === 0) {
                require_once $archivo;

                if (!class_exists($nombreModelo, false)) {
                    throw new RuntimeException(
                        "La clase {$nombreModelo} no existe en {$archivo}"
                    );
                }

                $this->model = new $nombreModelo();
                return;
            }
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

        require_once __DIR__ . '/../../Models/SesionModel.php';
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

    /**
     * Requiere que el usuario tenga alguno de los roles indicados.
     * Ej: $this->requireRoles([1, 11]); // Admin y Supervisor
     */
    protected function requireRoles(array $rolesPermitidos)
    {
        // Por si acaso aún no se ha iniciado la sesión en el controlador hijo
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['rol_usuario']) || !in_array($_SESSION['rol_usuario'], $rolesPermitidos)) {
            header('Location: ' . BASE_URL . 'admin');
            exit;
        }
    }

    /**
     * Azúcar sintáctico para Admin (y si quieres agregar más).
     */
    protected function requireAdmin()
    {
        // Admin (1) y Supervisor (11), puedes ajustar esta lista
        $this->requireRoles([1]);
    }
}
