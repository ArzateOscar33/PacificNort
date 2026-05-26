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

        // Solo usuarios internos / roles permitidos
        $this->requireRoles([1, 11, 2]);
    }


    /* =============================================================
       LISTAR EVENTOS TERRESTRES - MARÍTIMO FERRO
       GET:
       - page
       - per_page
       - op_id | operacion_id | mar_id
       - ferro_id | cont_id
       - q
       - fecha_desde
       - fecha_hasta
       - transportista_id
       - cliente_id
       - destino_id
       - contenedor | contenedor_maritimo
       - ferro | numero_ferro
       - operacion | numero_operacion
       ============================================================= */
    public function listar()
    {
        header('Content-Type: application/json; charset=UTF-8');

        $page    = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = isset($_GET['per_page']) ? min(1000000, max(1, (int)$_GET['per_page'])) : 10;

        $opId = null;

        if (isset($_GET['op_id']) && $_GET['op_id'] !== '') {
            $tmp = (int)$_GET['op_id'];
            $opId = $tmp > 0 ? $tmp : null;
        }

        if ($opId === null && isset($_GET['operacion_id']) && $_GET['operacion_id'] !== '') {
            $tmp = (int)$_GET['operacion_id'];
            $opId = $tmp > 0 ? $tmp : null;
        }

        if ($opId === null && isset($_GET['mar_id']) && $_GET['mar_id'] !== '') {
            $tmp = (int)$_GET['mar_id'];
            $opId = $tmp > 0 ? $tmp : null;
        }

        $ferroId = null;

        if (isset($_GET['ferro_id']) && $_GET['ferro_id'] !== '') {
            $tmp = (int)$_GET['ferro_id'];
            $ferroId = $tmp > 0 ? $tmp : null;
        }

        if ($ferroId === null && isset($_GET['cont_id']) && $_GET['cont_id'] !== '') {
            $tmp = (int)$_GET['cont_id'];
            $ferroId = $tmp > 0 ? $tmp : null;
        }

        $q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';

        $fechaDesde = (isset($_GET['fecha_desde']) && trim((string)$_GET['fecha_desde']) !== '')
            ? trim((string)$_GET['fecha_desde'])
            : null;

        $fechaHasta = (isset($_GET['fecha_hasta']) && trim((string)$_GET['fecha_hasta']) !== '')
            ? trim((string)$_GET['fecha_hasta'])
            : null;

        $transportistaId = null;

        if (isset($_GET['transportista_id']) && $_GET['transportista_id'] !== '') {
            $tmp = (int)$_GET['transportista_id'];
            $transportistaId = $tmp > 0 ? $tmp : null;
        }

        $clienteId = null;

        if (isset($_GET['cliente_id']) && $_GET['cliente_id'] !== '') {
            $tmp = (int)$_GET['cliente_id'];
            $clienteId = $tmp > 0 ? $tmp : null;
        }

        $destinoId = null;

        if (isset($_GET['destino_id']) && $_GET['destino_id'] !== '') {
            $tmp = (int)$_GET['destino_id'];
            $destinoId = $tmp > 0 ? $tmp : null;
        }

        $contenedor = '';

        if (isset($_GET['contenedor'])) {
            $contenedor = trim((string)$_GET['contenedor']);
        } elseif (isset($_GET['contenedor_maritimo'])) {
            $contenedor = trim((string)$_GET['contenedor_maritimo']);
        }

        $ferro = '';

        if (isset($_GET['ferro'])) {
            $ferro = trim((string)$_GET['ferro']);
        } elseif (isset($_GET['numero_ferro'])) {
            $ferro = trim((string)$_GET['numero_ferro']);
        }

        $operacion = '';

        if (isset($_GET['operacion'])) {
            $operacion = trim((string)$_GET['operacion']);
        } elseif (isset($_GET['numero_operacion'])) {
            $operacion = trim((string)$_GET['numero_operacion']);
        }

        try {
            $res = $this->model->listarEventosFOPaginado(
                $page,
                $perPage,
                $opId,
                $ferroId,
                $q,
                $fechaDesde,
                $fechaHasta,
                $transportistaId,
                $clienteId,
                $destinoId,
                $contenedor,
                $ferro,
                $operacion
            );

            echo json_encode([
                'status'   => 'success',
                'data'     => $res['rows'] ?? [],
                'total'    => (int)($res['total'] ?? 0),
                'page'     => (int)($res['page'] ?? $page),
                'per_page' => (int)($res['per_page'] ?? $perPage)
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            error_log('listar eventos terrestres MF: ' . $e->getMessage());
            http_response_code(500);

            echo json_encode([
                'status'   => 'error',
                'data'     => [],
                'total'    => 0,
                'page'     => $page,
                'per_page' => $perPage,
                'msg'      => 'No fue posible obtener el listado.'
            ], JSON_UNESCAPED_UNICODE);
        }

        die();
    }


    /* =============================================================
       COLUMNAS DINÁMICAS
       Catálogo de tipos de evento terrestres.
       Respuesta:
       {
         ok: true,
         count: 0,
         columns: [{id, nombre, key}]
       }
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
                'error' => 'No fue posible obtener las columnas de eventos terrestres.'
            ], JSON_UNESCAPED_UNICODE);
        }

        die();
    }


    /* =============================================================
       NUEVO ENDPOINT PRINCIPAL - GUARDAR CELDA TIPO EXCEL

       POST:
       - operacion_ferro_id
       - contenedor_fisico_id
       - tipo_evento_id
       - fecha
       - comentario opcional

       Comportamiento:
       - Si no existe evento: inserta.
       - Si ya existe: actualiza.
       - Si fecha viene vacía: limpia la celda/baja lógica.
       ============================================================= */
    public function guardar_celda()
    {
        header('Content-Type: application/json; charset=UTF-8');

        $data = [
            'operacion_ferro_id'   => (int)($_POST['operacion_ferro_id'] ?? 0),
            'contenedor_fisico_id' => (int)($_POST['contenedor_fisico_id'] ?? 0),
            'tipo_evento_id'       => (int)($_POST['tipo_evento_id'] ?? 0),
            'fecha'                => trim((string)($_POST['fecha'] ?? '')),
            'comentario'           => trim((string)($_POST['comentario'] ?? ''))
        ];

        if ($data['operacion_ferro_id'] <= 0) {
            echo json_encode([
                'status' => 'warning',
                'msg'    => 'Falta la operación ferro.'
            ], JSON_UNESCAPED_UNICODE);
            die();
        }

        if ($data['contenedor_fisico_id'] <= 0) {
            echo json_encode([
                'status' => 'warning',
                'msg'    => 'Falta el ferro/caja de la celda.'
            ], JSON_UNESCAPED_UNICODE);
            die();
        }

        if ($data['tipo_evento_id'] <= 0) {
            echo json_encode([
                'status' => 'warning',
                'msg'    => 'Falta el tipo de evento terrestre.'
            ], JSON_UNESCAPED_UNICODE);
            die();
        }

        try {
            $usuarioId = (int)($_SESSION['id_usuario'] ?? 0);

            $res = $this->model->guardarCeldaEvento($data, $usuarioId);

            if (!is_array($res)) {
                echo json_encode([
                    'status'    => 'error',
                    'msg'       => 'Respuesta inválida del modelo.',
                    'accion'    => 'error',
                    'id_evento' => null,
                    'fecha'     => $data['fecha']
                ], JSON_UNESCAPED_UNICODE);
                die();
            }

            $ok     = (bool)($res['ok'] ?? false);
            $accion = (string)($res['accion'] ?? 'error');

            if ($ok) {
                $tipoLog = 'actualizacion';

                if ($accion === 'insertado') {
                    $tipoLog = 'creacion';
                } elseif ($accion === 'eliminado') {
                    $tipoLog = 'eliminacion';
                }

                $desc = $this->makeDesc('Celda de evento terrestre guardada', [
                    'accion'              => $accion,
                    'id_evento'           => $res['id_evento'] ?? '',
                    'op_ferro'            => $data['operacion_ferro_id'],
                    'contenedor_fisico'   => $data['contenedor_fisico_id'],
                    'tipo_evento'         => $data['tipo_evento_id'],
                    'fecha'               => $res['fecha'] ?? $data['fecha']
                ]);

                /*
                  Nota:
                  La bitácora OperacionesLogModel trabaja normalmente con id_operacion.
                  En el flujo viejo se enviaba operacion_ferro_id.
                  Para no romper tu lógica actual, mantenemos el mismo comportamiento
                  que ya venías usando en registrar/actualizar.
                */
                $this->logOp($data['operacion_ferro_id'], $tipoLog, $desc);

                echo json_encode([
                    'status'    => 'success',
                    'msg'       => (string)($res['msg'] ?? 'Celda guardada correctamente.'),
                    'accion'    => $accion,
                    'id_evento' => $res['id_evento'] ?? null,
                    'fecha'     => (string)($res['fecha'] ?? $data['fecha'])
                ], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode([
                    'status'    => 'error',
                    'msg'       => (string)($res['msg'] ?? 'No fue posible guardar la celda.'),
                    'accion'    => $accion,
                    'id_evento' => $res['id_evento'] ?? null,
                    'fecha'     => (string)($res['fecha'] ?? $data['fecha'])
                ], JSON_UNESCAPED_UNICODE);
            }
        } catch (\Throwable $e) {
            error_log('guardar_celda evento terrestre MF: ' . $e->getMessage());
            http_response_code(500);

            echo json_encode([
                'status'    => 'error',
                'msg'       => 'Error interno al guardar la celda.',
                'accion'    => 'error',
                'id_evento' => null,
                'fecha'     => $data['fecha']
            ], JSON_UNESCAPED_UNICODE);
        }

        die();
    }


    /* =============================================================
       ELIMINAR EVENTO POR ID
       Se conserva por compatibilidad y para acciones futuras.
       POST:
       - id_evento
       ============================================================= */
    public function eliminar()
    {
        header('Content-Type: application/json; charset=UTF-8');

        $idEvento = (int)($_POST['id_evento'] ?? 0);

        if ($idEvento <= 0) {
            echo json_encode([
                'status' => 'warning',
                'msg'    => 'Falta id_evento.'
            ], JSON_UNESCAPED_UNICODE);
            die();
        }

        try {
            $ok = $this->model->eliminarEventoPorId($idEvento);

            if ($ok) {
                echo json_encode([
                    'status' => 'success',
                    'msg'    => 'Evento eliminado correctamente.'
                ], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'msg'    => 'No se pudo eliminar el evento.'
                ], JSON_UNESCAPED_UNICODE);
            }
        } catch (\Throwable $e) {
            error_log('eliminar evento terrestre MF: ' . $e->getMessage());
            http_response_code(500);

            echo json_encode([
                'status' => 'error',
                'msg'    => 'Error interno al eliminar el evento.'
            ], JSON_UNESCAPED_UNICODE);
        }

        die();
    }


    /* =============================================================
       SUGERIR OPERACIONES
       Se conserva por compatibilidad con filtros/autocomplete.
       GET:
       - term
       - limit
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
            $rows = $this->model->sugerirOperacionesMFoContenedor($term, $limit);

            $out = array_map(function ($r) {
                return [
                    'id'         => (int)($r['id'] ?? 0),
                    'label'      => (string)($r['label'] ?? ''),
                    'ferro'      => (string)($r['ferro'] ?? ''),
                    'contenedor' => (string)($r['contenedor'] ?? '')
                ];
            }, is_array($rows) ? $rows : []);

            echo json_encode($out, JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            error_log('sugerir_operaciones MF/Contenedor: ' . $e->getMessage());
            echo json_encode([], JSON_UNESCAPED_UNICODE);
        }

        die();
    }


    /* =============================================================
       BUSCAR FERROS / CAJAS DE UNA OPERACIÓN FERRO
       Se conserva por compatibilidad.
       GET:
       - operacion_id
       - term
       - limit
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

            $out = array_map(function ($r) {
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
       OBTENER FERRO PRINCIPAL
       Se conserva por compatibilidad.
       GET:
       - operacion_id
       ============================================================= */
    public function ferro_de_operacion()
    {
        header('Content-Type: application/json; charset=UTF-8');

        $opId = isset($_GET['operacion_id']) ? (int)$_GET['operacion_id'] : 0;

        if ($opId <= 0) {
            echo json_encode(null, JSON_UNESCAPED_UNICODE);
            die();
        }

        try {
            $row = $this->model->getFerroDeOperacion($opId);
            echo json_encode($row ?: null, JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            error_log('ferro_de_operacion MF: ' . $e->getMessage());
            echo json_encode(null, JSON_UNESCAPED_UNICODE);
        }

        die();
    }


    /* =============================================================
       CATÁLOGO SIMPLE DE TIPOS DE EVENTO TERRESTRES
       Se conserva por compatibilidad.
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
            error_log('tipos_evento ferro: ' . $e->getMessage());
            echo json_encode([], JSON_UNESCAPED_UNICODE);
        }

        die();
    }


    /* =============================================================
       GUARDAR OBSERVACIÓN POR RENGLÓN
       POST:
       - operacion_id
       - operacion_ferro_id
       - contenedor_fisico_id
       - observacion
       ============================================================= */
    public function guardar_observacion_renglon()
    {
        header('Content-Type: application/json; charset=UTF-8');

        $data = [
            'operacion_id'         => (int)($_POST['operacion_id'] ?? 0),
            'operacion_ferro_id'   => (int)($_POST['operacion_ferro_id'] ?? 0),
            'contenedor_fisico_id' => (int)($_POST['contenedor_fisico_id'] ?? 0),
            'observacion'          => trim((string)($_POST['observacion'] ?? ''))
        ];

        if ($data['operacion_id'] <= 0) {
            echo json_encode([
                'status' => 'warning',
                'msg'    => 'Falta la operación marítima.'
            ], JSON_UNESCAPED_UNICODE);
            die();
        }

        if ($data['operacion_ferro_id'] <= 0) {
            echo json_encode([
                'status' => 'warning',
                'msg'    => 'Falta la operación ferro.'
            ], JSON_UNESCAPED_UNICODE);
            die();
        }

        if ($data['contenedor_fisico_id'] <= 0) {
            echo json_encode([
                'status' => 'warning',
                'msg'    => 'Falta el ferro/caja del renglón.'
            ], JSON_UNESCAPED_UNICODE);
            die();
        }

        try {
            $usuarioId = (int)($_SESSION['id_usuario'] ?? 0);

            $ok = $this->model->guardarObservacionRenglon($data, $usuarioId);

            if ($ok) {
                $desc = $this->makeDesc('Observación de renglón actualizada en eventos terrestres', [
                    'operacion_id'       => $data['operacion_id'],
                    'op_ferro'           => $data['operacion_ferro_id'],
                    'contenedor_fisico'  => $data['contenedor_fisico_id']
                ]);

                $this->logOp($data['operacion_id'], 'actualizacion', $desc);

                echo json_encode([
                    'status'      => 'success',
                    'msg'         => $data['observacion'] === ''
                        ? 'Observación eliminada.'
                        : 'Observación guardada correctamente.',
                    'observacion' => $data['observacion']
                ], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'msg'    => 'No fue posible guardar la observación. Valida que el renglón exista y esté activo.'
                ], JSON_UNESCAPED_UNICODE);
            }
        } catch (\Throwable $e) {
            error_log('guardar_observacion_renglon FER: ' . $e->getMessage());
            http_response_code(500);

            echo json_encode([
                'status' => 'error',
                'msg'    => 'Error interno al guardar la observación.'
            ], JSON_UNESCAPED_UNICODE);
        }

        die();
    }


    /* =============================================================
       OBTENER OBSERVACIÓN POR RENGLÓN
       GET:
       - operacion_id
       - operacion_ferro_id
       - contenedor_fisico_id
       ============================================================= */
    public function obtener_observacion_renglon()
    {
        header('Content-Type: application/json; charset=UTF-8');

        $operacionId = (int)($_GET['operacion_id'] ?? 0);
        $opFerroId   = (int)($_GET['operacion_ferro_id'] ?? 0);
        $ferroId     = (int)($_GET['contenedor_fisico_id'] ?? 0);

        if ($operacionId <= 0 || $opFerroId <= 0 || $ferroId <= 0) {
            echo json_encode([
                'status'      => 'warning',
                'msg'         => 'Faltan datos del renglón.',
                'data'        => null,
                'observacion' => ''
            ], JSON_UNESCAPED_UNICODE);
            die();
        }

        try {
            $row = $this->model->obtenerObservacionRenglon(
                $operacionId,
                $opFerroId,
                $ferroId
            );

            echo json_encode([
                'status'      => 'success',
                'data'        => $row,
                'observacion' => $row ? (string)($row['observacion'] ?? '') : ''
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            error_log('obtener_observacion_renglon FER: ' . $e->getMessage());
            http_response_code(500);

            echo json_encode([
                'status'      => 'error',
                'msg'         => 'Error interno al obtener la observación.',
                'data'        => null,
                'observacion' => ''
            ], JSON_UNESCAPED_UNICODE);
        }

        die();
    }


    /* =============================================================
       HELPERS
       ============================================================= */
    private function logOp(int $operacionId, string $accion, string $descripcion): void
    {
        try {
            $usuarioId = (int)($_SESSION['id_usuario'] ?? 0);

            $id = $this->opLog->crear(
                $operacionId,
                $usuarioId,
                $accion,
                $descripcion
            );

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
            $kv[] = "{$k}={$v}";
        }

        return $base . ' (' . implode(', ', $kv) . ')';
    }
}
