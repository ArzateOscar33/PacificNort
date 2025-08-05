<?php
class Contenedores_en_Operacion extends Controller
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
        $data['title'] = 'Contenedores en Operación';

        $this->views->getView('admin/contenedores_en_operacion', "index", $data);
    }
 

}