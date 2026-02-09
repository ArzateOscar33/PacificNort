<?php

class PortalClientes extends Controller
{
    public function __construct()
    {
        parent::__construct();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // (Opcional pero recomendado) sesión única + inactividad
        // $this->validarSesionUnica();
        // $this->validarSesionInactividad();

        // solo rol cliente (3)
        $this->requireRoles([3]);

        // debe tener cliente_id
        if (empty($_SESSION['cliente_id']) || (int)$_SESSION['cliente_id'] <= 0) {
            header('Location: ' . BASE_URL . 'admin/salir');
            exit;
        }
    }

    public function index()
    {
        // Si NO hay sesión, manda al login
        if (empty($_SESSION['nombre_usuario'])) {
            header('Location: ' . BASE_URL . 'admin');
            exit;
        }

        $data['title'] = 'Portal Cliente';
        $this->views->getView('PortalClientes', 'index', $data);
    }
}
