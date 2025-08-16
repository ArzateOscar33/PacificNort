
<?php
class Operaciones_terrestres extends Controller
{
    public function __construct()
    {
        parent::__construct();
        session_start();
        // Verifica si el usuario tiene sesión
        if (empty($_SESSION['nombre_usuario'])) {
            header("Location: " . BASE_URL);
            exit;
        }
    }

    // Vista principal con tabs
    public function ver($id)
    {
        $data['id_operacion'] = 1;
        $data['title'] = 'Detalles Operacion';
        $this->views->getView('admin/operaciones_terrestres', "ver", $data);
    }
    // TAB : Crear Operación
    public function crear_operacion($id)
    {
        $data['title'] = 'Crear Operación';
        $this->views->getView('admin/operaciones_terrestres/tabs/operaciones_terrestres', "crear_operacion", $data);
    }
    // TAB: Detalles Generales (operaciones + detalles_logisticos)
    public function detalles($id)
    {
        //$data = $this->model->getDetallesOperacion($id);
        $data['title'] = 'Detalles Operacion';
        $this->views->getView('admin/operaciones_terrestres/tabs/detalles_generales', "detalles", $data);
         
    }

    // TAB: Contenedores
    public function contenedores($id)
    {
        //$data = $this->model->getContenedoresPorOperacion($id);
        $data['title'] = 'Contenedores';
        $this->views->getView('admin/operaciones_terrestres/tabs/contenedores', "contenedores", $data);
        
    }

    // TAB: Costos por Contenedor
    public function costos($id)
    {   
        //$data = $this->model->getCostosPorOperacion($id);
        $data['title'] = 'Costos por Contenedor';
        $this->views->getView('admin/operaciones_terrestres/tabs/costos', "costos", $data);
    }

    // TAB: Trazabilidad / Movimientos
    public function trazabilidad($id)
    {
        //$data = $this->model->getTrazabilidadOperacion($id);
        $data['title'] = 'Trazabilidad';
        $this->views->getView('admin/operaciones_terrestres/tabs/trazabilidad', "trazabilidad", $data);
    }

    // TAB: Documentos
    public function documentos($id)
    {
        //$data = $this->model->getDocumentosOperacion($id);
        $data['title'] = 'Documentos';
        $this->views->getView('admin/operaciones_terrestres/tabs/documentos', "documentos", $data);
 
    }
    public function costos_operacion($id)
    {
        $data['title'] = 'Costos por Operación';
        $this->views->getView('admin/operaciones_terrestres/tabs/costos_operacion', "costos", $data);
    }
 
    // TAB: Bitácora / Log
    public function log($id)
    {
        //$data = $this->model->getBitacoraOperacion($id);
        $data['title'] = 'Bitácora';
        $this->views->getView('admin/operaciones_terrestres/tabs/log', "log", $data);
    }
    // TAB: Detalles Logísticos
    public function detalles_logisticos($id)
    {
        //$data = $this->model->getBitacoraOperacion($id);
        $data['title'] = 'Detalles Logísticos';
        $this->views->getView('admin/operaciones_terrestres/tabs/detalles_logisticos', "detalles_logisticos", $data);
    }
}
?>
