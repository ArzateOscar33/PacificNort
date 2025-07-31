<?php
class Contenedores_maritimos extends Controller
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
        $data['title'] = 'Contenedores_maritimos';

        $this->views->getView('admin/Contenedores_maritimos', "index", $data);
    }
 

}