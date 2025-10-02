<?php
class Operaciones_maritimo_ferro_trazabilidad extends Controller
{
    public function __construct()
    {
        parent::__construct();
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
    }
 /* ===========================
     * Helpers de respuesta JSON
     * =========================== */
    private function json($payload, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function badRequest(string $msg): void
    {
        $this->json(['ok' => false, 'msg' => $msg], 400);
    }

    private function notFound(string $msg): void
    {
        $this->json(['ok' => false, 'msg' => $msg], 404);
    }

    /* =======================================================
     * GET /operaciones_maritimo_ferro_trazabilidad/sugerencias_operaciones_ferro?q=FO-01&limit=10
     * Autosuggest para "Operación Ferroviaria" (por número FO, ferro/caja o cliente)
     * ======================================================= */
    public function sugerencias_operaciones_ferro(): void
    {
        // 1) Leer inputs
        $q     = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        if ($q === '') {
            $this->badRequest('Parámetro q requerido.');
        }
        if ($limit <= 0 || $limit > 50) { $limit = 10; }

        // 2) Consultar modelo
        try {
            $rows = $this->model->buscarOperacionesFerroSugerencias($q, $limit);
        } catch (Throwable $e) {
            error_log('sugerencias_operaciones_ferro: ' . $e->getMessage());
            $this->json(['ok' => false, 'msg' => 'Error al buscar sugerencias.'], 500);
        }

        // 3) Dar respuesta (lista plana ideal para pintar list-group)
        // Estructura sugerida para el frontend:
        // [
        //   { "id": 123, "numero_operacion": "FO-0001", "numero_ferro": "FX12345", "cliente": "ACME" }
        // ]
        $data = array_map(function($r) {
            return [
                'id'               => (int)$r['id_operacion_ferro'],
                'numero_operacion' => (string)$r['numero_operacion'],
                'numero_ferro'     => (string)($r['numero_ferro'] ?? ''),
                'cliente'          => (string)($r['cliente_nombre'] ?? '')
            ];
        }, $rows ?? []);

        $this->json(['ok' => true, 'items' => $data]);
    }

    /* =======================================================
     * GET /operaciones_maritimo_ferro_trazabilidad/datos_modal_trazabilidad?id=123
     * ======================================================= */
    public function datos_modal_trazabilidad(): void
    {
        // 1) Validar id
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            $this->badRequest('ID inválido.');
        }

        // 2) Leer paquete desde el modelo
        try {
            $pack = $this->model->getDatosModalTrazabilidad($id);
        } catch (Throwable $e) {
            error_log('datos_modal_trazabilidad: ' . $e->getMessage());
            $this->json(['ok' => false, 'msg' => 'Error al leer datos de la operación.'], 500);
        }

        if (!$pack || empty($pack['operacion'])) {
            $this->notFound('Operación ferroviaria no encontrada.');
        }

        // 3) Normalizar salida para el modal
        // operacion: id, numero_operacion, fecha, comentarios, estatus_id, cliente (id/nombre)
        $op = $pack['operacion'];
        $operacion = [
            'id_operacion_ferro' => (int)$op['id_operacion_ferro'],
            'numero_operacion'   => (string)$op['numero_operacion'],
            'fecha'              => (string)($op['fecha'] ?? ''),
            'comentarios'        => (string)($op['comentarios'] ?? ''),
            'estatus_id'         => isset($op['estatus_id']) ? (int)$op['estatus_id'] : null,
            'cliente_id'         => isset($op['cliente_id']) ? (int)$op['cliente_id'] : null,
            'cliente_nombre'     => (string)($op['cliente_nombre'] ?? '')
        ];

        // ferro: id_fisico, numero_ferro
        $ferro = $pack['ferro'] ? [
            'id_fisico'    => isset($pack['ferro']['id_fisico']) ? (int)$pack['ferro']['id_fisico'] : (int)$op['contenedor_fisico_id'],
            'numero_ferro' => (string)($pack['ferro']['numero_ferro'] ?? $op['numero_ferro'] ?? '')
        ] : [
            'id_fisico'    => isset($op['contenedor_fisico_id']) ? (int)$op['contenedor_fisico_id'] : null,
            'numero_ferro' => (string)($op['numero_ferro'] ?? '')
        ];

        // clientes: lista [{id_cliente, nombre}]
        $clientes = array_map(function($c){
            return [
                'id_cliente' => (int)$c['id_cliente'],
                'nombre'     => (string)$c['nombre']
            ];
        }, $pack['clientes'] ?? []);

        // 4) Respuesta
        $this->json([
            'ok'        => true,
            'operacion' => $operacion,
            'ferro'     => $ferro,
            'clientes'  => $clientes
        ]);
    }

