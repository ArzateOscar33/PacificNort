<?php
class Operaciones_por_partida_envios extends Controller
{
    public function __construct()
    {
        parent::__construct();
        session_start();

        if (empty($_SESSION['nombre_usuario'])) {
            header('Location: ' . BASE_URL . 'admin');
            exit;
        }

        // Solo usuarios internos
        $this->requireRoles([1, 11, 2]);
    }

    /* =========================================================
       VISTA
       ========================================================= */
    public function index()
    {
        $data['title'] = 'Envíos por Ferro / Caja';
        $this->views->getView($this, "index", $data);
    }


    public function listar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->methodNotAllowed();
        }

        $page    = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = isset($_GET['per_page']) ? min(100, max(1, (int)$_GET['per_page'])) : 10;

        $fisicoId = null;
        if (isset($_GET['fisico_id']) && $_GET['fisico_id'] !== '') {
            $fisicoId = (int)$_GET['fisico_id'];
            if ($fisicoId <= 0) {
                $fisicoId = null;
            }
        }

        $transportistaId = null;
        if (isset($_GET['transportista_id']) && $_GET['transportista_id'] !== '') {
            $transportistaId = (int)$_GET['transportista_id'];
            if ($transportistaId <= 0) {
                $transportistaId = null;
            }
        }

        $estatusEnvio = isset($_GET['estatus_envio'])
            ? trim((string)$_GET['estatus_envio'])
            : '';

        $q = isset($_GET['q'])
            ? trim((string)$_GET['q'])
            : '';

        try {
            $result = $this->model->listarPaginado(
                $page,
                $perPage,
                $fisicoId,
                $transportistaId,
                $estatusEnvio,
                $q
            );

            $rows  = isset($result['rows']) && is_array($result['rows']) ? $result['rows'] : [];
            $total = isset($result['total']) ? (int)$result['total'] : 0;

            $totalPages = $perPage > 0 ? (int)ceil($total / $perPage) : 1;

            $this->jsonResponse([
                'ok'          => true,
                'rows'        => $rows,
                'total'       => $total,
                'page'        => $page,
                'per_page'    => $perPage,
                'total_pages' => $totalPages
            ]);
        } catch (Exception $e) {
            $this->jsonResponse([
                'ok'  => false,
                'msg' => 'Error al listar los envíos.',
                'err' => $e->getMessage()
            ], 500);
        }
    }


    /* =========================================================
       HELPERS JSON
       ========================================================= */
    private function jsonResponse($data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function methodNotAllowed(): void
    {
        $this->jsonResponse([
            'ok'  => false,
            'msg' => 'Método no permitido.'
        ], 405);
    }

    /* =========================================================
       CATÁLOGOS / BÚSQUEDAS
       ========================================================= */




    /**
     * GET
     * Autocomplete de destinos (ciudades)
     * Params:
     * - term
     * - limit (opcional)
     */


    /**
     * GET
     * Autocomplete de ferro / caja
     * Params:
     * - term
     * - limit (opcional)
     */
    public function sugerirFerroCaja()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->methodNotAllowed();
        }

        $term  = isset($_GET['term']) ? trim((string)$_GET['term']) : '';
        $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 10;

        if ($term === '') {
            $this->jsonResponse([]);
        }

        try {
            $rows = $this->model->sugerirFerroCaja($term, $limit);

            $out = array_map(function ($r) {
                return [
                    'id'           => (int)($r['id'] ?? 0),
                    'label'        => (string)($r['numero_ferro'] ?? ''),
                    'value'        => (string)($r['numero_ferro'] ?? ''),
                    'numero_ferro' => (string)($r['numero_ferro'] ?? '')
                ];
            }, is_array($rows) ? $rows : []);

            $this->jsonResponse($out);
        } catch (Exception $e) {
            $this->jsonResponse([
                'ok'  => false,
                'msg' => 'Error al buscar ferro/caja.',
                'err' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET
     * Buscar facturas para autocomplete
     * Params:
     * - term
     * - limit (opcional)
     */
    public function sugerirFacturas()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->methodNotAllowed();
        }

        $term  = isset($_GET['term']) ? trim((string)$_GET['term']) : '';
        $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 10;

        if ($term === '') {
            $this->jsonResponse([]);
        }

        try {
            $rows = $this->model->sugerirFacturas($term, $limit);

            $out = array_map(function ($r) {
                return [
                    'id'                => (int)($r['id'] ?? 0),
                    'label'             => (string)($r['numero_factura'] ?? ''),
                    'value'             => (string)($r['numero_factura'] ?? ''),
                    'numero_factura'    => (string)($r['numero_factura'] ?? ''),
                    'proveedor'         => (string)($r['proveedor'] ?? ''),
                    'bodega'            => (string)($r['bodega'] ?? ''),
                    'pallets_inv'       => (int)($r['pallets_inv'] ?? 0),
                    'fecha_recibido'    => (string)($r['fecha_recibido'] ?? ''),
                    'cajas_disponibles' => (int)($r['cajas_disponibles'] ?? 0)
                ];
            }, is_array($rows) ? $rows : []);

            $this->jsonResponse($out);
        } catch (Exception $e) {
            $this->jsonResponse([
                'ok'  => false,
                'msg' => 'Error al buscar facturas.',
                'err' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET
     * Devuelve encabezado de factura + productos
     * Params:
     * - factura_id
     */
    public function productosFactura()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->methodNotAllowed();
        }

        $facturaId = isset($_GET['factura_id']) ? (int)$_GET['factura_id'] : 0;

        if ($facturaId <= 0) {
            $this->jsonResponse([
                'ok'  => false,
                'msg' => 'Factura inválida.'
            ], 400);
        }

        try {
            $factura = $this->model->obtenerFacturaPorId($facturaId);

            if (!$factura) {
                $this->jsonResponse([
                    'ok'  => false,
                    'msg' => 'Factura no encontrada.'
                ], 404);
            }

            $productos = $this->model->listarProductosPorFactura($facturaId);

            $productosOut = array_map(function ($r) {
                return [
                    'id'              => (int)($r['id'] ?? 0),
                    'factura_id'      => (int)($r['factura_id'] ?? 0),
                    'descripcion'     => (string)($r['descripcion'] ?? ''),
                    'upc'             => (string)($r['upc'] ?? ''),
                    'marca'           => (string)($r['marca'] ?? ''),
                    'expiracion'      => (string)($r['expiracion'] ?? ''),
                    'inner_pack'      => (int)($r['inner_pack'] ?? 0),
                    'case_pack'       => (int)($r['case_pack'] ?? 0),
                    'pallets_rcv'     => (int)($r['pallets_rcv'] ?? 0),
                    'cajas_totales'   => (int)($r['cajas_totales'] ?? 0),
                    'cajas_enviadas'  => (int)($r['cajas_enviadas'] ?? 0),
                    'cajas_restantes' => (int)($r['cajas_restantes'] ?? 0),
                    'piezas'          => (int)($r['piezas'] ?? 0)
                ];
            }, is_array($productos) ? $productos : []);

            $this->jsonResponse([
                'ok'      => true,
                'factura' => [
                    'id_factura'        => (int)($factura['id_factura'] ?? 0),
                    'numero_factura'    => (string)($factura['numero_factura'] ?? ''),
                    'proveedor'         => (string)($factura['proveedor'] ?? ''),
                    'bodega'            => (string)($factura['bodega'] ?? ''),
                    'pallets_inv'       => (int)($factura['pallets_inv'] ?? 0),
                    'fecha_recibido'    => (string)($factura['fecha_recibido'] ?? ''),
                    'notas'             => (string)($factura['notas'] ?? ''),
                    'cajas_totales'     => (int)($factura['cajas_totales'] ?? 0),
                    'cajas_disponibles' => (int)($factura['cajas_disponibles'] ?? 0)
                ],
                'productos' => $productosOut
            ]);
        } catch (Exception $e) {
            $this->jsonResponse([
                'ok'  => false,
                'msg' => 'Error al obtener productos de la factura.',
                'err' => $e->getMessage()
            ], 500);
        }
    }

    /* =========================================================
       REGISTRO
       ========================================================= */

    /**
     * POST
     * Registra envío + detalle
     *
     * Espera:
     * - contenedor_fisico_id
     * - destino_ciudad_id
     * - fecha_envio
     * - estatus_envio
     * - transportista_id
     * - notas
     * - detalle (JSON)
     *
     * detalle esperado:
     * [
     *   {
     *     "factura_id": 1,
     *     "producto_id": 10,
     *     "cajas_enviadas": 25,
     *     "notas_detalle": ""
     *   }
     * ]
     */
    public function registrar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->methodNotAllowed();
        }

        $contenedorFisicoId = isset($_POST['contenedor_fisico_id']) ? (int)$_POST['contenedor_fisico_id'] : 0;
        $numeroFerro        = isset($_POST['numero_ferro']) ? trim((string)$_POST['numero_ferro']) : '';

        $destinoCiudadId = isset($_POST['destino_ciudad_id']) && $_POST['destino_ciudad_id'] !== ''
            ? (int)$_POST['destino_ciudad_id']
            : null;

        $fechaEnvio = isset($_POST['fecha_envio']) ? trim((string)$_POST['fecha_envio']) : '';
        $estatusEnvio = isset($_POST['estatus_envio']) ? trim((string)$_POST['estatus_envio']) : '';

        $transportistaId = isset($_POST['transportista_id']) && $_POST['transportista_id'] !== ''
            ? (int)$_POST['transportista_id']
            : null;

        $notas = isset($_POST['notas']) ? trim((string)$_POST['notas']) : '';

        $detalleRaw = isset($_POST['detalle']) ? $_POST['detalle'] : '[]';
        $detalle = json_decode($detalleRaw, true);

        if ($contenedorFisicoId <= 0 && $numeroFerro === '') {
            $this->jsonResponse([
                'ok'  => false,
                'msg' => 'Debes seleccionar o escribir un ferro/caja válido.'
            ], 400);
        }

        if (empty($fechaEnvio)) {
            $this->jsonResponse([
                'ok'  => false,
                'msg' => 'La fecha de envío es obligatoria.'
            ], 400);
        }

        if (empty($estatusEnvio)) {
            $this->jsonResponse([
                'ok'  => false,
                'msg' => 'El estatus del envío es obligatorio.'
            ], 400);
        }

        if (empty($transportistaId) || $transportistaId <= 0) {
            $this->jsonResponse([
                'ok'  => false,
                'msg' => 'Debes seleccionar un transportista válido.'
            ], 400);
        }

        if (!is_array($detalle) || empty($detalle)) {
            $this->jsonResponse([
                'ok'  => false,
                'msg' => 'Debes agregar al menos un producto al envío.'
            ], 400);
        }

        try {
            // Resolver o crear ferro/caja si no viene ID
            if ($contenedorFisicoId <= 0) {
                $ferro = $this->model->obtenerOCrearFerroCaja($numeroFerro);

                if (!$ferro || empty($ferro['id_fisico'])) {
                    $this->jsonResponse([
                        'ok'  => false,
                        'msg' => 'No fue posible resolver o crear el ferro/caja.'
                    ], 400);
                }

                $contenedorFisicoId = (int)$ferro['id_fisico'];
            }

            // Registrar encabezado
            $envioId = $this->model->registrarEnvio(
                $contenedorFisicoId,
                $destinoCiudadId,
                $fechaEnvio,
                $estatusEnvio,
                $transportistaId,
                $notas
            );

            if (empty($envioId)) {
                $this->jsonResponse([
                    'ok'  => false,
                    'msg' => 'No fue posible registrar el envío.'
                ], 500);
            }

            $insertados = 0;
            $erroresDetalle = [];

            foreach ($detalle as $index => $item) {
                $facturaId     = isset($item['factura_id']) ? (int)$item['factura_id'] : 0;
                $productoId    = isset($item['producto_id']) ? (int)$item['producto_id'] : 0;
                $cajasEnviadas = isset($item['cajas_enviadas']) ? (int)$item['cajas_enviadas'] : 0;
                $notasDetalle  = isset($item['notas_detalle']) ? trim((string)$item['notas_detalle']) : '';

                if ($facturaId <= 0 || $productoId <= 0 || $cajasEnviadas <= 0) {
                    $erroresDetalle[] = "Detalle #" . ($index + 1) . ": datos incompletos.";
                    continue;
                }

                $producto = $this->model->obtenerProductoConDisponibilidad($productoId);

                if (!$producto) {
                    $erroresDetalle[] = "Detalle #" . ($index + 1) . ": producto no encontrado o inactivo.";
                    continue;
                }

                if ((int)$producto['factura_id'] !== $facturaId) {
                    $erroresDetalle[] = "Detalle #" . ($index + 1) . ": el producto no pertenece a la factura indicada.";
                    continue;
                }

                $cajasRestantes = (int)($producto['cajas_restantes'] ?? 0);
                if ($cajasEnviadas > $cajasRestantes) {
                    $erroresDetalle[] = "Detalle #" . ($index + 1) . ": cajas solicitadas mayores a las disponibles.";
                    continue;
                }

                $okDetalle = $this->model->registrarEnvioDetalle(
                    (int)$envioId,
                    $facturaId,
                    $productoId,
                    $cajasEnviadas,
                    $notasDetalle
                );

                if ($okDetalle) {
                    $insertados++;
                } else {
                    $erroresDetalle[] = "Detalle #" . ($index + 1) . ": no fue posible guardar el detalle.";
                }
            }

            if ($insertados <= 0) {
                $this->jsonResponse([
                    'ok'       => false,
                    'msg'      => 'El envío fue creado, pero no se registró ningún detalle válido.',
                    'envio_id' => (int)$envioId,
                    'errores'  => $erroresDetalle
                ], 400);
            }

            $this->jsonResponse([
                'ok'             => true,
                'msg'            => 'Envío registrado correctamente.',
                'envio_id'       => (int)$envioId,
                'detalles_ok'    => (int)$insertados,
                'detalles_total' => count($detalle),
                'errores'        => $erroresDetalle
            ]);
        } catch (Exception $e) {
            $this->jsonResponse([
                'ok'  => false,
                'msg' => 'Error al registrar el envío.',
                'err' => $e->getMessage()
            ], 500);
        }
    }

    /*actualizar*/
    /**
     * GET
     * Obtiene un envío por id para edición
     * Params:
     * - id_envio
     */
    public function obtener()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->methodNotAllowed();
        }

        $envioId = isset($_GET['id_envio']) ? (int)$_GET['id_envio'] : 0;

        if ($envioId <= 0) {
            $this->jsonResponse([
                'ok'  => false,
                'msg' => 'ID de envío inválido.'
            ], 400);
        }

        try {
            $envio = $this->model->obtenerEnvioPorId($envioId);

            if (!$envio) {
                $this->jsonResponse([
                    'ok'  => false,
                    'msg' => 'Envío no encontrado.'
                ], 404);
            }

            $detalle = isset($envio['detalle']) && is_array($envio['detalle'])
                ? $envio['detalle']
                : [];

            $detalleOut = array_map(function ($row) {
                return [
                    'id_envio_detalle' => (int)($row['id_envio_detalle'] ?? 0),
                    'envio_id'         => (int)($row['envio_id'] ?? 0),
                    'factura_id'       => (int)($row['factura_id'] ?? 0),
                    'producto_id'      => (int)($row['producto_id'] ?? 0),
                    'numero_factura'   => (string)($row['numero_factura'] ?? ''),
                    'descripcion'      => (string)($row['descripcion'] ?? ''),
                    'upc'              => (string)($row['upc'] ?? ''),
                    'marca'            => (string)($row['marca'] ?? ''),
                    'cajas_enviadas'   => (int)($row['cajas_enviadas'] ?? 0),
                    'notas_detalle'    => (string)($row['notas_detalle'] ?? '')
                ];
            }, $detalle);

            $this->jsonResponse([
                'ok'    => true,
                'envio' => [
                    'id_envio'             => (int)($envio['id_envio'] ?? 0),
                    'contenedor_fisico_id' => (int)($envio['contenedor_fisico_id'] ?? 0),
                    'ferro'                => (string)($envio['ferro'] ?? ''),
                    'transportista_id'     => isset($envio['transportista_id']) ? (int)$envio['transportista_id'] : null,
                    'transportista'        => (string)($envio['transportista'] ?? ''),
                    'fecha_envio'          => (string)($envio['fecha_envio'] ?? ''),
                    'destino_ciudad_id'    => isset($envio['destino_ciudad_id']) ? (int)$envio['destino_ciudad_id'] : null,
                    'destino'              => (string)($envio['destino'] ?? ''),
                    'estatus_envio'        => (string)($envio['estatus_envio'] ?? ''),
                    'notas'                => (string)($envio['notas'] ?? ''),
                    'detalle'              => $detalleOut
                ]
            ]);
        } catch (Exception $e) {
            $this->jsonResponse([
                'ok'  => false,
                'msg' => 'Error al obtener el envío.',
                'err' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST
     * Actualiza un envío (solo estatus y notas)
     *
     * Espera:
     * - id_envio
     * - estatus_envio
     * - notas
     */
    public function actualizar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->methodNotAllowed();
        }

        $envioId = isset($_POST['id_envio']) ? (int)$_POST['id_envio'] : 0;
        $estatusEnvio = isset($_POST['estatus_envio']) ? trim((string)$_POST['estatus_envio']) : '';
        $notas = isset($_POST['notas']) ? trim((string)$_POST['notas']) : '';

        if ($envioId <= 0) {
            $this->jsonResponse([
                'ok'  => false,
                'msg' => 'ID de envío inválido.'
            ], 400);
        }

        if ($estatusEnvio === '') {
            $this->jsonResponse([
                'ok'  => false,
                'msg' => 'El estatus del envío es obligatorio.'
            ], 400);
        }

        try {
            $envio = $this->model->obtenerEnvioPorId($envioId);

            if (!$envio) {
                $this->jsonResponse([
                    'ok'  => false,
                    'msg' => 'El envío no existe o ya no está disponible.'
                ], 404);
            }

            $ok = $this->model->actualizarEnvioEditable(
                $envioId,
                $estatusEnvio,
                $notas
            );

            if (!$ok) {
                $this->jsonResponse([
                    'ok'  => false,
                    'msg' => 'No fue posible actualizar el envío.'
                ], 500);
            }

            $this->jsonResponse([
                'ok'  => true,
                'msg' => 'Envío actualizado correctamente.'
            ]);
        } catch (Exception $e) {
            $this->jsonResponse([
                'ok'  => false,
                'msg' => 'Error al actualizar el envío.',
                'err' => $e->getMessage()
            ], 500);
        }
    }
}
