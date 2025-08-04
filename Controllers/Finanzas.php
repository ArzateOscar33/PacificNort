<?php
class Finanzas extends Controller
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
        $data['title'] = 'Finanzas';

        $this->views->getView('admin/Finanzas', "index", $data);
    }
 
    public function costos_logisticos()
    {
        $data['title'] = 'Costos de Operaciones';
        $this->views->getView('admin/finanzas', "costos_logisticos", $data);
    }

    public function costos_contenedor_operacion()
    {
        $data['title'] = 'Costos por Contenedor';
        $this->views->getView('admin/finanzas', "costos_contenedor_operacion", $data);
    }

    public function costos_operacion()
    {
        $data['title'] = 'Costos Operacion';
        $this->views->getView('admin/finanzas', "costos_operacion", $data);
    }
}