    /* =======================================================
     * (OPCIONAL) GET /operaciones_maritimo_ferro_trazabilidad/ferro_por_operacion?id=123
     * Si quieres un endpoint específico para revalidar el ferro/caja 1:1
     * ======================================================= */
    public function ferro_por_operacion(): void
    {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) { $this->badRequest('ID inválido.'); }

        try {
            $row = $this->model->getFerroDeOperacionFerro($id);
        } catch (Throwable $e) {
            error_log('ferro_por_operacion: ' . $e->getMessage());
            $this->json(['ok' => false, 'msg' => 'Error al leer ferro.'], 500);
        }

        if (!$row) { $this->notFound('No se encontró ferro/caja para esta operación.'); }

        $this->json([
            'ok' => true,
            'ferro' => [
                'id_fisico'    => (int)$row['id_fisico'],
                'numero_ferro' => (string)$row['numero_ferro']
            ]
        ]);
    }
       /* =======================
     * LUGARES (ciudades/puertos)
     * ======================= */

       ///operaciones_maritimo_ferro_trazabilidad/sugerencias_lugares?q=Laz
       ///operaciones_maritimo_ferro_trazabilidad/sugerencias_ciudades?q=tij
    public function sugerencias_lugares(): void
    {
        $term  = isset($_GET['q']) ? trim($_GET['q']) : '';
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        if ($term === '') {
            $this->json(['ok'=>false, 'msg'=>'Parámetro q requerido'], 400);
        }
        $rows = $this->model->buscarLugares($term, $limit);

        $items = array_map(function($r){
            return [
                'id'    => (int)$r['id'],
                'nombre'=> (string)$r['nombre'],
                'tipo'  => (string)$r['tipo']  // ciudad|puerto
            ];
        }, $rows ?? []);

        $this->json(['ok'=>true, 'items'=>$items]);
    }

    //operaciones_maritimo_ferro_trazabilidad/sugerencias_ciudades?q=tij
    public function sugerencias_ciudades(): void
    {
        $term  = isset($_GET['q']) ? trim($_GET['q']) : '';
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        if ($term === '') {
            $this->json(['ok'=>false, 'msg'=>'Parámetro q requerido'], 400);
        }
        $rows = $this->model->buscarCiudades($term, $limit);

        $items = array_map(function($r){
            return ['id'=>(int)$r['id'], 'nombre'=>(string)$r['nombre']];
        }, $rows ?? []);

        $this->json(['ok'=>true, 'items'=>$items]);
    }
 

    /* =======================
     * TRANSPORTISTAS
     * ======================= */
    // operaciones_maritimo_ferro_trazabilidad/sugerencias_transportistas?q=ferro&tipo=ferroviario
    public function sugerencias_transportistas(): void
    {
        $term  = isset($_GET['q']) ? trim($_GET['q']) : '';
        $tipo  = isset($_GET['tipo']) ? trim($_GET['tipo']) : 'ferroviario';
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        if ($term === '') {
            $this->json(['ok'=>false, 'msg'=>'Parámetro q requerido'], 400);
        }

        $rows = $this->model->buscarTransportistas($term, $tipo, $limit);

        $items = array_map(function($r){
            return [
                'id'    => (int)$r['id'],
                'nombre'=> (string)$r['nombre'],
                'tipo'  => (string)$r['tipo']
            ];
        }, $rows ?? []);

        $this->json(['ok'=>true, 'items'=>$items]);
    }

    //REGISTRAR
     public function crear_ruta_ferro(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['ok'=>false,'msg'=>'Método no permitido'], 405);
        }

        // 1) Leer inputs
        $opId   = (int)($_POST['operacion_ferro_id']   ?? 0);
        $fisId  = (int)($_POST['contenedor_fisico_id'] ?? 0);
        $coment = isset($_POST['comentario']) ? trim((string)$_POST['comentario'])
                 : (isset($_POST['comentario_ruta']) ? trim((string)$_POST['comentario_ruta']) : null);

        // 2) Validaciones mínimas
        if ($opId <= 0 || $fisId <= 0) {
            $this->json(['ok'=>false, 'msg'=>'operacion_ferro_id y contenedor_fisico_id son requeridos.'], 400);
        }

        // 3) Insertar ruta
        try {
            $rutaId = $this->model->crearRutaFerro($opId, $fisId, $coment);
        } catch (Throwable $e) {
            error_log('crear_ruta_ferro ERROR: '.$e->getMessage());
            $this->json(['ok'=>false, 'msg'=>'Error al crear la ruta.'], 500);
        }

        if ($rutaId <= 0) {
            $this->json(['ok'=>false, 'msg'=>'No fue posible registrar la ruta.'], 500);
        }

        // 4) Respuesta OK
        $this->json([
            'ok'      => true,
            'ruta_id' => (int)$rutaId,
            'msg'     => 'Ruta creada correctamente.'
        ]);
    }


