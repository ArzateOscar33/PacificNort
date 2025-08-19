<?php
class Operaciones_maritimas extends Controller
{
    public function __construct()
    {
        parent::__construct();
        session_start();
        if (empty($_SESSION['nombre_usuario'])) {
            header("Location: " . BASE_URL);
            exit;
        }
    }

    // =================== VISTAS ===================
    public function ver()
    {
        $data['title']      = 'Operaciones Marítimas';
        $data['subtipos']   = $this->model->subtiposMaritimos();
        $data['estatus']    = $this->model->catalogoEstatus();
        $data['puertos']    = $this->model->catalogoPuertos();     // se usa para el select (solo UI)
        $data['navieras']   = $this->model->catalogoNavieras();
        $data['forwarders'] = $this->model->catalogoForwarders();
        $this->views->getView('admin/operaciones_maritimas/', "ver", $data);
    }

    // ===== Tabs opcionales =====
    public function crear_operacion($id){ $this->views->getView('admin/operaciones_maritimas/tabs/operaciones', "crear_operacion", ['title'=>'Crear Operación']); }
    public function detalles($id){        $this->views->getView('admin/operaciones_maritimas/tabs/detalles_generales', "detalles", ['title'=>'Detalles Operación']); }
    public function contenedores($id){    $this->views->getView('admin/operaciones_maritimas/tabs/contenedores', "contenedores", ['title'=>'Contenedores']); }
    public function costos($id){          $this->views->getView('admin/operaciones_maritimas/tabs/costos', "costos", ['title'=>'Costos por Contenedor']); }
    public function costos_operacion($id){$this->views->getView('admin/operaciones_maritimas/tabs/costos_operacion', "costos_operacion", ['title'=>'Costos por Operación']); }
    public function trazabilidad($id){    $this->views->getView('admin/operaciones_maritimas/tabs/trazabilidad', "trazabilidad", ['title'=>'Trazabilidad']); }
    public function documentos($id){      $this->views->getView('admin/operaciones_maritimas/tabs/documentos', "documentos", ['title'=>'Documentos']); }
    public function log($id){             $this->views->getView('admin/operaciones_maritimas/tabs/log', "log", ['title'=>'Bitácora']); }
    public function detalles_logisticos($id){
        $this->views->getView('admin/operaciones_maritimas/tabs/detalles_logisticos', "detalles_logisticos", ['title'=>'Detalles Logísticos']);
    }

    // =================== API ===================

    // LISTAR (tabla principal)
    public function listar()
    {
        $filters = [
            'subtipo_id' => (isset($_GET['subtipo_id']) && $_GET['subtipo_id'] !== '')
                            ? (int)$_GET['subtipo_id']
                            : ((isset($_GET['filtroSubtipo']) && $_GET['filtroSubtipo'] !== '')
                               ? (int)$_GET['filtroSubtipo'] : 0),
            'term'       => trim($_GET['term'] ?? '')
        ];
        $data = $this->model->listar($filters);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }

    // Autocomplete clientes
    public function buscar_clientes()
    {
        $term = isset($_GET['term']) ? trim($_GET['term']) : '';
        echo json_encode($term === '' ? [] : $this->model->buscarClientes($term), JSON_UNESCAPED_UNICODE);
        die();
    }

    // Autocomplete contenedores
    public function buscar_contenedores()
    {
        $term = isset($_GET['term']) ? trim($_GET['term']) : '';
        echo json_encode($term === '' ? [] : $this->model->buscarContenedores($term), JSON_UNESCAPED_UNICODE);
        die();
    }

    // REGISTRAR (sin movimientos_logisticos)
    public function registrar()
    {
        $op = [
            'numero_operacion'     => trim($_POST['numero_operacion'] ?? ''),
            'tipo_operacion_id'    => 1, // Marítimo
            'subtipo_operacion_id' => (int)($_POST['subtipo_operacion_id'] ?? 0),
            'etd'                  => $_POST['etd'] ?? null,
            'eta'                  => $_POST['eta'] ?? null,
            'numero_bl'            => trim($_POST['numero_bl'] ?? ''),
            'cliente_id'           => (int)($_POST['cliente_id'] ?? 0),
            'estatus_id'           => (int)($_POST['estatus_id'] ?? 9),
            'naviera_id'           => (int)($_POST['naviera_id'] ?? 0),
            'forwarder_id'         => (int)($_POST['forwarder_id'] ?? 0),
        ];

        $contenedores = [];
        if (!empty($_POST['contenedores'])) {
            $tmp = json_decode($_POST['contenedores'], true);
            if (is_array($tmp)) $contenedores = $tmp;
        }

        $usuarioId = (int)($_SESSION['id_usuario'] ?? 0);

        // 👇 Modelo ya NO recibe puerto_arribo_id
        $res = $this->model->insertarOperacion($op, $contenedores, $usuarioId);

        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        die();
    }

    // EDITAR: datos para llenar modal
    public function editar($id)
    {
        $id = (int)$id;
        if ($id <= 0) { echo json_encode(['status'=>'error','msg'=>'ID inválido'], JSON_UNESCAPED_UNICODE); die(); }

        $op = $this->model->getOperacionById($id);
        if (!$op) { echo json_encode(['status'=>'error','msg'=>'No encontrado'], JSON_UNESCAPED_UNICODE); die(); }

        $contenedores = $this->model->getContenedoresByOperacion($id);
        echo json_encode(['status'=>'success','op'=>$op,'contenedores'=>$contenedores], JSON_UNESCAPED_UNICODE);
        die();
    }

    // ELIMINAR: baja lógica
    public function eliminar($id)
    {
        $id = (int)$id;
        if ($id <= 0) { echo json_encode(['status'=>'error','msg'=>'ID inválido'], JSON_UNESCAPED_UNICODE); die(); }

        $usuarioId = (int)($_SESSION['id_usuario'] ?? 0);
        $res = $this->model->desactivarOperacion($id, $usuarioId);

        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        die();
    }
}
