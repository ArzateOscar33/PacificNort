<?php
require_once "Models/OperacionesLogModel.php";

class Operaciones_maritimas_eventos extends Controller
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
        // Solo sin rol cliente
        $this->requireRoles([1, 11, 2]);
    }

    /* =============================================================
       LISTADO MATRIZ (OPERACIONES MARÍTIMAS)
       GET ?page=1&per_page=10[&op_id=...][&cont_id=...][&q=...]
       ============================================================= */
    public function listar()
    {
        header('Content-Type: application/json; charset=UTF-8');

        $page    = isset($_GET['page'])     ? max(1, (int)$_GET['page'])                     : 1;
        $perPage = isset($_GET['per_page']) ? min(100, max(1, (int)$_GET['per_page']))       : 10;

        $opId   = (isset($_GET['op_id'])   && $_GET['op_id']   !== '') ? (int)$_GET['op_id']   : null;
        $contId = (isset($_GET['cont_id']) && $_GET['cont_id'] !== '') ? (int)$_GET['cont_id'] : null; // cmo.id
        $q      = isset($_GET['q']) ? trim((string)$_GET['q']) : '';

        try {
            // SOLO Marítimo (tipo_operacion_id = 1) lo filtra el modelo
            $res = $this->model->listarEventosMarPaginado($page, $perPage, $opId, $contId, $q);

            echo json_encode([
                'data'     => $res['rows']     ?? [],
                'total'    => (int)($res['total']    ?? 0),
                'page'     => (int)($res['page']     ?? $page),
                'per_page' => (int)($res['per_page'] ?? $perPage)
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            error_log('listar eventos marítimos: ' . $e->getMessage());
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
       CATÁLOGO PARA COLUMNAS DINÁMICAS (EVENTOS MARÍTIMOS)
       ============================================================= */
    public function eventos_maritimos_columnas()
    {
        header('Content-Type: application/json; charset=UTF-8');

        try {
            // Del modelo: listarEventosMaritimosParaColumnas()
            $rows = $this->model->listarEventosMaritimosParaColumnas();

            $out = array_map(function ($r) {
                return [
                    'id'     => (int)($r['id'] ?? $r['id_tipo_evento'] ?? 0),
                    'nombre' => (string)($r['nombre'] ?? ''),
                    'key'    => (string)($r['key'] ?? '')
                ];
            }, is_array($rows) ? $rows : []);

            echo json_encode([
                'ok'      => true,
                'count'   => count($out),
                'columns' => $out
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            error_log('eventos_maritimos_columnas (Mar): ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'ok'    => false,
                'error' => 'No fue posible obtener las columnas de eventos marítimos'
            ], JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    /* =============================================================
       AUTOCOMPLETE: OPERACIONES MARÍTIMAS (para modal)
       GET ?term=LI-01[&limit=10]
       Respuesta: [{id, label, meta}]
       ============================================================= */
    public function buscar_operaciones()
    {
        header('Content-Type: application/json; charset=UTF-8');

        $term  = isset($_GET['term'])  ? trim($_GET['term'])  : '';
        $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 10;

        if ($term === '') {
            echo json_encode([], JSON_UNESCAPED_UNICODE);
            die();
        }

        try {
            $rows = $this->model->buscarOperacionesMaritimas($term, $limit);

            $out  = array_map(function ($r) {
                $contenedores = isset($r['contenedores']) ? trim((string)$r['contenedores']) : '';
                $fallback     = isset($r['maritimos']) ? ((int)$r['maritimos'] . ' contenedor(es)') : '';

                return [
                    'id'    => (int)($r['id'] ?? 0),
                    'label' => (string)($r['label'] ?? ''),   // numero_operacion
                    'meta'  => $contenedores !== '' ? $contenedores : $fallback,
                ];
            }, is_array($rows) ? $rows : []);

            echo json_encode($out, JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            error_log('buscar_operaciones (Mar): ' . $e->getMessage());
            echo json_encode([], JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    /* =============================================================
       AUTOCOMPLETE (filtro superior): OPERACIONES MARÍTIMAS
       GET ?term=LI-01[&limit=10]
       Respuesta: [{id, label, meta}]
       ============================================================= */
    public function sugerir_operaciones()
    {
        header('Content-Type: application/json; charset=UTF-8');

        $term  = isset($_GET['term'])  ? trim((string)$_GET['term'])  : '';
        $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit'])  : 10;

        if ($term === '') {
            echo json_encode([], JSON_UNESCAPED_UNICODE);
            die();
        }

        try {
            $rows = $this->model->buscarOperacionesMaritimas($term, $limit);

            $out = array_map(function ($r) {
                $contenedores = isset($r['contenedores']) ? trim((string)$r['contenedores']) : '';
                $fallback     = isset($r['maritimos']) ? ((int)$r['maritimos'] . ' contenedor(es)') : '';

                return [
                    'id'    => (int)($r['id'] ?? 0),
                    'label' => (string)($r['label'] ?? ''),  // numero_operacion
                    'meta'  => $contenedores !== '' ? $contenedores : $fallback,
                ];
            }, is_array($rows) ? $rows : []);

            echo json_encode($out, JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            error_log('sugerir_operaciones (Mar): ' . $e->getMessage());
            echo json_encode([], JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    /* =============================================================
       AUTOCOMPLETE: CONTENEDORES MARÍTIMOS DE UNA OPERACIÓN
       GET ?operacion_id=123[&term=MSKU&limit=15]
       Respuesta: [{id,label,tipo}]  // id = cmo.id
       ============================================================= */
    public function buscar_contenedores()
    {
        header('Content-Type: application/json; charset=UTF-8');

        $operacionId = isset($_GET['operacion_id']) ? (int)$_GET['operacion_id'] : 0;
        $term        = isset($_GET['term']) ? trim($_GET['term']) : '';
        $limit       = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 15;

        if ($operacionId <= 0) {
            echo json_encode([], JSON_UNESCAPED_UNICODE);
            die();
        }

        try {
            $rows = $this->model->buscarContenedoresMarDeOperacion($operacionId, $term, $limit);

            $out = array_map(function ($r) {
                return [
                    'id'    => (int)($r['id'] ?? 0),         // cmo.id
                    'label' => (string)($r['label'] ?? ''),  // cm.numero_contenedor
                    'tipo'  => (string)($r['tipo'] ?? 'MARITIMO')
                ];
            }, is_array($rows) ? $rows : []);

            echo json_encode($out, JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            error_log('buscar_contenedores (Mar): ' . $e->getMessage());
            echo json_encode([], JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    /** GET ?operacion_id=123 -> {id,label} del contenedor marítimo (primero) */
    public function contenedor_maritimo_de_operacion()
    {
        header('Content-Type: application/json; charset=UTF-8');
        $opId = isset($_GET['operacion_id']) ? (int)$_GET['operacion_id'] : 0;
        if ($opId <= 0) {
            echo json_encode(null, JSON_UNESCAPED_UNICODE);
            die();
        }

        $row = $this->model->getContenedorMaritimoDeOperacion($opId);
        echo json_encode($row ?: null, JSON_UNESCAPED_UNICODE);
        die();
    }

    /* =============================================================
       CATÁLOGO DE TIPOS DE EVENTO (MARÍTIMOS)
       GET -> [{id, nombre}]
       ============================================================= */
    public function tipos_evento()
    {
        header('Content-Type: application/json; charset=UTF-8');

        try {
            $rows = $this->model->listarTiposEventoMaritimo();
            $out  = array_map(function ($r) {
                return [
                    'id'     => (int)($r['id_tipo_evento'] ?? 0),
                    'nombre' => (string)($r['nombre'] ?? '')
                ];
            }, is_array($rows) ? $rows : []);

            echo json_encode($out, JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            error_log('tipos_evento (Mar): ' . $e->getMessage());
            echo json_encode([], JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    /* =============================================================
       Helpers de auditoría (igual que en MF)
       ============================================================= */
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

    /* =============================================================
       REGISTRAR EVENTO MARÍTIMO
       POST: operacion_id, cont_maritimo_operacion_id, tipo_evento_id, fecha, comentario
       ============================================================= */
    public function registrar()
    {
        header('Content-Type: application/json; charset=UTF-8');

        $evento = [
            'operacion_id'               => (int)($_POST['operacion_id'] ?? 0),
            'cont_maritimo_operacion_id' => isset($_POST['cont_maritimo_operacion_id']) ? (int)$_POST['cont_maritimo_operacion_id'] : 0,
            'tipo_evento_id'             => (int)($_POST['tipo_evento_id'] ?? 0),
            'fecha'                      => trim($_POST['fecha'] ?? ''),
            'comentario'                 => trim($_POST['comentario'] ?? '')
        ];

        if ($evento['operacion_id'] <= 0) {
            echo json_encode(['status' => 'warning', 'msg' => 'Selecciona una operación marítima.']);
            die();
        }
        if ($evento['cont_maritimo_operacion_id'] <= 0) {
            echo json_encode(['status' => 'warning', 'msg' => 'Selecciona un contenedor marítimo.']);
            die();
        }
        if ($evento['tipo_evento_id'] <= 0) {
            echo json_encode(['status' => 'warning', 'msg' => 'Selecciona un tipo de evento marítimo.']);
            die();
        }
        if ($evento['fecha'] === '') {
            echo json_encode(['status' => 'warning', 'msg' => 'Indica la fecha del evento.']);
            die();
        }

        $usuarioId = (int)($_SESSION['id_usuario'] ?? 0);
        $id = $this->model->registrar($evento, $usuarioId);

        if ($id > 0) {
            $desc = $this->makeDesc('Evento creado (Mar)', [
                'id_evento'   => $id,
                'operacion'   => $evento['operacion_id'],
                'cmo'         => $evento['cont_maritimo_operacion_id'],
                'tipo_evt_id' => $evento['tipo_evento_id'],
                'fecha'       => $evento['fecha']
            ]);
            $this->logOp($evento['operacion_id'], 'creacion', $desc);

            echo json_encode(['status' => 'success', 'msg' => 'Evento registrado', 'id' => $id]);
        } else {
            echo json_encode([
                'status' => 'error',
                'msg'    => 'No fue posible registrar el evento. Verifica: operación marítima, contenedor activo/pertenencia, tipo de evento marítimo y que no sea duplicado.'
            ]);
        }
        die();
    }

    /* =============================================================
       OBTENER POR CLAVE (para modal de celda)
       GET ?operacion_id=&cont_maritimo_operacion_id=&tipo_evento_id=
       ============================================================= */
    public function obtener_por_clave()
    {
        header('Content-Type: application/json; charset=UTF-8');

        $opId  = (int)($_GET['operacion_id'] ?? 0);
        $cmoId = (int)($_GET['cont_maritimo_operacion_id'] ?? 0);
        $evtId = (int)($_GET['tipo_evento_id'] ?? 0);

        if ($opId <= 0 || $cmoId <= 0 || $evtId <= 0) {
            echo json_encode(null, JSON_UNESCAPED_UNICODE);
            die();
        }

        $row = $this->model->obtenerEventoPorClave($opId, $cmoId, $evtId);
        echo json_encode($row ?: null, JSON_UNESCAPED_UNICODE);
        die();
    }

    /* =============================================================
       ACTUALIZAR
       ============================================================= */
    public function actualizar()
    {
        header('Content-Type: application/json; charset=UTF-8');

        $evento = [
            'id_evento'                  => (int)($_POST['id_evento'] ?? 0),
            'operacion_id'               => (int)($_POST['operacion_id'] ?? 0),
            'cont_maritimo_operacion_id' => (int)($_POST['cont_maritimo_operacion_id'] ?? 0),
            'tipo_evento_id'             => (int)($_POST['tipo_evento_id'] ?? 0),
            'fecha'                      => trim($_POST['fecha'] ?? ''),
            'comentario'                 => trim($_POST['comentario'] ?? '')
        ];

        if ($evento['id_evento'] <= 0) {
            echo json_encode(['status' => 'warning', 'msg' => 'Falta id_evento']);
            die();
        }
        if ($evento['operacion_id'] <= 0) {
            echo json_encode(['status' => 'warning', 'msg' => 'Selecciona una operación marítima.']);
            die();
        }
        if ($evento['cont_maritimo_operacion_id'] <= 0) {
            echo json_encode(['status' => 'warning', 'msg' => 'Selecciona un contenedor marítimo.']);
            die();
        }
        if ($evento['tipo_evento_id'] <= 0) {
            echo json_encode(['status' => 'warning', 'msg' => 'Selecciona un tipo de evento marítimo.']);
            die();
        }
        if ($evento['fecha'] === '') {
            echo json_encode(['status' => 'warning', 'msg' => 'Indica la fecha del evento.']);
            die();
        }

        try {
            $ok = $this->model->actualizar($evento);
            if ($ok) {
                $desc = $this->makeDesc('Evento actualizado (Mar)', [
                    'id_evento'   => $evento['id_evento'],
                    'operacion'   => $evento['operacion_id'],
                    'cmo'         => $evento['cont_maritimo_operacion_id'],
                    'tipo_evt_id' => $evento['tipo_evento_id'],
                    'fecha'       => $evento['fecha']
                ]);
                $this->logOp($evento['operacion_id'], 'actualizacion', $desc);

                echo json_encode(['status' => 'success', 'msg' => 'Evento actualizado']);
            } else {
                echo json_encode(['status' => 'error', 'msg' => 'No fue posible actualizar. Verifica operación marítima, contenedor activo/pertenencia, tipo marítimo y que no sea duplicado.']);
            }
        } catch (\Throwable $e) {
            error_log('actualizar evento Mar: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['status' => 'error', 'msg' => 'Error interno al actualizar.']);
        }
        die();
    }

    /* =============================================================
       ELIMINAR (baja lógica)
       ============================================================= */
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
            error_log('eliminar evento Mar: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['status' => 'error', 'msg' => 'Error interno al eliminar.']);
        }
        die();
    }
}
