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
    public function index($id)
    {
        $data['title'] = 'Finanzas';
        $data['id_operacion']   = (int)$id;
        $data['brokers']        = $this->model->getBrokers();
        $data['transportistas'] = $this->model->getTransportistas();
        $data['ciudades']       = $this->model->listarDestinos();
        $data['categoriasCostos']     = $this->model->listarCategoriasCostos();

        $data['clientes']       = $this->model->catalogoClientes();

        $this->views->getView('admin/Finanzas', "ver", $data);
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
