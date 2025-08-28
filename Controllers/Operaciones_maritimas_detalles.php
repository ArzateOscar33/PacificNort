<?php
class Operaciones_maritimas_detalles extends Controller
{
    public function __construct()
    {
        parent::__construct();
        if (session_status() === PHP_SESSION_NONE) { @session_start(); }
    }

    public function listar()
    {
        $data = $this->model->listar();
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }
 
/** GET ?term=LI */
    public function buscar_operaciones()
    {
        $term  = isset($_GET['term']) ? trim($_GET['term']) : '';
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;

        if (mb_strlen($term) < 1) {
            echo json_encode([], JSON_UNESCAPED_UNICODE); die();
        }

        $ops = $this->model->buscarOperacionesMaritimas($term, $limit);

        // Estructura estándar: [{id, label, meta}]
        $out = array_map(function($row){
            $meta = '';
            if (isset($row['fisicos'], $row['maritimos'])) {
                $meta = "{$row['fisicos']} físico(s) · {$row['maritimos']} marítimo(s)";
            }
            return [
                'id'    => (int)$row['id'],
                'label' => $row['label'],
                'meta'  => $meta
            ];
        }, $ops);

        echo json_encode($out, JSON_UNESCAPED_UNICODE);
        die();
    }

    /** GET ?operacion_id=48[&term=FXEU] */
    public function buscar_contenedores()
    {
        $operacionId = isset($_GET['operacion_id']) ? intval($_GET['operacion_id']) : 0;
        $term        = isset($_GET['term']) ? trim($_GET['term']) : '';
        $limit       = isset($_GET['limit']) ? intval($_GET['limit']) : 15;

        if ($operacionId <= 0) { echo json_encode([], JSON_UNESCAPED_UNICODE); die(); }

        $rows = $this->model->buscarContenedoresDeOperacion($operacionId, $term, $limit);

        // Estructura estándar: [{id, label, tipo}]
        $out = array_map(function($r){
            return [
                'id'    => (int)$r['id'],     // id_contenedor (FISICO) o id (tabla cmo) MARITIMO
                'label' => $r['label'],       // numero_ferro o numero_contenedor
                'tipo'  => $r['tipo']         // 'FISICO' | 'MARITIMO'
            ];
        }, $rows);

        echo json_encode($out, JSON_UNESCAPED_UNICODE);
        die();
    }
}