public function rutas_list(): void
    {
        try {
            // Sanitizar/leer query params
            $q       = isset($_GET['q'])      ? trim((string)$_GET['q']) : '';
            $desde   = isset($_GET['desde'])  ? trim((string)$_GET['desde']) : null;
            $hasta   = isset($_GET['hasta'])  ? trim((string)$_GET['hasta']) : null;
            $page    = isset($_GET['page'])   ? (int)$_GET['page'] : 1;
            $perPage = isset($_GET['perPage'])? (int)$_GET['perPage'] : 10;

            // Llamar al modelo
            $res = $this->model->listarRutasFerroCatalogo($q, $desde, $hasta, $page, $perPage);

            // Formato directo para tu JS de catálogo
            $out = [
                'ok'    => (bool)($res['ok'] ?? false),
                'total' => (int)($res['total'] ?? 0),
                'from'  => (int)($res['from'] ?? 0),
                'to'    => (int)($res['to'] ?? 0),
                'data'  => $res['data'] ?? []
            ];

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($out);
        } catch (Throwable $e) {
            error_log("rutas_list error: " . $e->getMessage());
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok'=>false, 'total'=>0, 'data'=>[], 'msg'=>'Error interno al listar rutas.']);
        }
    }


    //trazabilida
    public function ruta_detalle(): void
{
    $rutaId = isset($_GET['id_ruta']) ? (int)$_GET['id_ruta'] : 0;
    if ($rutaId <= 0) {
        $this->badRequest('id_ruta inválido.');
    }

    try {
        // Header (op, ferro, comentario)
        $hdr = $this->model->getRutaFerroHeaderByRutaId($rutaId);
        if (!$hdr) {
            $this->notFound('Ruta no encontrada.');
        }

        // Clientes chips (todos los de la operación)
        $clientes = $this->model->getClientesPorOperacionFerroId((int)$hdr['operacion_ferro_id']) ?: [];

        // Tramos para pintar en la tabla (carrito)
        $tramos = $this->model->getTramosPorRutaConNombres($rutaId) ?: [];

        // Normalizar salida
        $outClientes = array_map(function($c){
            return [
                'id_cliente' => (int)$c['id_cliente'],
                'nombre'     => (string)$c['nombre']
            ];
        }, $clientes);

        $this->json([
            'ok'     => true,
            'header' => [
                'id_ruta'             => (int)$hdr['id_ruta'],
                'operacion_ferro_id'  => (int)$hdr['operacion_ferro_id'],
                'numero_operacion'    => (string)$hdr['numero_operacion'],
                'contenedor_fisico_id'=> (int)$hdr['contenedor_fisico_id'],
                'numero_ferro'        => (string)($hdr['numero_ferro'] ?? ''),
                'comentario_ruta'     => (string)($hdr['comentario_ruta'] ?? '')
            ],
            'clientes' => $outClientes,
            'tramos'   => $tramos
        ]);
    } catch (Throwable $e) {
        error_log('ruta_detalle: ' . $e->getMessage());
        $this->json(['ok'=>false, 'msg'=>'Error interno.'], 500);
    }
}


