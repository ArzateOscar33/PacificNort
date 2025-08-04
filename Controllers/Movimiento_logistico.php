<?php
class Movimiento_logistico extends Controller
{
    public function __construct()
    {
        parent::__construct();
        session_start();
        if (empty($_SESSION['nombre_usuario'])) {
            header('Location: ' . BASE_URL . 'admin');
            exit;
        }
    }
    public function index()
    {
        $data['title'] = 'Movimiento_logistico';

        $this->views->getView('admin/movimiento_logistico', "index", $data);
    }
 
    public function tipo_movimiento()
    {
        $data['title'] = 'Tipo_movimiento';

        $this->views->getView('admin/movimiento_logistico', "tipo_movimiento", $data);
    }

}