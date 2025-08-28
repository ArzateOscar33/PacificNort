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

     public function tipos_evento_maritimo()
    {
        $rows = $this->model->listarTiposEventoMaritimo();
        $out  = array_map(function($r){
            return [
                'id'     => (int)$r['id_tipo_evento'],
                'nombre' => $r['nombre']
            ];
        }, is_array($rows) ? $rows : []);
        echo json_encode($out, JSON_UNESCAPED_UNICODE); die();
    }

    public function tipos_evento()
    {
        // Permite null => solo globales
        $tipo = isset($_GET['tipo_operacion_id']) && $_GET['tipo_operacion_id'] !== ''
              ? (int)$_GET['tipo_operacion_id']
              : null;

        $rows = $this->model->listarTiposEventoPorTipoOperacion($tipo);
        $out  = array_map(function($r){
            return [
                'id'     => (int)$r['id_tipo_evento'],
                'nombre' => $r['nombre']
            ];
        }, is_array($rows) ? $rows : []);
        echo json_encode($out, JSON_UNESCAPED_UNICODE); die();
    }
    public function listarTiposEvento(){
        $data = $this->model->listarTiposEvento();
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }

 public function registrar()
    {
        try {
            // Lee POST (usa los name de tu formulario)
            $operacion_id            = isset($_POST['operacion_id']) ? (int) $_POST['operacion_id'] : 0;
            $contenedor_operacion_id = isset($_POST['contenedor_operacion_id']) && $_POST['contenedor_operacion_id'] !== '' 
                                        ? (int) $_POST['contenedor_operacion_id'] : null;
            // OJO: se llama "tipo_operacion_id" en el form, pero corresponde a tipo_evento_id en la tabla
            $tipo_operacion_id       = isset($_POST['tipo_operacion_id']) ? (int) $_POST['tipo_operacion_id'] : 0;
            $fecha                   = isset($_POST['fecha']) ? trim($_POST['fecha']) : '';
            $comentario              = isset($_POST['comentario']) ? trim($_POST['comentario']) : null;

            // Validaciones mínimas
            if ($operacion_id <= 0) {
                return $this->json(['status' => 'warning', 'msg' => 'Selecciona una operación válida.']);
            }
            if ($tipo_operacion_id <= 0) {
                return $this->json(['status' => 'warning', 'msg' => 'Selecciona un tipo de evento.']);
            }
            if ($fecha === '') {
                return $this->json(['status' => 'warning', 'msg' => 'La fecha es obligatoria.']);
            }

            // Arma el payload que espera tu modelo->registrar(array $data)
            $payload = [
                'operacion_id'            => $operacion_id,
                'contenedor_operacion_id' => $contenedor_operacion_id, // null si vacío
                'tipo_operacion_id'       => $tipo_operacion_id,       // (es tipo_evento_id en DB)
                'fecha'                   => $fecha,
                'comentario'              => $comentario
            ];

            $insertId = $this->model->registrar($payload);

            if ($insertId > 0) {
                return $this->json([
                    'status' => 'success',
                    'msg'    => 'Evento registrado correctamente.',
                    'id'     => $insertId
                ]);
            }

            return $this->json(['status' => 'error', 'msg' => 'No se pudo registrar el evento.']);
        } catch (\Throwable $e) {
            return $this->json(['status' => 'error', 'msg' => 'Error inesperado.', 'debug' => $e->getMessage()]);
        }
    }



    /* ==== Helper para responder JSON consistente ==== */
    private function json($data, $code = 200)
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

}
