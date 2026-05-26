<?php

require_once "Models/OperacionesLogModel.php";

class Operaciones_maritimo_ferro_eventos_mar extends Controller
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
       LISTAR EVENTOS MARÍTIMOS - MARÍTIMO FERRO
       GET:
       - page
       - per_page
       - op_id | operacion_id | mar_id
       - cont_id | cont_maritimo_operacion_id
       - cliente_id
       - contenedor | contenedor_maritimo
       - q
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

        $contId = null;

        if (isset($_GET['cont_id']) && $_GET['cont_id'] !== '') {
            $tmp = (int)$_GET['cont_id'];
            $contId = $tmp > 0 ? $tmp : null;
        }

        if ($contId === null && isset($_GET['cont_maritimo_operacion_id']) && $_GET['cont_maritimo_operacion_id'] !== '') {
            $tmp = (int)$_GET['cont_maritimo_operacion_id'];
            $contId = $tmp > 0 ? $tmp : null;
        }

        $clienteId = null;

        if (isset($_GET['cliente_id']) && $_GET['cliente_id'] !== '') {
            $tmp = (int)$_GET['cliente_id'];
            $clienteId = $tmp > 0 ? $tmp : null;
        }

        $contenedor = '';

        if (isset($_GET['contenedor'])) {
            $contenedor = trim((string)$_GET['contenedor']);
        } elseif (isset($_GET['contenedor_maritimo'])) {
            $contenedor = trim((string)$_GET['contenedor_maritimo']);
        }

        $q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';

        try {
            $res = $this->model->listarEventosMFPaginado(
                $page,
                $perPage,
                $opId,
                $contId,
                $clienteId,
                $contenedor,
                $q
            );

            echo json_encode([
                'status'   => 'success',
                'data'     => $res['rows'] ?? [],
                'total'    => (int)($res['total'] ?? 0),
                'page'     => (int)($res['page'] ?? $page),
                'per_page' => (int)($res['per_page'] ?? $perPage)
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            error_log('listar eventos marítimos MF: ' . $e->getMessage());
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
       Catálogo de tipos de evento marítimos.
       Respuesta:
       {
         ok: true,
         count: 0,
         columns: [{id, nombre, key}]
       }
       ============================================================= */
    public function eventos_maritimos_columnas()
    {
        header('Content-Type: application/json; charset=UTF-8');

        try {
            $rows = $this->model->listarEventosMaritimosParaColumnas();

            $slug = function (string $s): string {
                $s = mb_strtolower($s, 'UTF-8');
                $s = preg_replace('/[^a-z0-9]+/u', '_', $s);
                return trim($s, '_');
            };

            $out = array_map(function ($r) use ($slug) {
                $id  = (int)($r['id'] ?? $r['id_tipo_evento'] ?? 0);
                $nom = (string)($r['nombre'] ?? '');

                return [
                    'id'     => $id,
                    'nombre' => $nom,
                    'key'    => (string)($r['key'] ?? $slug($nom))
                ];
            }, is_array($rows) ? $rows : []);

            echo json_encode([
                'ok'      => true,
                'count'   => count($out),
                'columns' => $out
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            error_log('eventos_maritimos_columnas: ' . $e->getMessage());
            http_response_code(500);

            echo json_encode([
                'ok'    => false,
                'error' => 'No fue posible obtener las columnas de eventos marítimos.'
            ], JSON_UNESCAPED_UNICODE);
        }

        die();
    }


    /* =============================================================
       NUEVO ENDPOINT PRINCIPAL - GUARDAR CELDA TIPO EXCEL

       POST:
       - operacion_id
       - cont_maritimo_operacion_id
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
            'operacion_id'               => (int)($_POST['operacion_id'] ?? 0),
            'cont_maritimo_operacion_id' => (int)($_POST['cont_maritimo_operacion_id'] ?? 0),
            'tipo_evento_id'             => (int)($_POST['tipo_evento_id'] ?? 0),
            'fecha'                      => trim((string)($_POST['fecha'] ?? '')),
            'comentario'                 => trim((string)($_POST['comentario'] ?? ''))
        ];

        if ($data['operacion_id'] <= 0) {
            echo json_encode([
                'status' => 'warning',
                'msg'    => 'Falta la operación marítima.'
            ], JSON_UNESCAPED_UNICODE);
            die();
        }

        if ($data['cont_maritimo_operacion_id'] <= 0) {
            echo json_encode([
                'status' => 'warning',
                'msg'    => 'Falta el contenedor marítimo de la celda.'
            ], JSON_UNESCAPED_UNICODE);
            die();
        }

        if ($data['tipo_evento_id'] <= 0) {
            echo json_encode([
                'status' => 'warning',
                'msg'    => 'Falta el tipo de evento marítimo.'
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

                $desc = $this->makeDesc('Celda de evento marítimo guardada', [
                    'accion'                    => $accion,
                    'id_evento'                 => $res['id_evento'] ?? '',
                    'operacion_id'              => $data['operacion_id'],
                    'cont_maritimo_operacion'   => $data['cont_maritimo_operacion_id'],
                    'tipo_evento'               => $data['tipo_evento_id'],
                    'fecha'                     => $res['fecha'] ?? $data['fecha']
                ]);

                $this->logOp($data['operacion_id'], $tipoLog, $desc);

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
            error_log('guardar_celda evento marítimo MF: ' . $e->getMessage());
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
            error_log('eliminar evento marítimo MF: ' . $e->getMessage());
            http_response_code(500);

            echo json_encode([
                'status' => 'error',
                'msg'    => 'Error interno al eliminar el evento.'
            ], JSON_UNESCAPED_UNICODE);
        }

        die();
    }


    /* =============================================================
       SUGERIR OPERACIONES MF
       GET:
       - term
       - limit
       ============================================================= */
    public function sugerir_operaciones()
    {
        header('Content-Type: application/json; charset=UTF-8');

        $term  = isset($_GET['term']) ? trim((string)$_GET['term']) : '';
        $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 10;

        if ($term === '') {
            echo json_encode([], JSON_UNESCAPED_UNICODE);
            die();
        }

        try {
            $rows = $this->model->sugerirOperacionesMF($term, $limit);

            $out = array_map(function ($r) {
                $contenedores = isset($r['contenedores']) ? trim((string)$r['contenedores']) : '';
                $fallback     = isset($r['maritimos']) ? ((int)$r['maritimos'] . ' contenedor(es)') : '';

                return [
                    'id'    => (int)($r['id'] ?? 0),
                    'label' => (string)($r['label'] ?? ''),
                    'meta'  => $contenedores !== '' ? $contenedores : $fallback,
                ];
            }, is_array($rows) ? $rows : []);

            echo json_encode($out, JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            error_log('sugerir_operaciones MF eventos marítimos: ' . $e->getMessage());
            echo json_encode([], JSON_UNESCAPED_UNICODE);
        }

        die();
    }


    /* =============================================================
       AUTOCOMPLETE: OPERACIONES MF
       Conservado por compatibilidad con JS anterior.
       GET:
       - term
       - limit
       ============================================================= */
    public function buscar_operaciones()
    {
        header('Content-Type: application/json; charset=UTF-8');

        $term  = isset($_GET['term']) ? trim((string)$_GET['term']) : '';
        $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 10;

        if ($term === '') {
            echo json_encode([], JSON_UNESCAPED_UNICODE);
            die();
        }

        try {
            $rows = $this->model->buscarOperacionesMaritimoFerro($term, $limit);

            $out = array_map(function ($r) {
                $contenedores = isset($r['contenedores']) ? trim((string)$r['contenedores']) : '';
                $fallback     = isset($r['maritimos']) ? ((int)$r['maritimos'] . ' contenedor(es)') : '';

                return [
                    'id'    => (int)($r['id'] ?? 0),
                    'label' => (string)($r['label'] ?? ''),
                    'meta'  => $contenedores !== '' ? $contenedores : $fallback,
                ];
            }, is_array($rows) ? $rows : []);

            echo json_encode($out, JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            error_log('buscar_operaciones MF eventos marítimos: ' . $e->getMessage());
            echo json_encode([], JSON_UNESCAPED_UNICODE);
        }

        die();
    }


    /* =============================================================
       BUSCAR CONTENEDORES MARÍTIMOS DE UNA OPERACIÓN MF
       GET:
       - operacion_id
       - term
       - limit

       Respuesta:
       [{id,label,tipo}]
       id = contenedores_maritimos_operacion.id
       ============================================================= */
    public function buscar_contenedores()
    {
        header('Content-Type: application/json; charset=UTF-8');

        $operacionId = isset($_GET['operacion_id']) ? (int)$_GET['operacion_id'] : 0;
        $term        = isset($_GET['term']) ? trim((string)$_GET['term']) : '';
        $limit       = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 15;

        if ($operacionId <= 0) {
            echo json_encode([], JSON_UNESCAPED_UNICODE);
            die();
        }

        try {
            $rows = $this->model->buscarContenedoresMarDeOperacion($operacionId, $term, $limit);

            $out = array_map(function ($r) {
                return [
                    'id'    => (int)($r['id'] ?? 0),
                    'label' => (string)($r['label'] ?? ''),
                    'tipo'  => (string)($r['tipo'] ?? 'MARITIMO')
                ];
            }, is_array($rows) ? $rows : []);

            echo json_encode($out, JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            error_log('buscar_contenedores MF eventos marítimos: ' . $e->getMessage());
            echo json_encode([], JSON_UNESCAPED_UNICODE);
        }

        die();
    }


    /* =============================================================
       OBTENER CONTENEDOR MARÍTIMO PRINCIPAL DE LA OPERACIÓN
       GET:
       - operacion_id
       ============================================================= */
    public function contenedor_maritimo_de_operacion()
    {
        header('Content-Type: application/json; charset=UTF-8');

        $opId = isset($_GET['operacion_id']) ? (int)$_GET['operacion_id'] : 0;

        if ($opId <= 0) {
            echo json_encode(null, JSON_UNESCAPED_UNICODE);
            die();
        }

        try {
            $row = $this->model->getContenedorMaritimoDeOperacion($opId);
            echo json_encode($row ?: null, JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            error_log('contenedor_maritimo_de_operacion MF: ' . $e->getMessage());
            echo json_encode(null, JSON_UNESCAPED_UNICODE);
        }

        die();
    }


    /* =============================================================
       CATÁLOGO SIMPLE DE TIPOS DE EVENTO MARÍTIMOS
       GET -> [{id, nombre}]
       ============================================================= */
    public function tipos_evento()
    {
        header('Content-Type: application/json; charset=UTF-8');

        try {
            $rows = $this->model->listarTiposEventoMaritimo();

            $out = array_map(function ($r) {
                return [
                    'id'     => (int)($r['id_tipo_evento'] ?? $r['id'] ?? 0),
                    'nombre' => (string)($r['nombre'] ?? '')
                ];
            }, is_array($rows) ? $rows : []);

            echo json_encode($out, JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            error_log('tipos_evento marítimos: ' . $e->getMessage());
            echo json_encode([], JSON_UNESCAPED_UNICODE);
        }

        die();
    }


    /* =============================================================
       OBTENER EVENTO POR CLAVE
       Se conserva por compatibilidad con JS viejo/modal.
       GET:
       - operacion_id
       - cont_maritimo_operacion_id
       - tipo_evento_id
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

        try {
            $row = $this->model->obtenerEventoPorClave($opId, $cmoId, $evtId);
            echo json_encode($row ?: null, JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            error_log('obtener_por_clave evento marítimo MF: ' . $e->getMessage());
            echo json_encode(null, JSON_UNESCAPED_UNICODE);
        }

        die();
    }


    /* =============================================================
       COMPATIBILIDAD CON FLUJO ANTERIOR - REGISTRAR
       Internamente usa guardarCeldaEvento().
       POST:
       - operacion_id
       - cont_maritimo_operacion_id
       - tipo_evento_id
       - fecha
       - comentario
       ============================================================= */
    public function registrar()
    {
        header('Content-Type: application/json; charset=UTF-8');

        $data = [
            'operacion_id'               => (int)($_POST['operacion_id'] ?? 0),
            'cont_maritimo_operacion_id' => (int)($_POST['cont_maritimo_operacion_id'] ?? 0),
            'tipo_evento_id'             => (int)($_POST['tipo_evento_id'] ?? 0),
            'fecha'                      => trim((string)($_POST['fecha'] ?? '')),
            'comentario'                 => trim((string)($_POST['comentario'] ?? ''))
        ];

        if ($data['operacion_id'] <= 0) {
            echo json_encode([
                'status' => 'warning',
                'msg'    => 'Selecciona una operación MF.'
            ], JSON_UNESCAPED_UNICODE);
            die();
        }

        if ($data['cont_maritimo_operacion_id'] <= 0) {
            echo json_encode([
                'status' => 'warning',
                'msg'    => 'No hay contenedor marítimo ligado a la operación.'
            ], JSON_UNESCAPED_UNICODE);
            die();
        }

        if ($data['tipo_evento_id'] <= 0) {
            echo json_encode([
                'status' => 'warning',
                'msg'    => 'Selecciona un tipo de evento marítimo.'
            ], JSON_UNESCAPED_UNICODE);
            die();
        }

        if ($data['fecha'] === '') {
            echo json_encode([
                'status' => 'warning',
                'msg'    => 'Indica la fecha del evento.'
            ], JSON_UNESCAPED_UNICODE);
            die();
        }

        try {
            $usuarioId = (int)($_SESSION['id_usuario'] ?? 0);
            $res = $this->model->guardarCeldaEvento($data, $usuarioId);

            if (!is_array($res) || empty($res['ok'])) {
                echo json_encode([
                    'status' => 'error',
                    'msg'    => is_array($res)
                        ? (string)($res['msg'] ?? 'No fue posible registrar el evento.')
                        : 'No fue posible registrar el evento.'
                ], JSON_UNESCAPED_UNICODE);
                die();
            }

            $accion = (string)($res['accion'] ?? 'insertado');
            $tipoLog = $accion === 'insertado' ? 'creacion' : 'actualizacion';

            $desc = $this->makeDesc('Evento marítimo registrado', [
                'accion'       => $accion,
                'id_evento'    => $res['id_evento'] ?? '',
                'operacion_id' => $data['operacion_id'],
                'cmo'          => $data['cont_maritimo_operacion_id'],
                'tipo_evt_id'  => $data['tipo_evento_id'],
                'fecha'        => $data['fecha']
            ]);

            $this->logOp($data['operacion_id'], $tipoLog, $desc);

            echo json_encode([
                'status'    => 'success',
                'msg'       => (string)($res['msg'] ?? 'Evento registrado correctamente.'),
                'id'        => $res['id_evento'] ?? null,
                'id_evento' => $res['id_evento'] ?? null,
                'accion'    => $accion,
                'fecha'     => (string)($res['fecha'] ?? $data['fecha'])
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            error_log('registrar evento marítimo MF: ' . $e->getMessage());
            http_response_code(500);

            echo json_encode([
                'status' => 'error',
                'msg'    => 'Error interno al registrar el evento.'
            ], JSON_UNESCAPED_UNICODE);
        }

        die();
    }


    /* =============================================================
       COMPATIBILIDAD CON FLUJO ANTERIOR - ACTUALIZAR
       Internamente usa guardarCeldaEvento().
       POST:
       - id_evento
       - operacion_id
       - cont_maritimo_operacion_id
       - tipo_evento_id
       - fecha
       - comentario
       ============================================================= */
    public function actualizar()
    {
        header('Content-Type: application/json; charset=UTF-8');

        $data = [
            'id_evento'                  => (int)($_POST['id_evento'] ?? 0),
            'operacion_id'               => (int)($_POST['operacion_id'] ?? 0),
            'cont_maritimo_operacion_id' => (int)($_POST['cont_maritimo_operacion_id'] ?? 0),
            'tipo_evento_id'             => (int)($_POST['tipo_evento_id'] ?? 0),
            'fecha'                      => trim((string)($_POST['fecha'] ?? '')),
            'comentario'                 => trim((string)($_POST['comentario'] ?? ''))
        ];

        if ($data['id_evento'] <= 0) {
            echo json_encode([
                'status' => 'warning',
                'msg'    => 'Falta id_evento.'
            ], JSON_UNESCAPED_UNICODE);
            die();
        }

        if ($data['operacion_id'] <= 0) {
            echo json_encode([
                'status' => 'warning',
                'msg'    => 'Selecciona una operación MF.'
            ], JSON_UNESCAPED_UNICODE);
            die();
        }

        if ($data['cont_maritimo_operacion_id'] <= 0) {
            echo json_encode([
                'status' => 'warning',
                'msg'    => 'No hay contenedor marítimo ligado a la operación.'
            ], JSON_UNESCAPED_UNICODE);
            die();
        }

        if ($data['tipo_evento_id'] <= 0) {
            echo json_encode([
                'status' => 'warning',
                'msg'    => 'Selecciona un tipo de evento marítimo.'
            ], JSON_UNESCAPED_UNICODE);
            die();
        }

        if ($data['fecha'] === '') {
            echo json_encode([
                'status' => 'warning',
                'msg'    => 'Indica la fecha del evento.'
            ], JSON_UNESCAPED_UNICODE);
            die();
        }

        try {
            $usuarioId = (int)($_SESSION['id_usuario'] ?? 0);
            $res = $this->model->guardarCeldaEvento($data, $usuarioId);

            if (!is_array($res) || empty($res['ok'])) {
                echo json_encode([
                    'status' => 'error',
                    'msg'    => is_array($res)
                        ? (string)($res['msg'] ?? 'No fue posible actualizar el evento.')
                        : 'No fue posible actualizar el evento.'
                ], JSON_UNESCAPED_UNICODE);
                die();
            }

            $desc = $this->makeDesc('Evento marítimo actualizado', [
                'id_evento'    => $res['id_evento'] ?? $data['id_evento'],
                'operacion_id' => $data['operacion_id'],
                'cmo'          => $data['cont_maritimo_operacion_id'],
                'tipo_evt_id'  => $data['tipo_evento_id'],
                'fecha'        => $data['fecha']
            ]);

            $this->logOp($data['operacion_id'], 'actualizacion', $desc);

            echo json_encode([
                'status'    => 'success',
                'msg'       => (string)($res['msg'] ?? 'Evento actualizado correctamente.'),
                'id_evento' => $res['id_evento'] ?? $data['id_evento'],
                'accion'    => $res['accion'] ?? 'actualizado',
                'fecha'     => (string)($res['fecha'] ?? $data['fecha'])
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            error_log('actualizar evento marítimo MF: ' . $e->getMessage());
            http_response_code(500);

            echo json_encode([
                'status' => 'error',
                'msg'    => 'Error interno al actualizar el evento.'
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
