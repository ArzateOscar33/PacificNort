<?php
class Operaciones_maritimas_documentos extends Controller
{
    public function __construct()
    {
        parent::__construct(); 
    }

    // === BUSCAR OPERACIONES (solo las que tengan contenedores) ===
    public function buscarOperaciones()
    {
        $term = isset($_GET['term']) ? trim($_GET['term']) : '';
        if ($term === '') { echo json_encode([]); die(); }
        $rows = $this->model->buscarOperacionesConContenedores($term);
        echo json_encode($rows, JSON_UNESCAPED_UNICODE); die();
    }

    // === CONTENEDORES POR OPERACIÓN (sugerencias) ===
    public function contenedoresPorOperacion($operacion_id = 0)
    {
        $opId = (int)$operacion_id;
        if ($opId <= 0) { echo json_encode([]); die(); }
        $rows = $this->model->contenedoresDeOperacionMixto($opId); // NUEVO
        echo json_encode($rows, JSON_UNESCAPED_UNICODE); die();
    }

    public function listar()
    {
        $operacion_id  = (int)($_GET['operacion_id'] ?? 0);
        $contenedor_id = isset($_GET['contenedor_id']) ? (int)$_GET['contenedor_id'] : null;
        $tipo          = isset($_GET['tipo']) ? trim($_GET['tipo']) : null;  

        if ($operacion_id <= 0) {
            echo json_encode(['error' => 'operacion_id es requerido']); die();
        }

        $rows = $this->model->listarDocumentosMixto($operacion_id, $contenedor_id, $tipo); // NUEVO
        echo json_encode($rows, JSON_UNESCAPED_UNICODE); die();
    }

    
}
