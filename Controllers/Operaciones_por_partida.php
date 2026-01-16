<?php
class Operaciones_por_partida extends Controller
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
        $data['title'] = 'Operaciones por Partida'; 

        $this->views->getView('admin/Operaciones_por_partida', "ver", $data);
    }
 
 

    }