// === EDITAR (DIFERENCIAL): inserta/actualiza/elimina según payload ===
public function guardar_tramos(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $this->json(['ok'=>false,'msg'=>'Método no permitido'], 405);
    }

    $rutaId           = (int)($_POST['ruta_id'] ?? 0);
    $operacionFerroId = (int)($_POST['operacion_ferro_id'] ?? 0);

    // Acepta "rutasPayload" (hidden) o "tramos"
    $raw = $_POST['rutasPayload'] ?? $_POST['tramos'] ?? '[]';
    $tramosPayload = json_decode($raw, true);

    if ($rutaId <= 0 || $operacionFerroId <= 0) {
        $this->json(['ok'=>false,'msg'=>'ruta_id y operacion_ferro_id son requeridos.'], 400);
    }
    if (!is_array($tramosPayload) || empty($tramosPayload)) {
        $this->json(['ok'=>false,'msg'=>'No hay tramos para guardar.'], 400);
    }

    // Deben venir TODOS los tramos que quieres que queden en BD:
    //  - existentes con id_tramo > 0 (para conservar o actualizar)
    //  - nuevos sin id_tramo (para insertar)
    //  - los que NO vengan serán eliminados
    if (session_status() === PHP_SESSION_NONE) { @session_start(); }
    $creadoPor = isset($_SESSION['id_usuario']) ? (int)$_SESSION['id_usuario'] : 0;
    if ($creadoPor <= 0) {
        $this->json(['ok'=>false,'msg'=>'Sesión inválida. Vuelve a iniciar sesión.'], 401);
    }

    try {
        $res = $this->model->guardarTramosYCostosTransaccional(
            $operacionFerroId, $rutaId, $tramosPayload, $creadoPor
        );
        if (empty($res['ok'])) {
            $this->json(['ok'=>false,'msg'=>$res['msg'] ?? 'No fue posible guardar los tramos.'], 500);
        }
        $this->json([
            'ok'=>true,
            'msg'=>'Tramos guardados correctamente.',
            'data'=>[
                'insertados'   => (int)$res['insertados'],
                'actualizados' => (int)$res['actualizados'],
                'eliminados'   => (int)$res['eliminados'],
                'costos'       => (int)$res['costos'],
            ]
        ]);
    } catch (Throwable $e) {
        error_log("guardar_tramos ERROR: ".$e->getMessage());
        $this->json(['ok'=>false,'msg'=>'Error interno al guardar tramos.'], 500);
    }
}

// === APPEND-ONLY: inserta/actualiza; NO elimina tramos que no vengan ===
public function guardar_tramos_append(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $this->json(['ok'=>false, 'msg'=>'Método no permitido'], 405);
    }

    $rutaId           = (int)($_POST['ruta_id'] ?? 0);
    $operacionFerroId = (int)($_POST['operacion_ferro_id'] ?? 0);

    $raw           = $_POST['rutasPayload'] ?? $_POST['tramos'] ?? '[]';
    $tramosPayload = json_decode($raw, true);

    if ($rutaId <= 0 || $operacionFerroId <= 0) {
        $this->json(['ok'=>false,'msg'=>'ruta_id y operacion_ferro_id son requeridos.'], 400);
    }
    if (!is_array($tramosPayload) || empty($tramosPayload)) {
        $this->json(['ok'=>false,'msg'=>'No hay tramos para guardar.'], 400);
    }

    if (session_status() === PHP_SESSION_NONE) { @session_start(); }
    $creadoPor = isset($_SESSION['id_usuario']) ? (int)$_SESSION['id_usuario'] : 0;
    if ($creadoPor <= 0) {
        $this->json(['ok'=>false, 'msg'=>'Sesión inválida. Vuelve a iniciar sesión.'], 401);
    }

    try {
        $res = $this->model->guardarTramosAppendOnly(
            $operacionFerroId, $rutaId, $tramosPayload, $creadoPor
        );
        if (empty($res['ok'])) {
            $this->json(['ok'=>false, 'msg'=>$res['msg'] ?? 'No fue posible guardar.'], 500);
        }
        $this->json([
            'ok'=>true,
            'msg'=>'Tramos guardados (sin eliminar existentes).',
            'data'=>[
                'insertados'   => (int)$res['insertados'],
                'actualizados' => (int)$res['actualizados'],
                'costos'       => (int)$res['costos'],
            ]
        ]);
    } catch (Throwable $e) {
        error_log('guardar_tramos_append: ' . $e->getMessage());
        $this->json(['ok'=>false, 'msg'=>'Error interno al guardar.'], 500);
    }
}




}