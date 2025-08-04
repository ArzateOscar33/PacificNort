<?php
class Movimientos_Contenedor extends Controller
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

        $this->views->getView('admin/movimientos_contenedor', "index", $data);
    }
 
 

}