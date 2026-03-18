<?php
require_once "Models/OperacionesLogModel.php";

class Operaciones_por_partida_eventos extends Controller
{
    /** @var OperacionesLogModel */
    private $opLog;

    public function __construct()
    {
        parent::__construct();

        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        if (empty($_SESSION['nombre_usuario'])) {
            header('Location: ' . BASE_URL . 'admin');
            exit;
        }

        $this->opLog = new OperacionesLogModel();

        header_remove('X-Powered-By');
        // Solo usuarios internos
        $this->requireRoles([1, 2, 11, 15]); //1=admin, 11=supervisor, 2=operador, 15=revisor
    }

    /* ======================
       VISTA
       ====================== */
    public function index()
    {
        $data['title'] = 'Eventos terrestres por operación de partida';
        $this->views->getView($this, "index", $data);
    }

    /* ======================
       LISTAR (Paginado)
       ====================== */
    public function listar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->methodNotAllowed();
        }

        $page    = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = isset($_GET['per_page']) ? min(100, max(1, (int)$_GET['per_page'])) : 10;

        $opId = null;
        if (isset($_GET['op_id']) && $_GET['op_id'] !== '') {
            $opId = (int)$_GET['op_id'];
            if ($opId <= 0) {
                $opId = null;
            }
        }

        $factura = isset($_GET['factura']) ? trim($_GET['factura']) : '';
        $ferro   = isset($_GET['ferro']) ? trim($_GET['ferro']) : '';

        $transportistaId = null;
        if (isset($_GET['transportista_id']) && $_GET['transportista_id'] !== '') {
            $transportistaId = (int)$_GET['transportista_id'];
            if ($transportistaId <= 0) {
                $transportistaId = null;
            }
        }

        $destinoId = null;
        if (isset($_GET['destino_id']) && $_GET['destino_id'] !== '') {
            $destinoId = (int)$_GET['destino_id'];
            if ($destinoId <= 0) {
                $destinoId = null;
            }
        }

        $result = $this->model->listarPaginado(
            $page,
            $perPage,
            $opId,
            $factura,
            $ferro,
            $transportistaId,
            $destinoId
        );

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'total'    => (int)($result['total'] ?? 0),
            'page'     => $page,
            'per_page' => $perPage,
            'data'     => $result['data'] ?? []
        ], JSON_UNESCAPED_UNICODE);
        exit;
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
            error_log('eventos_ferro_columnas partida: ' . $e->getMessage());
            http_response_code(500);

            echo json_encode([
                'ok'    => false,
                'error' => 'No fue posible obtener las columnas de eventos'
            ], JSON_UNESCAPED_UNICODE);
        }

        die();
    }

    /* =============================================================
       AUTOCOMPLETE: ENVÍOS / FERRO / FACTURA / CLIENTE
       ============================================================= */
    public function sugerir_operaciones()
    {
        header('Content-Type: application/json; charset=UTF-8');

        $term  = isset($_GET['term']) ? trim((string)$_GET['term']) : '';
        $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 8;

        if ($term === '') {
            echo json_encode([], JSON_UNESCAPED_UNICODE);
            die();
        }

        try {
            $rows = $this->model->sugerirOperacionesPartidaOFerro($term, $limit);

            $out = array_map(function ($r) {
                return [
                    'id'         => (int)($r['id'] ?? 0),        // id_envio
                    'label'      => (string)($r['label'] ?? ''), // ENV-{id}
                    'ferro'      => (string)($r['ferro'] ?? ''),
                    'contenedor' => (string)($r['factura'] ?? '') // compat con frontend viejo
                ];
            }, is_array($rows) ? $rows : []);

            echo json_encode($out, JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            error_log('sugerir_operaciones partida: ' . $e->getMessage());
            echo json_encode([], JSON_UNESCAPED_UNICODE);
        }

        die();
    }

    /* =============================================================
       AUTOCOMPLETE: FERROS DE UN ENVÍO
       GET ?operacion_id=123[&term=FX...&limit=10]
       ============================================================= */
    public function buscar_ferros()
    {
        header('Content-Type: application/json; charset=UTF-8');

        $opId  = isset($_GET['operacion_id']) ? (int)$_GET['operacion_id'] : 0;
        if ($opId <= 0 && isset($_GET['envio_id'])) {
            $opId = (int)$_GET['envio_id'];
        }

        $term  = isset($_GET['term']) ? trim((string)$_GET['term']) : '';
        $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 10;

        if ($opId <= 0) {
            echo json_encode([], JSON_UNESCAPED_UNICODE);
            die();
        }

        try {
            $rows = $this->model->buscarFerrosDeOperacion($opId, $term, $limit);

            $out = array_map(function ($r) {
                return [
                    'id'    => (int)($r['id'] ?? 0),
                    'label' => (string)($r['label'] ?? ''),
                    'tipo'  => (string)($r['tipo'] ?? 'FERRO')
                ];
            }, is_array($rows) ? $rows : []);

            echo json_encode($out, JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            error_log('buscar_ferros partida: ' . $e->getMessage());
            echo json_encode([], JSON_UNESCAPED_UNICODE);
        }

        die();
    }

    /* =============================================================
       OBTENER FERRO PRINCIPAL (1:1) DE UN ENVÍO
       GET ?operacion_id=123  -> {id,label} | null
       ============================================================= */
    public function ferro_de_operacion()
    {
        header('Content-Type: application/json; charset=UTF-8');

        $opId = isset($_GET['operacion_id']) ? (int)$_GET['operacion_id'] : 0;
        if ($opId <= 0 && isset($_GET['envio_id'])) {
            $opId = (int)$_GET['envio_id'];
        }

        if ($opId <= 0) {
            echo json_encode(null, JSON_UNESCAPED_UNICODE);
            die();
        }

        $row = $this->model->getFerroDeOperacion($opId);
        echo json_encode($row ?: null, JSON_UNESCAPED_UNICODE);
        die();
    }

    /* =============================================================
       CATÁLOGO DE TIPOS DE EVENTO TERRESTRES
       GET -> [{id, nombre}]
       ============================================================= */
    public function tipos_evento()
    {
        header('Content-Type: application/json; charset=UTF-8');

        try {
            $rows = $this->model->listarTiposEventoTerrestre();

            $out = array_map(function ($r) {
                return [
                    'id'     => (int)($r['id_tipo_evento'] ?? 0),
                    'nombre' => (string)($r['nombre'] ?? '')
                ];
            }, is_array($rows) ? $rows : []);

            echo json_encode($out, JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            error_log('tipos_evento partida: ' . $e->getMessage());
            echo json_encode([], JSON_UNESCAPED_UNICODE);
        }

        die();
    }

    /* ======================
       REGISTRAR EVENTO
       ====================== */
    public function registrar()
    {
        header('Content-Type: application/json; charset=UTF-8');

        $evento = [
            'operacion_ferro_id'    => (int)($_POST['operacion_ferro_id'] ?? $_POST['envio_partida_id'] ?? 0),
            'contenedor_fisico_id'  => (int)($_POST['contenedor_fisico_id'] ?? 0),
            'tipo_evento_id'        => (int)($_POST['tipo_evento_id'] ?? 0),
            'fecha'                 => trim($_POST['fecha'] ?? ''),
            'comentario'            => trim($_POST['comentario'] ?? '')
        ];

        if ($evento['operacion_ferro_id'] <= 0) {
            echo json_encode(['status' => 'warning', 'msg' => 'Selecciona un envío por partida.']);
            die();
        }
        if ($evento['contenedor_fisico_id'] <= 0) {
            echo json_encode(['status' => 'warning', 'msg' => 'Falta el ferro/caja ligado al envío.']);
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
            $desc = $this->makeDesc('Evento PARTIDA creado', [
                'id_evento' => $id,
                'envio'     => $evento['operacion_ferro_id'],
                'ferro_id'  => $evento['contenedor_fisico_id'],
                'tipo_evt'  => $evento['tipo_evento_id'],
                'fecha'     => $evento['fecha']
            ]);

            //$this->logOp($evento['operacion_ferro_id'], 'creacion', $desc);

            echo json_encode([
                'status' => 'success',
                'msg'    => 'Evento registrado',
                'id'     => $id
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'msg'    => 'No fue posible registrar el evento (valida envío, ferro activo/pertenencia, tipo terrestre y duplicados).'
            ]);
        }

        die();
    }

    /* =============================================================
       OBTENER EVENTO POR (ENVÍO, Ferro, Tipo)
       GET ?operacion_ferro_id=&contenedor_fisico_id=&tipo_evento_id=
       ============================================================= */
    public function obtener_por_clave()
    {
        header('Content-Type: application/json; charset=UTF-8');

        $opId  = (int)($_GET['operacion_ferro_id'] ?? $_GET['envio_partida_id'] ?? 0);
        $ferId = (int)($_GET['contenedor_fisico_id'] ?? 0);
        $evtId = (int)($_GET['tipo_evento_id'] ?? 0);

        if ($opId <= 0 || $ferId <= 0 || $evtId <= 0) {
            echo json_encode(null, JSON_UNESCAPED_UNICODE);
            die();
        }

        $row = $this->model->obtenerEventoPorClave($opId, $ferId, $evtId);
        echo json_encode($row ?: null, JSON_UNESCAPED_UNICODE);
        die();
    }

    /* ======================
       ACTUALIZAR EVENTO
       ====================== */
    public function actualizar()
    {
        header('Content-Type: application/json; charset=UTF-8');

        $evento = [
            'id_evento'            => (int)($_POST['id_evento'] ?? 0),
            'operacion_ferro_id'   => (int)($_POST['operacion_ferro_id'] ?? $_POST['envio_partida_id'] ?? 0),
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
            echo json_encode(['status' => 'warning', 'msg' => 'Selecciona un envío por partida.']);
            die();
        }
        if ($evento['contenedor_fisico_id'] <= 0) {
            echo json_encode(['status' => 'warning', 'msg' => 'Falta el ferro/caja ligado al envío.']);
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
                $desc = $this->makeDesc('Evento PARTIDA actualizado', [
                    'id_evento' => $evento['id_evento'],
                    'envio'     => $evento['operacion_ferro_id'],
                    'ferro_id'  => $evento['contenedor_fisico_id'],
                    'tipo_evt'  => $evento['tipo_evento_id'],
                    'fecha'     => $evento['fecha']
                ]);

                $this->logOp($evento['operacion_ferro_id'], 'actualizacion', $desc);

                echo json_encode([
                    'status' => 'success',
                    'msg'    => 'Evento actualizado'
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'msg'    => 'No fue posible actualizar (valida envío, ferro activo/pertenencia, tipo terrestre y duplicados).'
                ]);
            }
        } catch (\Throwable $e) {
            error_log('actualizar evento partida: ' . $e->getMessage());
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
            error_log('eliminar evento partida: ' . $e->getMessage());
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

    private function methodNotAllowed(): void
    {
        $this->jsonResponse([
            'ok'  => false,
            'msg' => 'Método no permitido.'
        ], 405);
    }
    private function jsonResponse($data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
