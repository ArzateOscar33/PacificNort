<?php
require_once "Models/OperacionesLogModel.php";


class Operaciones_maritimo_ferro_eventos_fer extends Controller
{
    /** @var OperacionesLogModel */
    private $opLog;


    public function __construct()
    {
        parent::__construct();
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $this->opLog = new OperacionesLogModel();

        header_remove('X-Powered-By');
    }

    /* ======================
       LISTAR (Paginado FO)
       ====================== */
    public function listar()
    {
        header('Content-Type: application/json; charset=UTF-8');

        $page    = isset($_GET['page'])     ? max(1, (int)$_GET['page']) : 1;
        $perPage = isset($_GET['per_page']) ? min(1000000, max(1, (int)$_GET['per_page'])) : 10;

        // op_id puede venir como marítima o como FO legacy
        $opId = null;
        if (isset($_GET['op_id']) && $_GET['op_id'] !== '') $opId = (int)$_GET['op_id'];
        if ($opId === null && isset($_GET['operacion_id']) && $_GET['operacion_id'] !== '') $opId = (int)$_GET['operacion_id'];
        if ($opId === null && isset($_GET['mar_id']) && $_GET['mar_id'] !== '') $opId = (int)$_GET['mar_id'];

        // ferro_id / cont_id compat
        $ferroId = (isset($_GET['ferro_id']) && $_GET['ferro_id'] !== '') ? (int)$_GET['ferro_id'] : null;
        if ($ferroId === null && isset($_GET['cont_id']) && $_GET['cont_id'] !== '') {
            $ferroId = (int)$_GET['cont_id'];
        }

        $q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';

        try {
            $res = $this->model->listarEventosFOPaginado($page, $perPage, $opId, $ferroId, $q);

            echo json_encode([
                'data'     => $res['rows']     ?? [],
                'total'    => (int)($res['total']    ?? 0),
                'page'     => (int)($res['page']     ?? $page),
                'per_page' => (int)($res['per_page'] ?? $perPage)
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            error_log('listar eventos terrestres (MF): ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'data'     => [],
                'total'    => 0,
                'page'     => $page,
                'per_page' => $perPage,
                'error'    => 'No fue posible obtener el listado.'
            ], JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    /* =============================================================
       COLUMNAS (catálogo de tipos de evento TERRESTRES)
       Normaliza a [{id, nombre, key}]
       ============================================================= */
    public function eventos_ferro_columnas()
    {
        header('Content-Type: application/json; charset=UTF-8');

        try {
            $rows = $this->model->listarTiposEventoTerrestre();
            $slug = function (string $s): string {
                $s = mb_strtolower($s, 'UTF-8');
                $s = preg_replace('/[^a-z0-9]+/u', '_', $s);
                return trim($s, '_');
            };
            $out = array_map(function ($r) use ($slug) {
                $id  = (int)($r['id_tipo_evento'] ?? $r['id'] ?? 0);
                $nom = (string)($r['nombre'] ?? '');
                return [
                    'id'     => $id,
                    'nombre' => $nom,
                    'key'    => $slug($nom)
                ];
            }, is_array($rows) ? $rows : []);

            echo json_encode([
                'ok'      => true,
                'count'   => count($out),
                'columns' => $out
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            error_log('eventos_ferro_columnas: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'ok'    => false,
                'error' => 'No fue posible obtener las columnas de eventos ferroviarios'
            ], JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    /* =============================================================
       AUTOCOMPLETE: OPERACIONES FO
       GET ?term=FO-01[&limit=8]
       Respuesta: [{id,label,ferro}]
       ============================================================= */
    public function sugerir_operaciones()
    {
        header('Content-Type: application/json; charset=UTF-8');

        $term  = isset($_GET['term'])  ? trim((string)$_GET['term'])  : '';
        $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit'])  : 8;

        if ($term === '') {
            echo json_encode([], JSON_UNESCAPED_UNICODE);
            die();
        }

        try {
            $rows = $this->model->sugerirOperacionesFO($term, $limit);
            $out  = array_map(function ($r) {
                return [
                    'id'    => (int)($r['id'] ?? 0),          // id_operacion_ferro
                    'label' => (string)($r['label'] ?? ''),   // numero_operacion
                    'ferro' => (string)($r['ferro'] ?? '')    // numero_ferro (si existe)
                ];
            }, is_array($rows) ? $rows : []);
            echo json_encode($out, JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            error_log('sugerir_operaciones FO: ' . $e->getMessage());
            echo json_encode([], JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    /* =============================================================
       AUTOCOMPLETE: FERROS DE UNA OPERACIÓN FO
       GET ?operacion_id=123[&term=FX...&limit=10]
       Respuesta: [{id,label,tipo}] // id = id_fisico
       ============================================================= */
    public function buscar_ferros()
    {
        header('Content-Type: application/json; charset=UTF-8');

        $opId  = isset($_GET['operacion_id']) ? (int)$_GET['operacion_id'] : 0;
        $term  = isset($_GET['term']) ? trim((string)$_GET['term']) : '';
        $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 10;

        if ($opId <= 0) {
            echo json_encode([], JSON_UNESCAPED_UNICODE);
            die();
        }

        try {
            $rows = $this->model->buscarFerrosDeOperacion($opId, $term, $limit);
            $out  = array_map(function ($r) {
                return [
                    'id'    => (int)($r['id'] ?? 0),
                    'label' => (string)($r['label'] ?? ''),
                    'tipo'  => (string)($r['tipo'] ?? 'FERRO')
                ];
            }, is_array($rows) ? $rows : []);
            echo json_encode($out, JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            error_log('buscar_ferros FO: ' . $e->getMessage());
            echo json_encode([], JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    /* =============================================================
       OBTENER FERRO PRINCIPAL (1:1) DE UNA OPERACIÓN FO
       GET ?operacion_id=123  -> {id,label} | null
       ============================================================= */
    public function ferro_de_operacion()
    {
        header('Content-Type: application/json; charset=UTF-8');
        $opId = isset($_GET['operacion_id']) ? (int)$_GET['operacion_id'] : 0;
        if ($opId <= 0) {
            echo json_encode(null, JSON_UNESCAPED_UNICODE);
            die();
        }

        $row = $this->model->getFerroDeOperacion($opId);
        echo json_encode($row ?: null, JSON_UNESCAPED_UNICODE);
        die();
    }

    /* =============================================================
       CATÁLOGO DE TIPOS DE EVENTO TERRESTRES (simple)
       GET -> [{id, nombre}]
       ============================================================= */
    public function tipos_evento()
    {
        header('Content-Type: application/json; charset=UTF-8');

        try {
            $rows = $this->model->listarTiposEventoTerrestre();
            $out  = array_map(function ($r) {
                return [
                    'id'     => (int)($r['id_tipo_evento'] ?? 0),
                    'nombre' => (string)($r['nombre'] ?? '')
                ];
            }, is_array($rows) ? $rows : []);
            echo json_encode($out, JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            error_log('tipos_evento (ferro): ' . $e->getMessage());
            echo json_encode([], JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    /* ======================
       REGISTRAR EVENTO FER
       ====================== */
    public function registrar()
    {
        header('Content-Type: application/json; charset=UTF-8');

        // Nombres alineados al modelo FER:
        // operacion_ferro_id, contenedor_fisico_id, tipo_evento_id, fecha, comentario
        $evento = [
            'operacion_ferro_id'  => (int)($_POST['operacion_ferro_id'] ?? 0),
            'contenedor_fisico_id' => (int)($_POST['contenedor_fisico_id'] ?? 0),
            'tipo_evento_id'      => (int)($_POST['tipo_evento_id'] ?? 0),
            'fecha'               => trim($_POST['fecha'] ?? ''),
            'comentario'          => trim($_POST['comentario'] ?? '')
        ];

        if ($evento['operacion_ferro_id'] <= 0) {
            echo json_encode(['status' => 'warning', 'msg' => 'Selecciona una operación FO.']);
            die();
        }
        if ($evento['contenedor_fisico_id'] <= 0) {
            echo json_encode(['status' => 'warning', 'msg' => 'Falta el ferro/caja ligado a la operación.']);
            die();
        }
        if ($evento['tipo_evento_id'] <= 0) {
            echo json_encode(['status' => 'warning', 'msg' => 'Selecciona un tipo de evento terrestre.']);
            die();
        }
        if ($evento['fecha'] === '') {
            echo json_encode(['status' => 'warning', 'msg' => 'Indica la fecha del evento.']);
            die();
        }

        $usuarioId = (int)($_SESSION['id_usuario'] ?? 0);
        $id = $this->model->registrar($evento, $usuarioId);

        if ($id > 0) {
            $desc = $this->makeDesc('Evento FER creado', [
                'id_evento'  => $id,
                'op_ferro'   => $evento['operacion_ferro_id'],
                'ferro_id'   => $evento['contenedor_fisico_id'],
                'tipo_evt'   => $evento['tipo_evento_id'],
                'fecha'      => $evento['fecha']
            ]);
            $this->logOp($evento['operacion_ferro_id'], 'creacion', $desc);
            echo json_encode(['status' => 'success', 'msg' => 'Evento registrado', 'id' => $id]);
        } else {
            echo json_encode(['status' => 'error', 'msg' => 'No fue posible registrar el evento (valida FO, ferro activo/pertenencia, tipo terrestre y duplicados).']);
        }
        die();
    }

    /* =============================================================
       OBTENER EVENTO POR (FO, Ferro, Tipo)
       GET ?operacion_ferro_id=&contenedor_fisico_id=&tipo_evento_id=
       ============================================================= */
    public function obtener_por_clave()
    {
        header('Content-Type: application/json; charset=UTF-8');

        $opId   = (int)($_GET['operacion_ferro_id']   ?? 0);
        $ferId  = (int)($_GET['contenedor_fisico_id'] ?? 0);
        $evtId  = (int)($_GET['tipo_evento_id']       ?? 0);

        if ($opId <= 0 || $ferId <= 0 || $evtId <= 0) {
            echo json_encode(null, JSON_UNESCAPED_UNICODE);
            die();
        }

        $row = $this->model->obtenerEventoPorClave($opId, $ferId, $evtId);
        echo json_encode($row ?: null, JSON_UNESCAPED_UNICODE);
        die();
    }

    /* ======================
       ACTUALIZAR EVENTO FER
       ====================== */
    public function actualizar()
    {
        header('Content-Type: application/json; charset=UTF-8');

        $evento = [
            'id_evento'            => (int)($_POST['id_evento'] ?? 0),
            'operacion_ferro_id'   => (int)($_POST['operacion_ferro_id'] ?? 0),
            'contenedor_fisico_id' => (int)($_POST['contenedor_fisico_id'] ?? 0),
            'tipo_evento_id'       => (int)($_POST['tipo_evento_id'] ?? 0),
            'fecha'                => trim($_POST['fecha'] ?? ''),
            'comentario'           => trim($_POST['comentario'] ?? '')
        ];

        if ($evento['id_evento'] <= 0) {
            echo json_encode(['status' => 'warning', 'msg' => 'Falta id_evento']);
            die();
        }
        if ($evento['operacion_ferro_id'] <= 0) {
            echo json_encode(['status' => 'warning', 'msg' => 'Selecciona una operación FO.']);
            die();
        }
        if ($evento['contenedor_fisico_id'] <= 0) {
            echo json_encode(['status' => 'warning', 'msg' => 'Falta el ferro/caja ligado a la operación.']);
            die();
        }
        if ($evento['tipo_evento_id'] <= 0) {
            echo json_encode(['status' => 'warning', 'msg' => 'Selecciona un tipo de evento terrestre.']);
            die();
        }
        if ($evento['fecha'] === '') {
            echo json_encode(['status' => 'warning', 'msg' => 'Indica la fecha del evento.']);
            die();
        }

        try {
            $ok = $this->model->actualizar($evento);
            if ($ok) {
                $desc = $this->makeDesc('Evento FER actualizado', [
                    'id_evento' => $evento['id_evento'],
                    'op_ferro'  => $evento['operacion_ferro_id'],
                    'ferro_id'  => $evento['contenedor_fisico_id'],
                    'tipo_evt'  => $evento['tipo_evento_id'],
                    'fecha'     => $evento['fecha']
                ]);
                $this->logOp($evento['operacion_ferro_id'], 'actualizacion', $desc);
                echo json_encode(['status' => 'success', 'msg' => 'Evento actualizado']);
            } else {
                echo json_encode(['status' => 'error', 'msg' => 'No fue posible actualizar (valida FO, ferro activo/pertenencia, tipo terrestre y duplicados).']);
            }
        } catch (\Throwable $e) {
            error_log('actualizar evento FER: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['status' => 'error', 'msg' => 'Error interno al actualizar.']);
        }
        die();
    }

    /* ======================
       ELIMINAR (baja lógica)
       ====================== */
    public function eliminar()
    {
        header('Content-Type: application/json; charset=UTF-8');

        $id = (int)($_POST['id_evento'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['status' => 'warning', 'msg' => 'Falta id_evento']);
            die();
        }

        try {
            $ok = $this->model->eliminar($id);
            echo json_encode([
                'status' => $ok ? 'success' : 'error',
                'msg'    => $ok ? 'Evento eliminado' : 'No se pudo eliminar'
            ]);
        } catch (\Throwable $e) {
            error_log('eliminar evento FER: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['status' => 'error', 'msg' => 'Error interno al eliminar.']);
        }
        die();
    }

    /* ==========================
       Helpers de auditoría
       ========================== */
    private function logOp(int $operacionId, string $accion, string $descripcion): void
    {
        try {
            $usuarioId = (int)($_SESSION['id_usuario'] ?? 0);
            $id = $this->opLog->crear($operacionId, $usuarioId, $accion, $descripcion);
            if (!$id) {
                error_log("operaciones_log: insert falló ({$accion}) op={$operacionId}");
            }
        } catch (\Throwable $e) {
            error_log("operaciones_log error: " . $e->getMessage());
        }
    }

    private function makeDesc(string $base, array $info = []): string
    {
        if (empty($info)) return $base;
        $kv = [];
        foreach ($info as $k => $v) {
            $kv[] = "$k=$v";
        }
        return $base . ' (' . implode(', ', $kv) . ')';
    }
}
