<?php
class Ciudades extends Controller
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
        $data['title'] = 'Ciudades';

        $this->views->getView('admin/Ciudades', "index", $data);
    }
 

}