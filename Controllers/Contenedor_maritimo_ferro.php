<?php
class Contenedor_maritimo_ferro extends Controller
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
        $data['title'] = 'Contenedores Marítimo - Ferro';

        $this->views->getView('admin/contenedor_maritimo_ferro', "index", $data);
    }
 

}