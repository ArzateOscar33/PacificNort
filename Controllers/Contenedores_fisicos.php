<?php
class Contenedores_fisicos extends Controller
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
        $data['title'] = 'Contenedores_fisicos';

        $this->views->getView('admin/Contenedores_fisicos', "index", $data);
    }
 

}