<?php
class Eventos_logisticos extends Controller
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
        $data['title'] = 'Eventos logisticos';

        $this->views->getView('admin/Evento_logistico', "index", $data);
    }
    public function tipo_evento()
    {
        $data['title'] = 'Tipo_evento';
        $this->views->getView('admin/Evento_logistico', "tipo_evento_logistico", $data);
    }

 

}