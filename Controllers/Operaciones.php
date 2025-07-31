<?php
class Operaciones extends Controller
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
        $data['title'] = 'Operaciones';

        $this->views->getView('admin/Operaciones', "index", $data);
    }
 
    public function terrestre()
    {
        $data['title'] = 'Operaciones';
        $this->views->getView('admin/operaciones', "terrestre", $data);
    }
    public function maritimo()
    {
        $data['title'] = 'Operaciones';
        $this->views->getView('admin/operaciones', "maritimo", $data);
    }
}