<?php

require_once "Models/BitacoraOpPartidaModel.php";
require_once "Models/OperacionesLogModel.php";

class Operaciones_por_partida_eventos extends Controller
{
    /** @var OperacionesLogModel */
    private $opLog;

    /** @var BitacoraOpPartidaModel */
    protected $bitacoraOpPartida;

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
        $this->bitacoraOpPartida = new BitacoraOpPartidaModel();

        header_remove('X-Powered-By');

        // Roles internos permitidos:
        // 1 = admin, 2 = operador, 11 = supervisor, 15 = revisor
        $this->requireRoles([1, 2, 11, 15]);
    }


    /* =============================================================
       HELPERS GENERALES
       ============================================================= */

    private function jsonResponse(array $payload, int $httpCode = 200): void
    {
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=UTF-8');

        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        die();
    }


    private function methodNotAllowed(): void
    {
        $this->jsonResponse([
            'status' => 'error',
            'msg'    => 'Método no permitido.'
        ], 405);
    }


    private function getIntFromRequest(array $source, array $keys): ?int
    {
        foreach ($keys as $key) {
            if (isset($source[$key]) && $source[$key] !== '') {
                $value = (int)$source[$key];
                return $value > 0 ? $value : null;
            }
        }

        return null;
    }


    private function makeDesc(string $titulo, array $data = []): string
    {
        $parts = [];

        foreach ($data as $k => $v) {
            if ($v === null || $v === '') {
                continue;
            }

            $parts[] = $k . '=' . $v;
        }

        return $titulo . (empty($parts) ? '' : ' (' . implode(', ', $parts) . ')');
    }


    private function registrarBitacoraPartida(
        string $modulo,
        string $accion,
        string $entidad,
        ?int $entidadId = null,
        ?string $detalle = null
    ): bool {
        try {
            $usuarioId = $_SESSION['id_usuario'] ?? null;

            return (bool)$this->bitacoraOpPartida->crear(
                $usuarioId,
                $modulo,
                $accion,
                $entidad,
                $entidadId,
                $detalle
            );
        } catch (Exception $e) {
            error_log('[BITACORA OP PARTIDA EVENTOS] ' . $e->getMessage());
            return false;
        }
    }


    private function logOpPartida(
        ?int $envioId,
        string $accion,
        string $detalle
    ): void {
        try {
            $this->registrarBitacoraPartida(
                'op_partida_eventos',
                $accion,
                'eventos_operacion_partida_ferro',
                $envioId,
                $detalle
            );
        } catch (Throwable $e) {
            error_log('[LOG OP PARTIDA EVENTOS] ' . $e->getMessage());
        }
    }


    private function slug(string $s): string
    {
        $s = mb_strtolower($s, 'UTF-8');
        $s = preg_replace('/[^a-z0-9]+/u', '_', $s);
        return trim($s, '_');
    }


    private function normalizarRowsListado(array $result): array
    {
        /*
          El modelo nuevo regresa:
          [
            rows,
            total,
            page,
            per_page
          ]

          El JS anterior puede esperar "data".
          Por eso regresamos ambos: rows y data.
        */
        $rows = [];

        if (isset($result['rows']) && is_array($result['rows'])) {
            $rows = $result['rows'];
        } elseif (isset($result['data']) && is_array($result['data'])) {
            $rows = $result['data'];
        }

        return $rows;
    }


    /* =============================================================
       VISTA
       ============================================================= */

    public function index()
    {
        $data['title'] = 'Eventos operaciones por partida';

        /*
          Tu vista usa:
          $data['transportistas']
          $data['ciudades']

          Si después agregas estos métodos al modelo, los toma.
          Si no existen, no rompe la vista.
        */
        $data['transportistas'] = [];
        $data['ciudades'] = [];

        try {
            if (method_exists($this->model, 'listarTransportistas')) {
                $data['transportistas'] = $this->model->listarTransportistas();
            }

            if (method_exists($this->model, 'listarCiudades')) {
                $data['ciudades'] = $this->model->listarCiudades();
            }
        } catch (Throwable $e) {
            error_log('[OP PARTIDA EVENTOS INDEX] ' . $e->getMessage());
        }

        $this->views->getView($this, "index", $data);
    }


    /* =============================================================
       LISTAR PAGINADO
       GET:
       - page
       - per_page
       - op_id / envio_partida_id / operacion_ferro_id
       - factura
       - ferro
       - transportista_id
       - destino_id

       Respuesta compatible:
       {
         status,
         total,
         page,
         per_page,
         rows,
         data
       }
       ============================================================= */

    public function listar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->methodNotAllowed();
        }

        try {
            $page = isset($_GET['page'])
                ? max(1, (int)$_GET['page'])
                : 1;

            $perPage = isset($_GET['per_page'])
                ? min(1000000, max(1, (int)$_GET['per_page']))
                : 10;

            $opId = $this->getIntFromRequest($_GET, [
                'op_id',
                'envio_partida_id',
                'operacion_ferro_id'
            ]);

            $factura = isset($_GET['factura'])
                ? trim((string)$_GET['factura'])
                : '';

            $ferro = isset($_GET['ferro'])
                ? trim((string)$_GET['ferro'])
                : '';

            $transportistaId = $this->getIntFromRequest($_GET, [
                'transportista_id'
            ]);

            $destinoId = $this->getIntFromRequest($_GET, [
                'destino_id'
            ]);

            $result = $this->model->listarPaginado(
                $page,
                $perPage,
                $opId,
                $factura,
                $ferro,
                $transportistaId,
                $destinoId
            );

            $rows = $this->normalizarRowsListado($result);

            $this->jsonResponse([
                'status'   => 'success',
                'total'    => (int)($result['total'] ?? 0),
                'page'     => (int)($result['page'] ?? $page),
                'per_page' => (int)($result['per_page'] ?? $perPage),

                // Compatibilidad doble
                'rows'     => $rows,
                'data'     => $rows
            ]);
        } catch (Throwable $e) {
            error_log('[OP PARTIDA EVENTOS LISTAR] ' . $e->getMessage());

            $this->jsonResponse([
                'status' => 'error',
                'msg'    => 'No fue posible listar los eventos por partida.',
                'total'  => 0,
                'rows'   => [],
                'data'   => []
            ], 500);
        }
    }


    /* =============================================================
       COLUMNAS DINÁMICAS
       Catálogo de tipos de evento terrestre.
       Lo mantenemos con el mismo nombre que usa tu JS:
       Operaciones_por_partida_eventos/eventos_ferro_columnas
       ============================================================= */

    public function eventos_ferro_columnas()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->methodNotAllowed();
        }

        try {
            $rows = $this->model->listarTiposEventoTerrestre();

            $out = array_map(function ($r) {
                $id  = (int)($r['id_tipo_evento'] ?? $r['id'] ?? 0);
                $nom = (string)($r['nombre'] ?? '');

                return [
                    'id'     => $id,
                    'nombre' => $nom,
                    'key'    => $this->slug($nom)
                ];
            }, is_array($rows) ? $rows : []);

            $this->jsonResponse([
                'status'  => 'success',
                'ok'      => true,
                'count'   => count($out),
                'columns' => $out,
                'data'    => $out
            ]);
        } catch (Throwable $e) {
            error_log('[OP PARTIDA EVENTOS COLUMNAS] ' . $e->getMessage());

            $this->jsonResponse([
                'status'  => 'error',
                'ok'      => false,
                'msg'     => 'No fue posible obtener las columnas de eventos.',
                'columns' => [],
                'data'    => []
            ], 500);
        }
    }


    public function tipos_evento()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->methodNotAllowed();
        }

        try {
            $rows = $this->model->listarTiposEventoTerrestre();

            $out = array_map(function ($r) {
                return [
                    'id_tipo_evento' => (int)($r['id_tipo_evento'] ?? $r['id'] ?? 0),
                    'id'             => (int)($r['id_tipo_evento'] ?? $r['id'] ?? 0),
                    'nombre'         => (string)($r['nombre'] ?? '')
                ];
            }, is_array($rows) ? $rows : []);

            $this->jsonResponse([
                'status' => 'success',
                'data'   => $out
            ]);
        } catch (Throwable $e) {
            error_log('[OP PARTIDA EVENTOS TIPOS] ' . $e->getMessage());

            $this->jsonResponse([
                'status' => 'error',
                'msg'    => 'No fue posible obtener los tipos de evento.',
                'data'   => []
            ], 500);
        }
    }


    /* =============================================================
       NUEVO ENDPOINT PRINCIPAL - GUARDAR CELDA TIPO EXCEL

       POST:
       - envio_partida_id u operacion_ferro_id
       - contenedor_fisico_id
       - tipo_evento_id
       - fecha
       - comentario opcional

       Comportamiento:
       - Si no existe evento: inserta.
       - Si ya existe: actualiza.
       - Si fecha viene vacía: limpia/baja lógica.
       ============================================================= */

    public function guardar_celda()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->methodNotAllowed();
        }

        $envioId = (int)(
            $_POST['envio_partida_id']
            ?? $_POST['operacion_ferro_id']
            ?? $_POST['op_id']
            ?? 0
        );

        $data = [
            'envio_partida_id'     => $envioId,
            'operacion_ferro_id'   => $envioId, // alias para compatibilidad
            'contenedor_fisico_id' => (int)($_POST['contenedor_fisico_id'] ?? 0),
            'tipo_evento_id'       => (int)($_POST['tipo_evento_id'] ?? 0),
            'fecha'                => trim((string)($_POST['fecha'] ?? '')),
            'comentario'           => trim((string)($_POST['comentario'] ?? ''))
        ];

        if ($data['envio_partida_id'] <= 0) {
            $this->jsonResponse([
                'status' => 'warning',
                'msg'    => 'Falta el envío de operación por partida.'
            ]);
        }

        if ($data['contenedor_fisico_id'] <= 0) {
            $this->jsonResponse([
                'status' => 'warning',
                'msg'    => 'Falta el ferro/caja de la celda.'
            ]);
        }

        if ($data['tipo_evento_id'] <= 0) {
            $this->jsonResponse([
                'status' => 'warning',
                'msg'    => 'Falta el tipo de evento de la celda.'
            ]);
        }

        /*
          OJO:
          La fecha puede venir vacía. Eso significa borrar/limpiar celda.
          No se debe validar como requerida aquí.
        */

        try {
            $usuarioId = (int)($_SESSION['id_usuario'] ?? 0);

            $res = $this->model->guardarCeldaEvento($data, $usuarioId);

            if (!is_array($res)) {
                $this->jsonResponse([
                    'status'    => 'error',
                    'msg'       => 'Respuesta inválida del modelo.',
                    'accion'    => 'error',
                    'id_evento' => null,
                    'fecha'     => $data['fecha']
                ], 500);
            }

            $ok     = (bool)($res['ok'] ?? false);
            $accion = (string)($res['accion'] ?? 'error');

            if ($ok) {
                $tipoLog = 'actualizacion';

                if ($accion === 'insertado') {
                    $tipoLog = 'creacion';
                } elseif ($accion === 'eliminado') {
                    $tipoLog = 'eliminacion';
                } elseif ($accion === 'sin_cambios') {
                    $tipoLog = 'consulta';
                }

                $desc = $this->makeDesc('Celda de evento por partida guardada', [
                    'accion'              => $accion,
                    'id_evento'           => $res['id_evento'] ?? '',
                    'envio_partida_id'    => $data['envio_partida_id'],
                    'contenedor_fisico'   => $data['contenedor_fisico_id'],
                    'tipo_evento'         => $data['tipo_evento_id'],
                    'fecha'               => $res['fecha'] ?? $data['fecha']
                ]);

                $this->logOpPartida($data['envio_partida_id'], $tipoLog, $desc);

                $this->jsonResponse([
                    'status'              => 'success',
                    'msg'                 => (string)($res['msg'] ?? 'Celda guardada correctamente.'),
                    'accion'              => $accion,
                    'id_evento'           => $res['id_evento'] ?? null,
                    'fecha'               => (string)($res['fecha'] ?? $data['fecha']),
                    'envio_partida_id'    => $data['envio_partida_id'],
                    'operacion_ferro_id'  => $data['envio_partida_id'], // alias para JS actual
                    'contenedor_fisico_id' => $data['contenedor_fisico_id'],
                    'tipo_evento_id'      => $data['tipo_evento_id']
                ]);
            }

            $this->jsonResponse([
                'status'    => 'error',
                'msg'       => (string)($res['msg'] ?? 'No fue posible guardar la celda.'),
                'accion'    => $accion,
                'id_evento' => $res['id_evento'] ?? null,
                'fecha'     => (string)($res['fecha'] ?? $data['fecha'])
            ]);
        } catch (Throwable $e) {
            error_log('[OP PARTIDA EVENTOS GUARDAR CELDA] ' . $e->getMessage());

            $this->jsonResponse([
                'status'    => 'error',
                'msg'       => 'Error interno al guardar la celda.',
                'accion'    => 'error',
                'id_evento' => null,
                'fecha'     => $data['fecha']
            ], 500);
        }
    }


    /* =============================================================
       OBTENER POR CLAVE
       Se conserva por compatibilidad con tu JS actual/modal.
       POST o GET:
       - envio_partida_id u operacion_ferro_id
       - contenedor_fisico_id
       - tipo_evento_id
       ============================================================= */

    public function obtener_por_clave()
    {
        if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
            $this->methodNotAllowed();
        }

        $source = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;

        $envioId = (int)(
            $source['envio_partida_id']
            ?? $source['operacion_ferro_id']
            ?? $source['op_id']
            ?? 0
        );

        $ferroId = (int)($source['contenedor_fisico_id'] ?? 0);
        $evtId   = (int)($source['tipo_evento_id'] ?? 0);

        if ($envioId <= 0 || $ferroId <= 0 || $evtId <= 0) {
            $this->jsonResponse([
                'status' => 'warning',
                'msg'    => 'Faltan datos para consultar el evento.',
                'data'   => null
            ]);
        }

        try {
            $row = $this->model->obtenerEventoActivoPorClave(
                $envioId,
                $ferroId,
                $evtId
            );

            $this->jsonResponse([
                'status' => 'success',
                'data'   => $row ?: null
            ]);
        } catch (Throwable $e) {
            error_log('[OP PARTIDA EVENTOS OBTENER CLAVE] ' . $e->getMessage());

            $this->jsonResponse([
                'status' => 'error',
                'msg'    => 'No fue posible consultar el evento.',
                'data'   => null
            ], 500);
        }
    }


    /* =============================================================
       REGISTRAR
       Compatibilidad con JS anterior.
       Internamente usa guardar_celda vía modelo.
       ============================================================= */

    public function registrar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->methodNotAllowed();
        }

        $envioId = (int)(
            $_POST['envio_partida_id']
            ?? $_POST['operacion_ferro_id']
            ?? $_POST['op_id']
            ?? 0
        );

        $data = [
            'envio_partida_id'     => $envioId,
            'operacion_ferro_id'   => $envioId,
            'contenedor_fisico_id' => (int)($_POST['contenedor_fisico_id'] ?? 0),
            'tipo_evento_id'       => (int)($_POST['tipo_evento_id'] ?? 0),
            'fecha'                => trim((string)($_POST['fecha'] ?? '')),
            'comentario'           => trim((string)($_POST['comentario'] ?? ''))
        ];

        if ($data['envio_partida_id'] <= 0) {
            $this->jsonResponse([
                'status' => 'warning',
                'msg'    => 'Falta el envío de operación por partida.'
            ]);
        }

        if ($data['contenedor_fisico_id'] <= 0) {
            $this->jsonResponse([
                'status' => 'warning',
                'msg'    => 'Falta el ferro/caja.'
            ]);
        }

        if ($data['tipo_evento_id'] <= 0) {
            $this->jsonResponse([
                'status' => 'warning',
                'msg'    => 'Falta el tipo de evento.'
            ]);
        }

        if ($data['fecha'] === '') {
            $this->jsonResponse([
                'status' => 'warning',
                'msg'    => 'Debes capturar la fecha del evento.'
            ]);
        }

        try {
            $usuarioId = (int)($_SESSION['id_usuario'] ?? 0);
            $res = $this->model->guardarCeldaEvento($data, $usuarioId);

            if (!empty($res['ok'])) {
                $desc = $this->makeDesc('Evento por partida registrado/actualizado', [
                    'accion'              => $res['accion'] ?? '',
                    'id_evento'           => $res['id_evento'] ?? '',
                    'envio_partida_id'    => $data['envio_partida_id'],
                    'contenedor_fisico'   => $data['contenedor_fisico_id'],
                    'tipo_evento'         => $data['tipo_evento_id'],
                    'fecha'               => $res['fecha'] ?? $data['fecha']
                ]);

                $this->logOpPartida(
                    $data['envio_partida_id'],
                    ($res['accion'] ?? '') === 'insertado' ? 'creacion' : 'actualizacion',
                    $desc
                );

                $this->jsonResponse([
                    'status'    => 'success',
                    'msg'       => (string)($res['msg'] ?? 'Evento guardado correctamente.'),
                    'id_evento' => $res['id_evento'] ?? null,
                    'accion'    => $res['accion'] ?? '',
                    'fecha'     => $res['fecha'] ?? $data['fecha']
                ]);
            }

            $this->jsonResponse([
                'status' => 'error',
                'msg'    => (string)($res['msg'] ?? 'No fue posible guardar el evento.')
            ]);
        } catch (Throwable $e) {
            error_log('[OP PARTIDA EVENTOS REGISTRAR] ' . $e->getMessage());

            $this->jsonResponse([
                'status' => 'error',
                'msg'    => 'Error interno al registrar el evento.'
            ], 500);
        }
    }


    /* =============================================================
       ACTUALIZAR
       Compatibilidad con JS anterior.
       ============================================================= */

    public function actualizar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->methodNotAllowed();
        }

        $idEvento = (int)($_POST['id_evento'] ?? 0);

        $envioId = (int)(
            $_POST['envio_partida_id']
            ?? $_POST['operacion_ferro_id']
            ?? $_POST['op_id']
            ?? 0
        );

        $data = [
            'id_evento'            => $idEvento,
            'envio_partida_id'     => $envioId,
            'operacion_ferro_id'   => $envioId,
            'contenedor_fisico_id' => (int)($_POST['contenedor_fisico_id'] ?? 0),
            'tipo_evento_id'       => (int)($_POST['tipo_evento_id'] ?? 0),
            'fecha'                => trim((string)($_POST['fecha'] ?? '')),
            'comentario'           => trim((string)($_POST['comentario'] ?? ''))
        ];

        if ($idEvento <= 0) {
            /*
              Si no viene id_evento, no fallamos: usamos el flujo nuevo.
              Esto ayuda cuando el JS todavía no tiene id_evento cargado.
            */
            $res = $this->model->guardarCeldaEvento(
                $data,
                (int)($_SESSION['id_usuario'] ?? 0)
            );

            if (!empty($res['ok'])) {
                $this->jsonResponse([
                    'status'    => 'success',
                    'msg'       => (string)($res['msg'] ?? 'Evento guardado correctamente.'),
                    'id_evento' => $res['id_evento'] ?? null,
                    'accion'    => $res['accion'] ?? '',
                    'fecha'     => $res['fecha'] ?? $data['fecha']
                ]);
            }

            $this->jsonResponse([
                'status' => 'error',
                'msg'    => (string)($res['msg'] ?? 'No fue posible actualizar el evento.')
            ]);
        }

        if ($data['envio_partida_id'] <= 0) {
            $this->jsonResponse([
                'status' => 'warning',
                'msg'    => 'Falta el envío de operación por partida.'
            ]);
        }

        if ($data['contenedor_fisico_id'] <= 0) {
            $this->jsonResponse([
                'status' => 'warning',
                'msg'    => 'Falta el ferro/caja.'
            ]);
        }

        if ($data['tipo_evento_id'] <= 0) {
            $this->jsonResponse([
                'status' => 'warning',
                'msg'    => 'Falta el tipo de evento.'
            ]);
        }

        if ($data['fecha'] === '') {
            $this->jsonResponse([
                'status' => 'warning',
                'msg'    => 'Debes capturar la fecha del evento.'
            ]);
        }

        try {
            $ok = $this->model->actualizar($data);

            if ($ok) {
                $desc = $this->makeDesc('Evento por partida actualizado', [
                    'id_evento'           => $idEvento,
                    'envio_partida_id'    => $data['envio_partida_id'],
                    'contenedor_fisico'   => $data['contenedor_fisico_id'],
                    'tipo_evento'         => $data['tipo_evento_id'],
                    'fecha'               => $data['fecha']
                ]);

                $this->logOpPartida($data['envio_partida_id'], 'actualizacion', $desc);

                $this->jsonResponse([
                    'status'    => 'success',
                    'msg'       => 'Evento actualizado correctamente.',
                    'id_evento' => $idEvento
                ]);
            }

            $this->jsonResponse([
                'status' => 'error',
                'msg'    => 'No fue posible actualizar el evento.'
            ]);
        } catch (Throwable $e) {
            error_log('[OP PARTIDA EVENTOS ACTUALIZAR] ' . $e->getMessage());

            $this->jsonResponse([
                'status' => 'error',
                'msg'    => 'Error interno al actualizar el evento.'
            ], 500);
        }
    }


    /* =============================================================
       ELIMINAR POR ID
       Compatibilidad con JS anterior/modal.
       ============================================================= */

    public function eliminar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->methodNotAllowed();
        }

        $idEvento = (int)($_POST['id_evento'] ?? 0);

        if ($idEvento <= 0) {
            $this->jsonResponse([
                'status' => 'warning',
                'msg'    => 'Falta el ID del evento.'
            ]);
        }

        try {
            $ok = $this->model->eliminarEventoPorId($idEvento);

            if ($ok) {
                $desc = $this->makeDesc('Evento por partida eliminado', [
                    'id_evento' => $idEvento
                ]);

                $this->logOpPartida(null, 'eliminacion', $desc);

                $this->jsonResponse([
                    'status' => 'success',
                    'msg'    => 'Evento eliminado correctamente.'
                ]);
            }

            $this->jsonResponse([
                'status' => 'error',
                'msg'    => 'No fue posible eliminar el evento.'
            ]);
        } catch (Throwable $e) {
            error_log('[OP PARTIDA EVENTOS ELIMINAR] ' . $e->getMessage());

            $this->jsonResponse([
                'status' => 'error',
                'msg'    => 'Error interno al eliminar el evento.'
            ], 500);
        }
    }


    /* =============================================================
       ELIMINAR POR CLAVE
       Este endpoint será útil para Delete/Backspace desde JS nuevo.
       También puede usarse mandando fecha vacía a guardar_celda.
       ============================================================= */

    public function eliminar_por_clave()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->methodNotAllowed();
        }

        $envioId = (int)(
            $_POST['envio_partida_id']
            ?? $_POST['operacion_ferro_id']
            ?? $_POST['op_id']
            ?? 0
        );

        $ferroId = (int)($_POST['contenedor_fisico_id'] ?? 0);
        $evtId   = (int)($_POST['tipo_evento_id'] ?? 0);

        if ($envioId <= 0 || $ferroId <= 0 || $evtId <= 0) {
            $this->jsonResponse([
                'status' => 'warning',
                'msg'    => 'Faltan datos para eliminar la celda.'
            ]);
        }

        try {
            $res = $this->model->eliminarEventoPorClave($envioId, $ferroId, $evtId);

            if (!empty($res['ok'])) {
                $desc = $this->makeDesc('Celda de evento por partida eliminada', [
                    'accion'              => $res['accion'] ?? '',
                    'id_evento'           => $res['id_evento'] ?? '',
                    'envio_partida_id'    => $envioId,
                    'contenedor_fisico'   => $ferroId,
                    'tipo_evento'         => $evtId
                ]);

                $this->logOpPartida($envioId, 'eliminacion', $desc);

                $this->jsonResponse([
                    'status'    => 'success',
                    'msg'       => (string)($res['msg'] ?? 'Evento eliminado correctamente.'),
                    'accion'    => $res['accion'] ?? '',
                    'id_evento' => $res['id_evento'] ?? null,
                    'fecha'     => ''
                ]);
            }

            $this->jsonResponse([
                'status' => 'error',
                'msg'    => (string)($res['msg'] ?? 'No fue posible eliminar la celda.')
            ]);
        } catch (Throwable $e) {
            error_log('[OP PARTIDA EVENTOS ELIMINAR CLAVE] ' . $e->getMessage());

            $this->jsonResponse([
                'status' => 'error',
                'msg'    => 'Error interno al eliminar la celda.'
            ], 500);
        }
    }
}
