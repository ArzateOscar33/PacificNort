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
    }

    /* ===== Helpers de auditoría ===== */
    private function logOp(int $operacionId, string $accion, string $descripcion): void
    {
        try {
            $usuarioId = (int)($_SESSION['id_usuario'] ?? 0);
            $id = $this->opLog->crear($operacionId, $usuarioId, $accion, $descripcion);
            if (!$id) { error_log("operaciones_log: insert falló ({$accion}) op={$operacionId}"); }
        } catch (\Throwable $e) {
            error_log("operaciones_log error: ".$e->getMessage());
        }
    }
    private function makeDesc(string $base, array $info = []): string
    {
        if (empty($info)) return $base;
        $kv = [];
        foreach ($info as $k => $v) { $kv[] = "$k=$v"; }
        return $base.' ('.implode(', ', $kv).')';
    }

    public function listar()
    {
        header('Content-Type: application/json; charset=UTF-8');

        // Parámetros opcionales (filtros + paginado)
        $page    = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = isset($_GET['per_page']) ? min(100, max(1, (int)$_GET['per_page'])) : 10;

        $opId   = (isset($_GET['op_id'])   && $_GET['op_id']   !== '') ? (int)$_GET['op_id']   : null;
        $contId = (isset($_GET['cont_id']) && $_GET['cont_id'] !== '') ? (int)$_GET['cont_id'] : null;
        $q      = isset($_GET['q']) ? trim($_GET['q']) : '';

        // Llama al modelo (ya ordena por Operación, Contenedor, Fecha DESC)
        $res = $this->model->listarPaginado($page, $perPage, $opId, $contId, $q);

        echo json_encode([
            'data'     => $res['rows'],
            'total'    => (int)$res['total'],
            'page'     => $page,
            'per_page' => $perPage
        ], JSON_UNESCAPED_UNICODE);
        die();
    }

    /** GET ?term=LI */
    public function buscar_operaciones()
    {
        $term  = isset($_GET['term']) ? trim($_GET['term']) : '';
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;

        if (mb_strlen($term) < 1) {
            echo json_encode([], JSON_UNESCAPED_UNICODE);
            die();
        }

        $ops = $this->model->buscarOperacionesMaritimas($term, $limit);

        // Estructura estándar: [{id, label, meta}]
        $out = array_map(function ($row) {
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

        if ($operacionId <= 0) {
            echo json_encode([], JSON_UNESCAPED_UNICODE);
            die();
        }

        $rows = $this->model->buscarContenedoresDeOperacion($operacionId, $term, $limit);

        // Estructura estándar: [{id, label, tipo}]
        $out = array_map(function ($r) {
            return [
                'id'    => (int)$r['id'],     // id_fisico o id_contenedor_maritimo (según tipo)
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
        $out  = array_map(function ($r) {
            return [
                'id'     => (int)$r['id_tipo_evento'],
                'nombre' => $r['nombre']
            ];
        }, is_array($rows) ? $rows : []);
        echo json_encode($out, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function tipos_evento()
    {
        $tipo = isset($_GET['tipo_operacion_id']) && $_GET['tipo_operacion_id'] !== ''
            ? (int)$_GET['tipo_operacion_id']
            : null;

        $rows = $this->model->listarTiposEventoPorTipoOperacion($tipo);
        $out  = array_map(function ($r) {
            return [
                'id'     => (int)$r['id_tipo_evento'],
                'nombre' => $r['nombre']
            ];
        }, is_array($rows) ? $rows : []);
        echo json_encode($out, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function listarTiposEvento()
    {
        $data = $this->model->listarTiposEvento();
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }

    /* ================== CRUD EVENTOS (con LOG) ================== */

    public function registrar()
    {
        header('Content-Type: application/json; charset=UTF-8');

        $evento = [
            'operacion_id'               => (int)($_POST['operacion_id'] ?? 0),
            'tipo_evento_id'             => (int)($_POST['tipo_evento_id'] ?? 0),
            'fecha'                      => trim($_POST['fecha'] ?? ''),
            'comentario'                 => trim($_POST['comentario'] ?? ''),
            'contenedor_operacion_id'    => isset($_POST['contenedor_operacion_id']) && $_POST['contenedor_operacion_id'] !== '' ? (int)$_POST['contenedor_operacion_id'] : null,
            'cont_maritimo_operacion_id' => isset($_POST['cont_maritimo_operacion_id']) && $_POST['cont_maritimo_operacion_id'] !== '' ? (int)$_POST['cont_maritimo_operacion_id'] : null,
        ];

        if ($evento['operacion_id'] <= 0 || $evento['tipo_evento_id'] <= 0 || $evento['fecha'] === '') {
            echo json_encode(['status' => 'warning', 'msg' => 'Faltan campos requeridos (operación, tipo de evento o fecha)']);
            die();
        }

        // Duplicados
        if (!empty($evento['contenedor_operacion_id'])) {
            if ($this->model->existeEventoFisicoDuplicado($evento['contenedor_operacion_id'], $evento['tipo_evento_id'])) {
                echo json_encode(['status' => 'warning', 'msg' => 'Ese contenedor ya tiene ese tipo de evento (activo).']);
                die();
            }
        } elseif (!empty($evento['cont_maritimo_operacion_id'])) {
            if ($this->model->existeEventoMaritimoDuplicado($evento['cont_maritimo_operacion_id'], $evento['tipo_evento_id'])) {
                echo json_encode(['status' => 'warning', 'msg' => 'Ese contenedor marítimo ya tiene ese tipo de evento (activo).']);
                die();
            }
        }

        $usuarioId = (int)($_SESSION['id_usuario'] ?? 0);
        $id = $this->model->registrar($evento, $usuarioId);

        if ($id) {
            // === LOG: Evento creado ===
            $desc = $this->makeDesc('Evento creado', [
                'id_evento'   => $id,
                'tipo_evt_id' => $evento['tipo_evento_id'],
                'fecha'       => $evento['fecha'],
                'cont_tipo'   => $evento['contenedor_operacion_id'] ? 'FISICO' : ($evento['cont_maritimo_operacion_id'] ? 'MARITIMO' : 'OP'),
                'cont_ref'    => $evento['contenedor_operacion_id'] ?? ($evento['cont_maritimo_operacion_id'] ?? '-')
            ]);
            $this->logOp($evento['operacion_id'], 'creacion', $desc);
        }

        echo json_encode($id
            ? ['status' => 'success', 'msg' => 'Evento registrado', 'id' => $id]
            : ['status' => 'error',   'msg' => 'No se registró el evento']
        );
        die();
    }

    public function editar($id)
    {
        $data = $this->model->obtenerEvento($id);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function actualizar()
    {
        header('Content-Type: application/json; charset=UTF-8');

        $data = [
            'id_evento'                  => (int)($_POST['id_evento'] ?? 0),
            'operacion_id'               => (int)($_POST['operacion_id'] ?? 0), // será reemplazado
            'tipo_evento_id'             => (int)($_POST['tipo_evento_id'] ?? 0),
            'fecha'                      => trim($_POST['fecha'] ?? ''),
            'comentario'                 => trim($_POST['comentario'] ?? ''),
            'contenedor_operacion_id'    => isset($_POST['contenedor_operacion_id']) && $_POST['contenedor_operacion_id'] !== '' ? (int)$_POST['contenedor_operacion_id'] : null,
            'cont_maritimo_operacion_id' => isset($_POST['cont_maritimo_operacion_id']) && $_POST['cont_maritimo_operacion_id'] !== '' ? (int)$_POST['cont_maritimo_operacion_id'] : null,
        ];

        if ($data['id_evento'] <= 0 || $data['tipo_evento_id'] <= 0 || $data['fecha'] === '') {
            echo json_encode(['status' => 'warning', 'msg' => 'Datos incompletos']);
            die();
        }

        // Congelar operación/contenedor desde el original
        $orig = $this->model->obtenerEvento($data['id_evento']);
        if (!$orig) {
            echo json_encode(['status' => 'warning', 'msg' => 'Evento no encontrado']);
            die();
        }
        $data['operacion_id']               = (int)$orig['operacion_id'];
        $data['contenedor_operacion_id']    = $orig['contenedor_operacion_id'] !== null ? (int)$orig['contenedor_operacion_id'] : null;
        $data['cont_maritimo_operacion_id'] = $orig['cont_maritimo_operacion_id'] !== null ? (int)$orig['cont_maritimo_operacion_id'] : null;

        // Duplicado (con valores fijados)
        if (!empty($data['contenedor_operacion_id'])) {
            if ($this->model->existeEventoFisicoDuplicado($data['contenedor_operacion_id'], $data['tipo_evento_id'], $data['id_evento'])) {
                echo json_encode(['status' => 'warning', 'msg' => 'Ese contenedor ya tiene ese tipo de evento (activo).']);
                die();
            }
        } elseif (!empty($data['cont_maritimo_operacion_id'])) {
            if ($this->model->existeEventoMaritimoDuplicado($data['cont_maritimo_operacion_id'], $data['tipo_evento_id'], $data['id_evento'])) {
                echo json_encode(['status' => 'warning', 'msg' => 'Ese contenedor marítimo ya tiene ese tipo de evento (activo).']);
                die();
            }
        }

        $ok = $this->model->actualizar($data);

        if ($ok) {
            // === LOG: Evento actualizado ===
            $desc = $this->makeDesc('Evento actualizado', [
                'id_evento'   => $data['id_evento'],
                'tipo_evt_id' => $data['tipo_evento_id'],
                'fecha'       => $data['fecha'],
                'cont_tipo'   => $data['contenedor_operacion_id'] ? 'FISICO' : ($data['cont_maritimo_operacion_id'] ? 'MARITIMO' : 'OP'),
                'cont_ref'    => $data['contenedor_operacion_id'] ?? ($data['cont_maritimo_operacion_id'] ?? '-')
            ]);
            $this->logOp($data['operacion_id'], 'actualizacion', $desc);
        }

        echo json_encode($ok
            ? ['status' => 'success', 'msg' => 'Evento actualizado']
            : ['status' => 'error',   'msg' => 'No se actualizó el evento']
        );
        die();
    }

    public function eliminar()
    {
        header('Content-Type: application/json; charset=UTF-8');

        $id = (int)($_POST['id_evento'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['status' => 'warning', 'msg' => 'ID inválido']);
            die();
        }

        // Tomar snapshot para el log (si existe)
        $orig = $this->model->obtenerEvento($id);

        $ok = $this->model->desactivar($id);

        if ($ok) {
            // === LOG: Evento eliminado (baja lógica) ===
            $opId = (int)($orig['operacion_id'] ?? 0);
            if ($opId > 0) {
                $desc = $this->makeDesc('Evento eliminado', [
                    'id_evento'   => $id,
                    'tipo_evt_id' => $orig['tipo_evento_id'] ?? '-',
                    'fecha'       => $orig['fecha'] ?? '-',
                    'cont_tipo'   => !empty($orig['contenedor_operacion_id']) ? 'FISICO' : (!empty($orig['cont_maritimo_operacion_id']) ? 'MARITIMO' : 'OP'),
                    'cont_ref'    => $orig['contenedor_operacion_id'] ?? ($orig['cont_maritimo_operacion_id'] ?? '-')
                ]);
                $this->logOp($opId, 'cancelacion', $desc);
            }
        }

        echo json_encode($ok
            ? ['status' => 'success', 'msg' => 'Evento eliminado']
            : ['status' => 'error',   'msg' => 'No se pudo eliminar']
        );
        die();
    }
    /** GET ?operacion_id=123 -> {id,label} del contenedor marítimo */
public function contenedor_maritimo_de_operacion()
{
    header('Content-Type: application/json; charset=UTF-8');
    $opId = isset($_GET['operacion_id']) ? (int)$_GET['operacion_id'] : 0;
    if ($opId <= 0) { echo json_encode(null); die(); }

    $row = $this->model->getContenedorMaritimoDeOperacion($opId);
    echo json_encode($row ?: null, JSON_UNESCAPED_UNICODE);
    die();
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
