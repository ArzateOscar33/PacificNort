<?php

require_once "Models/BitacoraOpPartidaModel.php";
class Operaciones_por_partida extends Controller
{
    protected $bitacoraOpPartida;
    public function __construct()
    {
        parent::__construct();
        session_start();

        if (empty($_SESSION['nombre_usuario'])) {
            header('Location: ' . BASE_URL . 'admin');
            exit;
        }
        // Solo sin rol cliente
        //$this->requireRoles([1, 11, 2]);
        $this->requireRoles([1, 11, 2, 15]);
        $this->bitacoraOpPartida = new BitacoraOpPartidaModel();
    }

    public function index()
    {
        $data['title'] = 'Operaciones por Partida';
        $data['bodegas'] = $this->model->listarBodegasActivas();
        $data['transportistas'] = $this->model->listarTransportistas();
        $data['ciudades'] = $this->model->listarCiudades();
        $data['clientes'] = $this->model->listarClientes();

        // Si en tu vista el filtro de bodegas se llena con PHP:
        // $data['bodegas'] = $this->model->getBodegasActivas();  (opcional, después)
        $this->views->getView('admin/Operaciones_por_partida', "ver", $data);
    }
    private function registrarBitacoraPartida(
        string $modulo,
        string $accion,
        string $entidad,
        ?int $entidadId = null,
        ?string $detalle = null
    ) {
        try {
            $usuarioId = $_SESSION['id_usuario'] ?? null;

            return $this->bitacoraOpPartida->crear(
                $usuarioId,
                $modulo,
                $accion,
                $entidad,
                $entidadId,
                $detalle
            );
        } catch (Exception $e) {
            error_log('[BITACORA OP PARTIDA] ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Endpoint JSON para listar facturas
     * URL ejemplo:
     *  /Operaciones_por_partida/listar?bodega_id=&term=&fi=&ff=&page=1&per_page=10
     */
    public function listar()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            // ====== Inputs (GET) ======
            $bodegaId = isset($_GET['bodega_id']) ? trim($_GET['bodega_id']) : '';
            $term     = isset($_GET['term']) ? trim($_GET['term']) : '';
            $fi       = isset($_GET['fi']) ? trim($_GET['fi']) : '';
            $ff       = isset($_GET['ff']) ? trim($_GET['ff']) : '';

            $page     = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $perPage  = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;

            if ($page < 1) $page = 1;
            if ($perPage < 1) $perPage = 10;

            // Limitar per_page a opciones típicas
            $allowed = [10, 25, 50, 100, 200, 500, 1000000];
            if (!in_array($perPage, $allowed, true)) $perPage = 10;

            // ====== Modelo ======
            $result = $this->model->listarFacturas([
                'bodega_id' => $bodegaId,
                'term'      => $term,
                'fi'        => $fi,
                'ff'        => $ff,
                'page'      => $page,
                'per_page'  => $perPage
            ]);

            $rows  = $result['rows'] ?? [];
            $total = (int)($result['total'] ?? 0);

            $totalPages = $perPage > 0 ? (int)ceil($total / $perPage) : 0;

            echo json_encode([
                'ok'   => true,
                'data' => $rows,
                'meta' => [
                    'total'       => $total,
                    'page'        => $page,
                    'per_page'    => $perPage,
                    'total_pages' => $totalPages
                ]
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            error_log("Operaciones_por_partida/listar ERROR: " . $e->getMessage());

            echo json_encode([
                'ok'   => false,
                'msg'  => 'Ocurrió un error al listar facturas.',
                'data' => [],
                'meta' => [
                    'total' => 0,
                    'page' => 1,
                    'per_page' => 10,
                    'total_pages' => 0
                ]
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }


    public function listarProductos()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $facturaId = isset($_GET['factura_id']) ? (int)$_GET['factura_id'] : 0;
            $term      = isset($_GET['term']) ? trim($_GET['term']) : '';

            $page      = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $perPage   = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 50;

            if ($facturaId <= 0) {
                echo json_encode([
                    'ok'     => false,
                    'msg'    => 'Factura inválida.',
                    'data'   => [],
                    'meta'   => ['total' => 0, 'page' => 1, 'per_page' => 50, 'total_pages' => 0],
                    'totals' => [
                        'total_cajas' => 0,
                        'total_piezas' => 0,
                        'total_pallets_rcv' => 0,
                        'total_cajas_enviadas' => 0,
                        'total_cajas_restantes' => 0
                    ]
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            if ($page < 1) $page = 1;
            if ($perPage < 1) $perPage = 50;

            $allowed = [10, 25, 50, 100, 200];
            if (!in_array($perPage, $allowed, true)) $perPage = 50;

            $result = $this->model->listarProductosConFotos($facturaId, [
                'term'     => $term,
                'page'     => $page,
                'per_page' => $perPage
            ]);

            $rows   = $result['rows'] ?? [];
            $total  = (int)($result['total'] ?? 0);
            $totals = $result['totals'] ?? [
                'total_cajas'           => 0,
                'total_piezas'          => 0,
                'total_pallets_rcv'     => 0,
                'total_cajas_enviadas'  => 0,
                'total_cajas_restantes' => 0
            ];

            $totalPages = $perPage > 0 ? (int)ceil($total / $perPage) : 0;

            echo json_encode([
                'ok'   => true,
                'data' => $rows,
                'meta' => [
                    'total'       => $total,
                    'page'        => $page,
                    'per_page'    => $perPage,
                    'total_pages' => $totalPages
                ],
                'totals' => [
                    'total_cajas'           => (int)($totals['total_cajas'] ?? 0),
                    'total_piezas'          => (int)($totals['total_piezas'] ?? 0),
                    'total_pallets_rcv'     => (int)($totals['total_pallets_rcv'] ?? 0),
                    'total_cajas_enviadas'  => (int)($totals['total_cajas_enviadas'] ?? 0),
                    'total_cajas_restantes' => (int)($totals['total_cajas_restantes'] ?? 0),
                ]
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            error_log("Operaciones_por_partida/listarProductos ERROR: " . $e->getMessage());
            echo json_encode([
                'ok'     => false,
                'msg'    => 'Ocurrió un error al listar productos.',
                'data'   => [],
                'meta'   => ['total' => 0, 'page' => 1, 'per_page' => 50, 'total_pages' => 0],
                'totals' => [
                    'total_cajas' => 0,
                    'total_piezas' => 0,
                    'total_pallets_rcv' => 0,
                    'total_cajas_enviadas' => 0,
                    'total_cajas_restantes' => 0
                ]
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    public function registrar()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode([
                    'ok'  => false,
                    'msg' => 'Método no permitido.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // ====== Inputs (POST) ======
            $bodegaId      = isset($_POST['operaciones_partida_bodega']) ? (int)$_POST['operaciones_partida_bodega'] : 0;
            $clienteId     = isset($_POST['operaciones_partida_cliente']) ? (int)$_POST['operaciones_partida_cliente'] : 0;
            $numeroFactura = isset($_POST['invoice_number'])
                ? trim((string)$_POST['invoice_number'])
                : (isset($_POST['operaciones_partida_factura']) ? trim((string)$_POST['operaciones_partida_factura']) : '');
            $proveedor     = isset($_POST['vendor_name'])
                ? trim((string)$_POST['vendor_name'])
                : (isset($_POST['operaciones_partida_proveedor']) ? trim((string)$_POST['operaciones_partida_proveedor']) : '');

            $revisionEstatus = isset($_POST['operaciones_partida_revision_select'])
                ? (int)$_POST['operaciones_partida_revision_select']
                : 0;

            $palletsInv    = isset($_POST['pallets_inv'])
                ? (int)$_POST['pallets_inv']
                : (isset($_POST['operaciones_partida_pallets_inv']) ? (int)$_POST['operaciones_partida_pallets_inv'] : 0);

            $fechaRecibido = isset($_POST['received_date'])
                ? trim((string)$_POST['received_date'])
                : (isset($_POST['operaciones_partida_fechaRecibido']) ? trim((string)$_POST['operaciones_partida_fechaRecibido']) : '');

            $notas         = isset($_POST['comentarios'])
                ? trim((string)$_POST['comentarios'])
                : (isset($_POST['operaciones_partida_notas']) ? trim((string)$_POST['operaciones_partida_notas']) : '');
            if (!in_array($revisionEstatus, [0, 1, 2, 3], true)) {
                echo json_encode([
                    'ok' => false,
                    'msg' => 'Estatus de revisión inválido.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            // ====== Sesión ======
            $creadoPor = isset($_SESSION['id_usuario']) ? (int)$_SESSION['id_usuario'] : null;

            // ====== Modelo ======
            $resp = $this->model->registrarFactura([
                'bodega_id'      => $bodegaId,
                'cliente_id'     => $clienteId,
                'numero_factura' => $numeroFactura,
                'proveedor'      => $proveedor,
                'revision_estatus' => $revisionEstatus,
                'pallets_inv'    => $palletsInv,
                'fecha_recibido' => $fechaRecibido,
                'notas'          => $notas,
                'creado_por'     => $creadoPor
            ]);

            if (empty($resp) || empty($resp['ok'])) {
                echo json_encode([
                    'ok'         => false,
                    'msg'        => $resp['msg'] ?? 'No se pudo registrar la factura.',
                    'id_factura' => $resp['id_factura'] ?? null
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Devuelve la factura recién creada
            $idFactura = (int)$resp['id_factura'];
            $factura   = $this->model->getFacturaByIdEditar($idFactura);
            //registra en bitacora
            $this->registrarBitacoraPartida(
                'op_partida_facturas',
                'crear',
                'op_partida_facturas',
                $idFactura,
                $this->bitacoraOpPartida->desc('factura', 'creada', [
                    'factura_id' => $idFactura,
                    'numero_factura' => $numeroFactura,
                    'cliente_id' => $clienteId,
                    'bodega_id' => $bodegaId
                ])
            );

            echo json_encode([
                'ok'         => true,
                'msg'        => $resp['msg'] ?? 'Factura registrada correctamente.',
                'id_factura' => $idFactura,
                'factura'    => $factura ?: null
            ], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (Throwable $e) {
            error_log("Operaciones_por_partida/registrar ERROR: " . $e->getMessage());

            echo json_encode([
                'ok'  => false,
                'msg' => 'Ocurrió un error al registrar la factura.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }


    public function getFactura()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $id = isset($_GET['id_factura']) ? (int)$_GET['id_factura'] : 0;

            if ($id <= 0) {
                echo json_encode([
                    'ok' => false,
                    'msg' => 'ID de factura inválido.',
                    'factura' => null
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Usa el método para editar, que ya incluye cliente_id y cliente_nombre
            $factura = $this->model->getFacturaByIdEditar($id);

            if (!$factura) {
                echo json_encode([
                    'ok' => false,
                    'msg' => 'Factura no encontrada o inactiva.',
                    'factura' => null
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            echo json_encode([
                'ok' => true,
                'factura' => $factura
            ], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (Throwable $e) {
            error_log("Operaciones_por_partida/getFactura ERROR: " . $e->getMessage());

            echo json_encode([
                'ok' => false,
                'msg' => 'Ocurrió un error al obtener la factura.',
                'factura' => null
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    public function actualizar()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode([
                    'ok' => false,
                    'msg' => 'Método no permitido.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $idFactura = isset($_POST['operaciones_partida_id']) ? (int)$_POST['operaciones_partida_id'] : 0;

            if ($idFactura <= 0) {
                echo json_encode([
                    'ok' => false,
                    'msg' => 'ID de factura inválido.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $bodegaId      = isset($_POST['operaciones_partida_bodega']) ? (int)$_POST['operaciones_partida_bodega'] : 0;
            $clienteId     = isset($_POST['operaciones_partida_cliente']) ? (int)$_POST['operaciones_partida_cliente'] : 0;
            $numeroFactura = isset($_POST['invoice_number']) ? trim((string)$_POST['invoice_number']) : '';
            $proveedor     = isset($_POST['vendor_name']) ? trim((string)$_POST['vendor_name']) : '';
            $revisionEstatus = isset($_POST['operaciones_partida_revision_select'])
                ? (int)$_POST['operaciones_partida_revision_select']
                : 0;
            if (!in_array($revisionEstatus, [0, 1, 2, 3], true)) {
                echo json_encode([
                    'ok' => false,
                    'msg' => 'Estatus de revisión inválido.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $palletsInv    = isset($_POST['pallets_inv']) ? (int)$_POST['pallets_inv'] : 0;
            $fechaRecibido = isset($_POST['received_date']) ? trim((string)$_POST['received_date']) : '';
            $notas         = isset($_POST['comentarios']) ? trim((string)$_POST['comentarios']) : '';

            $actualizadoPor = isset($_SESSION['id_usuario']) ? (int)$_SESSION['id_usuario'] : null;

            $resp = $this->model->actualizarFactura($idFactura, [
                'bodega_id'       => $bodegaId,
                'cliente_id'      => $clienteId,
                'numero_factura'  => $numeroFactura,
                'proveedor'       => $proveedor,
                'revision_estatus' => $revisionEstatus,
                'pallets_inv'     => $palletsInv,
                'fecha_recibido'  => $fechaRecibido,
                'notas'           => $notas,
                'actualizado_por' => $actualizadoPor
            ]);

            if (empty($resp) || empty($resp['ok'])) {
                echo json_encode([
                    'ok' => false,
                    'msg' => $resp['msg'] ?? 'No se pudo actualizar la factura.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Devuelve la factura actualizada
            $factura = $this->model->getFacturaByIdEditar($idFactura);

            //registra en bitacora
            $this->registrarBitacoraPartida(
                'op_partida_facturas',
                'actualizacion',
                'op_partida_facturas',
                $idFactura,
                $this->bitacoraOpPartida->desc('factura', 'actualizada', [
                    'factura_id' => $idFactura,
                    'numero_factura' => $numeroFactura,
                    'cliente_id' => $clienteId,
                    'bodega_id' => $bodegaId
                ])
            );

            echo json_encode([
                'ok' => true,
                'msg' => $resp['msg'] ?? 'Factura actualizada correctamente.',
                'factura' => $factura ?: null
            ], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (Throwable $e) {
            error_log("Operaciones_por_partida/actualizar ERROR: " . $e->getMessage());

            echo json_encode([
                'ok' => false,
                'msg' => 'Ocurrió un error al actualizar la factura.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    public function baja()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['ok' => false, 'msg' => 'Método no permitido.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Acepta varias llaves por flexibilidad
            $idFactura = 0;
            if (isset($_POST['id_factura'])) {
                $idFactura = (int)$_POST['id_factura'];
            } elseif (isset($_POST['operaciones_partida_id'])) {
                $idFactura = (int)$_POST['operaciones_partida_id'];
            } elseif (isset($_POST['id'])) {
                $idFactura = (int)$_POST['id'];
            }

            if ($idFactura <= 0) {
                echo json_encode(['ok' => false, 'msg' => 'ID de factura inválido.'], JSON_UNESCAPED_UNICODE);
                exit;
            }



            // Modelo
            $resp = $this->model->bajaFactura($idFactura);

            if (empty($resp) || empty($resp['ok'])) {
                echo json_encode([
                    'ok'  => false,
                    'msg' => $resp['msg'] ?? 'No se pudo dar de baja la factura.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            //registra en bitacora
            $this->registrarBitacoraPartida(
                'op_partida_facturas',
                'baja_logica',
                'op_partida_facturas',
                $idFactura,
                $this->bitacoraOpPartida->desc('factura', 'eliminada', [
                    'factura_id' => $idFactura
                ])
            );
            echo json_encode([
                'ok'  => true,
                'msg' => $resp['msg'] ?? 'Factura dada de baja correctamente.',
                'id_factura' => $idFactura
            ], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (Throwable $e) {
            error_log("Operaciones_por_partida/baja ERROR: " . $e->getMessage());

            echo json_encode([
                'ok'  => false,
                'msg' => 'Ocurrió un error al dar de baja la factura.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }


    //registrar productos
    public function registrarProducto()
    {
        // Seguridad básica
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(["ok" => false, "msg" => "Método no permitido"]);
            exit;
        }

        $factura_id  = isset($_POST['factura_id']) ? (int)$_POST['factura_id'] : 0;
        $item        = isset($_POST['item']) ? trim($_POST['item']) : '';
        $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
        $upc         = isset($_POST['upc']) ? trim($_POST['upc']) : '';
        $marca       = isset($_POST['marca']) ? trim($_POST['marca']) : '';
        $expiracion  = isset($_POST['expiracion']) ? trim($_POST['expiracion']) : null;

        $inner_pack  = isset($_POST['inner_pack']) && $_POST['inner_pack'] !== '' ? (int)$_POST['inner_pack'] : null;
        $case_pack   = isset($_POST['case_pack']) && $_POST['case_pack'] !== '' ? (int)$_POST['case_pack'] : null;

        $pallets_rcv = isset($_POST['pallets_rcv']) ? (int)$_POST['pallets_rcv'] : 0;
        $cajas       = isset($_POST['cajas']) ? (int)$_POST['cajas'] : 0;
        $piezas      = isset($_POST['piezas']) ? (int)$_POST['piezas'] : 0;
        $observaciones = isset($_POST['observaciones']) ? trim((string)$_POST['observaciones']) : '';

        // Validaciones mínimas (en tu tabla: upc es NOT NULL)
        if ($factura_id <= 0) {
            echo json_encode(["ok" => false, "msg" => "Factura inválida"]);
            exit;
        }
        if ($descripcion === '' || $upc === '' || $marca === '') {
            echo json_encode(["ok" => false, "msg" => "Descripción, UPC y Marca son obligatorios"]);
            exit;
        }
        if ($pallets_rcv < 0 || $cajas < 0 || $piezas < 0) {
            echo json_encode(["ok" => false, "msg" => "Valores numéricos inválidos"]);
            exit;
        }

        // (Opcional) Validar formato fecha YYYY-MM-DD si viene
        if ($expiracion !== null && $expiracion !== '') {
            $d = DateTime::createFromFormat('Y-m-d', $expiracion);
            if (!$d || $d->format('Y-m-d') !== $expiracion) {
                echo json_encode(["ok" => false, "msg" => "Expiración inválida"]);
                exit;
            }
        } else {
            $expiracion = null;
        }
        if ($item === '') {
            echo json_encode(["ok" => false, "msg" => "El item es obligatorio"]);
            exit;
        }

        // (Recomendado) Verificar que la factura exista y estatus=1
        $existe = $this->model->existeFacturaActiva($factura_id);
        if (!$existe) {
            echo json_encode(["ok" => false, "msg" => "La factura no existe o está inactiva"]);
            exit;
        }

        $id = $this->model->insertarProductoFactura([
            "factura_id"  => $factura_id,
            "descripcion" => $descripcion,
            "item"        => $item,
            "upc"         => $upc,
            "marca"       => $marca,
            "expiracion"  => $expiracion,
            "inner_pack"  => $inner_pack,
            "case_pack"   => $case_pack,
            "pallets_rcv" => $pallets_rcv,
            "cajas"       => $cajas,
            "piezas"      => $piezas,
            "observaciones" => $observaciones
        ]);

        if ($id > 0) {

            $this->registrarBitacoraPartida(
                'op_partida_productos',
                'crear',
                'op_partida_productos',
                $id,
                $this->bitacoraOpPartida->desc('producto', 'creado', [
                    'producto_id' => $id,
                    'factura_id' => $factura_id,
                    'upc' => $upc,
                    'item' => $item
                ])
            );

            echo json_encode(["ok" => true, "msg" => "Producto registrado", "id" => $id]);
        } else {
            echo json_encode(["ok" => false, "msg" => "No se pudo registrar el producto"]);
        }
        exit;
    }

    public function getProducto()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $idProducto = isset($_GET['id_producto']) ? (int)$_GET['id_producto'] : 0;
            $facturaId  = isset($_GET['factura_id']) ? (int)$_GET['factura_id'] : 0;

            if ($idProducto <= 0 || $facturaId <= 0) {
                echo json_encode(['ok' => false, 'msg' => 'Parámetros inválidos.', 'producto' => null], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $prod = $this->model->getProductoById($idProducto, $facturaId);

            if (!$prod) {
                echo json_encode(['ok' => false, 'msg' => 'Producto no encontrado o no pertenece a la factura.', 'producto' => null], JSON_UNESCAPED_UNICODE);
                exit;
            }

            echo json_encode(['ok' => true, 'producto' => $prod], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (Throwable $e) {
            error_log("Operaciones_por_partida/getProducto ERROR: " . $e->getMessage());
            echo json_encode(['ok' => false, 'msg' => 'Ocurrió un error al obtener el producto.', 'producto' => null], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
    public function actualizarProducto()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['ok' => false, 'msg' => 'Método no permitido.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $idProducto = isset($_POST['id_producto']) ? (int)$_POST['id_producto'] : 0;
            $facturaId  = isset($_POST['factura_id']) ? (int)$_POST['factura_id'] : 0;

            $descripcion = isset($_POST['descripcion']) ? trim((string)$_POST['descripcion']) : '';
            $upc         = isset($_POST['upc']) ? trim((string)$_POST['upc']) : '';
            $marca       = isset($_POST['marca']) ? trim((string)$_POST['marca']) : '';
            $expiracion  = isset($_POST['expiracion']) ? trim((string)$_POST['expiracion']) : null;

            $inner_pack  = isset($_POST['inner_pack']) && $_POST['inner_pack'] !== '' ? (int)$_POST['inner_pack'] : null;
            $case_pack   = isset($_POST['case_pack']) && $_POST['case_pack'] !== '' ? (int)$_POST['case_pack'] : null;

            $pallets_rcv = isset($_POST['pallets_rcv']) ? (int)$_POST['pallets_rcv'] : 0;
            $cajas       = isset($_POST['cajas']) ? (int)$_POST['cajas'] : 0;
            $piezas      = isset($_POST['piezas']) ? (int)$_POST['piezas'] : 0;
            $item        = isset($_POST['item']) ? trim($_POST['item']) : '';
            $observaciones = isset($_POST['observaciones']) ? trim((string)$_POST['observaciones']) : '';

            if ($idProducto <= 0 || $facturaId <= 0) {
                echo json_encode(['ok' => false, 'msg' => 'Producto o factura inválidos.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Validaciones mínimas
            if ($descripcion === '' || $upc === '' || $marca === '') {
                echo json_encode(['ok' => false, 'msg' => 'Descripción, UPC y Marca son obligatorios.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            if ($pallets_rcv < 0 || $cajas < 0 || $piezas < 0) {
                echo json_encode(['ok' => false, 'msg' => 'Valores numéricos inválidos.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // (Opcional) validar fecha
            if ($expiracion !== null && $expiracion !== '') {
                $d = DateTime::createFromFormat('Y-m-d', $expiracion);
                if (!$d || $d->format('Y-m-d') !== $expiracion) {
                    echo json_encode(['ok' => false, 'msg' => 'Expiración inválida.'], JSON_UNESCAPED_UNICODE);
                    exit;
                }
            } else {
                $expiracion = null;
            }

            $resp = $this->model->actualizarProductoFactura($idProducto, $facturaId, [
                'descripcion' => $descripcion,
                'item'        => $item,
                'upc'         => $upc,
                'marca'       => $marca,
                'expiracion'  => $expiracion,
                'inner_pack'  => $inner_pack,
                'case_pack'   => $case_pack,
                'pallets_rcv' => $pallets_rcv,
                'cajas'       => $cajas,
                'piezas'      => $piezas,
                'observaciones' => $observaciones
            ]);
            if (!empty($resp['ok'])) {
                $this->registrarBitacoraPartida(
                    'op_partida_productos',
                    'actualizacion',
                    'op_partida_productos',
                    $idProducto,
                    $this->bitacoraOpPartida->desc('producto', 'actualizado', [
                        'producto_id' => $idProducto,
                        'factura_id' => $facturaId,
                        'upc' => $upc,
                        'item' => $item
                    ])
                );
            }

            echo json_encode($resp, JSON_UNESCAPED_UNICODE);
            exit;
        } catch (Throwable $e) {
            error_log("Operaciones_por_partida/actualizarProducto ERROR: " . $e->getMessage());
            echo json_encode(['ok' => false, 'msg' => 'Ocurrió un error al actualizar el producto.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
    public function guardarProductos()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['ok' => false, 'msg' => 'Método no permitido.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $facturaId = isset($_POST['factura_id']) ? (int)$_POST['factura_id'] : 0;
            $itemsJson = isset($_POST['items']) ? (string)$_POST['items'] : '';

            if ($facturaId <= 0) {
                echo json_encode(['ok' => false, 'msg' => 'Factura inválida.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            if ($itemsJson === '') {
                echo json_encode(['ok' => false, 'msg' => 'Items vacíos.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $items = json_decode($itemsJson, true);
            if (!is_array($items)) {
                echo json_encode(['ok' => false, 'msg' => 'Formato de items inválido (JSON).'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            foreach ($items as &$it) {
                $it['observaciones'] = isset($it['observaciones'])
                    ? trim((string)$it['observaciones'])
                    : '';
            }
            unset($it);
            // Aquí delegas toda la lógica (insert vs update) al MODEL
            $resp = $this->model->guardarProductosFactura($facturaId, $items);

            //registra en bitacora
            if (!empty($resp['ok'])) {
                $this->registrarBitacoraPartida(
                    'op_partida_productos',
                    'actualizacion',
                    'op_partida_productos',
                    $facturaId,
                    $this->bitacoraOpPartida->desc('productos', 'guardados', [
                        'factura_id' => $facturaId,
                        'insertados' => $resp['insertados'] ?? 0,
                        'actualizados' => $resp['actualizados'] ?? 0
                    ])
                );
            }
            echo json_encode($resp, JSON_UNESCAPED_UNICODE);
            exit;
        } catch (Throwable $e) {
            error_log("Operaciones_por_partida/guardarProductos ERROR: " . $e->getMessage());
            echo json_encode(['ok' => false, 'msg' => 'Ocurrió un error al guardar productos.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
    public function bajaProducto()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['ok' => false, 'msg' => 'Método no permitido.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $idProducto = isset($_POST['id_producto']) ? (int)$_POST['id_producto'] : 0;
            $facturaId  = isset($_POST['factura_id']) ? (int)$_POST['factura_id'] : 0;

            if ($idProducto <= 0 || $facturaId <= 0) {
                echo json_encode(['ok' => false, 'msg' => 'Parámetros inválidos.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $resp = $this->model->bajaProductoFactura($idProducto, $facturaId);

            // (Opcional) devolver totales ya actualizados para refrescar UI sin relistar
            if (!empty($resp['ok'])) {
                $this->registrarBitacoraPartida(
                    'op_partida_productos',
                    'baja_logica',
                    'op_partida_productos',
                    $idProducto,
                    $this->bitacoraOpPartida->desc('producto', 'eliminado', [
                        'producto_id' => $idProducto,
                        'factura_id' => $facturaId
                    ])
                );

                $resp['totals'] = $this->model->getTotalesProductosFactura($facturaId);
            }

            echo json_encode($resp, JSON_UNESCAPED_UNICODE);
            exit;
        } catch (Throwable $e) {
            error_log("Operaciones_por_partida/bajaProducto ERROR: " . $e->getMessage());
            echo json_encode(['ok' => false, 'msg' => 'Ocurrió un error al dar de baja el producto.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }


    //DOCS
    public function sugerirFacturasDocs()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $term  = isset($_GET['term']) ? trim((string)$_GET['term']) : '';
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

            if ($limit < 1) $limit = 10;
            if ($limit > 25) $limit = 25; // tope razonable para sugerencias

            if ($term === '' || mb_strlen($term) < 2) {
                echo json_encode([
                    'ok' => true,
                    'data' => []
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Ideal: método dedicado en el MODEL para sugerencias (rápido y con LIMIT)
            // Ej: $rows = $this->model->sugerirFacturas($term, $limit);
            $rows = $this->model->sugerirFacturas($term, $limit);

            echo json_encode([
                'ok' => true,
                'data' => $rows ?: []
            ], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (Throwable $e) {
            error_log("Operaciones_por_partida/sugerirFacturasDocs ERROR: " . $e->getMessage());
            echo json_encode([
                'ok' => false,
                'msg' => 'Ocurrió un error al buscar facturas.',
                'data' => []
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }


    public function listarDocumentosFactura()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $facturaId = isset($_GET['factura_id']) ? (int)$_GET['factura_id'] : 0;
            $term      = isset($_GET['term']) ? trim((string)$_GET['term']) : '';

            if ($facturaId <= 0) {
                echo json_encode([
                    'ok'   => false,
                    'msg'  => 'Factura inválida.',
                    'data' => []
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Si manejas estatus, puedes fijarlo aquí (1=activo)
            $rows = $this->model->listarPorFactura($facturaId, $term, 1);

            echo json_encode([
                'ok'   => true,
                'data' => $rows ?: []
            ], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (Throwable $e) {
            error_log("Operaciones_por_partida/listarDocumentosFactura ERROR: " . $e->getMessage());
            echo json_encode([
                'ok'   => false,
                'msg'  => 'Ocurrió un error al listar documentos.',
                'data' => []
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }


    public function listarTiposDocumentoOPP()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $rows = $this->model->listarTiposDocumentoOPP();

            echo json_encode([
                'ok'   => true,
                'data' => $rows ?: []
            ], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (Throwable $e) {
            error_log("Operaciones_por_partida/listarTiposDocumentoOPP ERROR: " . $e->getMessage());
            echo json_encode([
                'ok'   => false,
                'msg'  => 'Ocurrió un error al listar tipos de documento.',
                'data' => []
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
    public function getFacturaHeaderDocs()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $facturaId = isset($_GET['factura_id']) ? (int)$_GET['factura_id'] : 0;

            if ($facturaId <= 0) {
                echo json_encode([
                    'ok' => false,
                    'msg' => 'Factura inválida.',
                    'factura' => null
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $factura = $this->model->obtenerFactura($facturaId);

            if (!$factura) {
                echo json_encode([
                    'ok' => false,
                    'msg' => 'Factura no encontrada.',
                    'factura' => null
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            echo json_encode([
                'ok' => true,
                'factura' => $factura
            ], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (Throwable $e) {
            error_log("Operaciones_por_partida/getFacturaHeaderDocs ERROR: " . $e->getMessage());
            echo json_encode([
                'ok' => false,
                'msg' => 'Ocurrió un error al obtener la factura.',
                'factura' => null
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }


    private function registrarOPPDocumentos()
    {
        try {
            // ===== Inputs =====
            $factura_id  = (int)($_POST['factura_id'] ?? 0);
            $tipo_doc_id = (int)($_POST['tipo_documento_id'] ?? 0);
            $notas       = trim((string)($_POST['notas'] ?? ''));

            if ($factura_id <= 0 || $tipo_doc_id <= 0) {
                echo json_encode(['status' => 'warning', 'msg' => 'Datos inválidos (factura/tipo documento)']);
                return;
            }

            // ===== Validar factura =====
            if (!$this->model->existeFacturaActiva($factura_id)) {
                echo json_encode(['status' => 'warning', 'msg' => 'La factura no existe o está inactiva']);
                return;
            }

            // ===== Validar tipo documento (OPP) =====
            if (!$this->model->existeTipoDocumentoOPP($tipo_doc_id)) {
                echo json_encode(['status' => 'warning', 'msg' => 'Tipo de documento no válido para Operaciones por Partida']);
                return;
            }

            // ===== Files: soporta multiple ("files") y fallback ("archivo") =====
            $filesKey = null;

            if (isset($_FILES['files']) && !empty($_FILES['files']['name'])) {
                $filesKey = 'files';
            } elseif (isset($_FILES['archivo']) && !empty($_FILES['archivo']['name'])) {
                $filesKey = 'archivo';
            }

            if ($filesKey === null) {
                echo json_encode(['status' => 'warning', 'msg' => 'Archivo(s) requerido(s)']);
                return;
            }


            // Normaliza a arreglo
            $f = $_FILES[$filesKey];

            $names = is_array($f['name']) ? $f['name'] : [$f['name']];
            $tmps  = is_array($f['tmp_name']) ? $f['tmp_name'] : [$f['tmp_name']];
            $errs  = is_array($f['error']) ? $f['error'] : [$f['error']];
            $sizes = is_array($f['size']) ? $f['size'] : [$f['size']];
            $types = is_array($f['type']) ? $f['type'] : [$f['type']];

            if (empty($names) || empty($tmps) || empty($tmps[0])) {
                echo json_encode(['status' => 'warning', 'msg' => 'Archivo(s) requerido(s)']);
                return;
            }

            // ===== Obtener numero_factura (para carpeta) =====
            $fac = $this->model->obtenerFactura($factura_id);
            if (!$fac || empty($fac['numero_factura'])) {
                echo json_encode(['status' => 'warning', 'msg' => 'Factura no encontrada']);
                return;
            }
            $numFactura = (string)$fac['numero_factura'];

            $root = $this->getProjectRootPath();
            $baseDocs = $root . DIRECTORY_SEPARATOR . 'Documents' . DIRECTORY_SEPARATOR . 'DocumentosPartidas';



            // slugFolder ya la usas en FO, la reutilizamos.
            $facFolder = $this->slugFolder($numFactura);

            $absPath = $baseDocs . DIRECTORY_SEPARATOR . $facFolder;

            if (!is_dir($absPath) && !@mkdir($absPath, 0775, true)) {
                echo json_encode(['status' => 'error', 'msg' => 'No se pudo crear la carpeta de destino']);
                return;
            }

            // ===== Validaciones de archivo =====
            $permit  = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip', 'webp'];
            $maxBytes = 50 * 1024 * 1024; // 50MB (igual que tu FO)

            // ===== Insert BD por archivo =====
            $userId = $_SESSION['id_usuario'] ?? $_SESSION['usuario_id'] ?? $_SESSION['id'] ?? $_SESSION['admin_id'] ?? null;
            if ($userId === null) {
                error_log('OPP_DOCS_REGISTRAR sin userId.');
            }

            $insertados = 0;
            $fallidos   = 0;
            $errores    = [];

            for ($i = 0; $i < count($names); $i++) {

                $orig = (string)$names[$i];
                $tmp  = (string)$tmps[$i];
                $err  = (int)$errs[$i];
                $size = (int)$sizes[$i];
                $type = (string)($types[$i] ?? '');

                if ($err !== UPLOAD_ERR_OK) {
                    $fallidos++;
                    $errores[] = ['archivo' => $orig, 'msg' => "Error de upload (code $err)"];
                    continue;
                }
                if (!is_uploaded_file($tmp)) {
                    $fallidos++;
                    $errores[] = ['archivo' => $orig, 'msg' => 'Archivo temporal inválido'];
                    continue;
                }

                $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
                if (!in_array($ext, $permit, true)) {
                    $fallidos++;
                    $errores[] = ['archivo' => $orig, 'msg' => 'Extensión no permitida'];
                    continue;
                }
                if ($size <= 0 || $size > $maxBytes) {
                    $fallidos++;
                    $errores[] = ['archivo' => $orig, 'msg' => 'Tamaño inválido o excede el límite (50MB)'];
                    continue;
                }

                // Mime real (igual que FO)
                $mime = function_exists('mime_content_type')
                    ? (mime_content_type($tmp) ?: ($type ?: 'application/octet-stream'))
                    : ($type ?: 'application/octet-stream');

                // Nombre final
                $uuid     = bin2hex(random_bytes(8));
                $sanOrig  = preg_replace('/[^A-Za-z0-9_.-]/', '_', $orig);
                $fileName = $uuid . '_' . $sanOrig;

                $destAbs = $absPath . DIRECTORY_SEPARATOR . $fileName;

                if (!move_uploaded_file($tmp, $destAbs)) {
                    $fallidos++;
                    $errores[] = ['archivo' => $orig, 'msg' => 'No se pudo guardar el archivo'];
                    continue;
                }

                // Hash opcional (si tu tabla NO lo usa, no pasa nada; no lo insertamos)
                $hash = @hash_file('sha256', $destAbs) ?: null;

                // Ruta relativa para BD (y servirlo por web)
                // Importante: usa rutas con / para URL
                $rutaRel = 'Documents/DocumentosPartidas/' . $facFolder . '/' . $fileName;

                // Insert BD
                $newId = $this->model->insertarDocumentoPartida([
                    'factura_id'        => $factura_id,
                    'tipo_documento_id' => $tipo_doc_id,
                    'nombre_archivo'    => $orig,      // nombre original (recomendado)
                    'ruta_archivo'      => $rutaRel,    // ruta relativa
                    'mime_type'         => $mime,
                    'tamano_bytes'      => $size,
                    'notas'             => ($notas !== '' ? $notas : null),
                    'subido_por'        => $userId,
                    // 'hash' => $hash, // solo si tu tabla lo tiene
                ]);

                if ($newId <= 0) {
                    // Si falla BD, opcional: borrar archivo físico para no dejar basura
                    @unlink($destAbs);
                    $fallidos++;
                    $errores[] = ['archivo' => $orig, 'msg' => 'No se pudo registrar en BD'];
                    continue;
                }

                $insertados++;
            }

            if ($insertados <= 0) {
                echo json_encode([
                    'status' => 'error',
                    'msg'    => 'No se pudo subir ningún documento',
                    'errors' => $errores
                ]);
                return;
            }
            $this->registrarBitacoraPartida(
                'op_partida_documentos',
                'crear',
                'op_partida_documentos',
                $factura_id,
                $this->bitacoraOpPartida->desc('documentos', 'subidos', [
                    'factura_id' => $factura_id,
                    'insertados' => $insertados,
                    'fallidos' => $fallidos
                ])
            );
            echo json_encode([
                'status'     => 'success',
                'msg'        => 'Documento(s) subido(s) correctamente',
                'insertados' => $insertados,
                'fallidos'   => $fallidos,
                'errors'     => $errores,
                'factura'    => $numFactura,
                'folder'     => 'Documents/DocumentosPartidas/' . $facFolder . '/'
            ]);
        } catch (Throwable $e) {
            error_log("OPP_DOCS_REGISTRAR: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'msg' => 'Error inesperado']);
        }
    }
    private function slugFolder($text)
    {
        // Reemplaza espacios y caracteres no permitidos por guiones bajos
        $text = preg_replace('/[^\w]+/', '_', $text);
        // Elimina guiones bajos al inicio y final
        $text = trim($text, '_');
        // Convierte a minúsculas
        $text = strtolower($text);
        return $text;
    }

    private function getProjectRootPath(): string
    {
        if (defined('UPLOAD_ROOT')) {
            return rtrim((string) constant('UPLOAD_ROOT'), "/\\");
        }
        return rtrim(dirname(__DIR__, 2), "/\\");
    }


    public function registrarDocumentosFactura()
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'msg' => 'Método no permitido.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Reusa tu implementación privada (la lógica pesada vive ahí)
        $this->registrarOPPDocumentos();
        exit;
    }
    public function eliminarDocumentoFactura()
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'msg' => 'Método no permitido.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        try {
            $idDocumento = (int)($_POST['id_documento'] ?? 0);
            if ($idDocumento <= 0) {
                echo json_encode(['status' => 'warning', 'msg' => 'ID de documento inválido.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // 1) Traer documento de BD
            $doc = $this->model->getDocumentoPartidaById($idDocumento);
            if (!$doc) {
                echo json_encode(['status' => 'warning', 'msg' => 'Documento no encontrado.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $rutaRel = (string)($doc['ruta_archivo'] ?? '');
            if ($rutaRel === '') {
                echo json_encode(['status' => 'error', 'msg' => 'Ruta de documento inválida en BD.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // 2) Seguridad: evitar path traversal / rutas fuera del directorio esperado
            $rutaRelNorm = str_replace('\\', '/', $rutaRel);
            $prefix = 'Documents/DocumentosPartidas/';
            if (strpos($rutaRelNorm, $prefix) !== 0) {
                echo json_encode(['status' => 'error', 'msg' => 'Ruta no permitida.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // 3) Armar ruta absoluta del archivo
            $root = $this->getProjectRootPath();
            $absFile = rtrim($root, "/\\") . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rutaRelNorm);

            // 4) Borrar archivo físico (si existe)
            $fileDeleted = true;
            if (file_exists($absFile)) {
                $fileDeleted = @unlink($absFile);
            }

            // 5) Borrar registro BD
            $okDb = $this->model->eliminarDocumentoPartida($idDocumento);
            if (!$okDb) {
                // Si BD falla pero borraste archivo, te quedaría inconsistente.
                // En este punto es preferible reportar error.
                echo json_encode(['status' => 'error', 'msg' => 'No se pudo eliminar el registro en BD.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // 6) Si ya no hay archivos en la carpeta de la factura, eliminar carpeta
            //    Carpeta = dirname del archivo
            $folderAbs = dirname($absFile);

            $folderDeleted = false;
            if (is_dir($folderAbs)) {
                $items = @scandir($folderAbs);
                if (is_array($items)) {
                    // Solo . y ..
                    if (count($items) <= 2) {
                        $folderDeleted = @rmdir($folderAbs);
                    }
                }
            }
            $this->registrarBitacoraPartida(
                'op_partida_documentos',
                'baja_logica',
                'op_partida_documentos',
                $idDocumento,
                $this->bitacoraOpPartida->desc('documento', 'eliminado', [
                    'documento_id' => $idDocumento,
                    'factura_id' => (int)($doc['factura_id'] ?? 0),
                    'nombre_archivo' => (string)($doc['nombre_archivo'] ?? '')
                ])
            );
            echo json_encode([
                'status'        => 'success',
                'msg'           => 'Documento eliminado correctamente.',
                'id_documento'  => $idDocumento,
                'archivo_borrado' => (bool)$fileDeleted,
                'carpeta_borrada' => (bool)$folderDeleted
            ], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (Throwable $e) {
            error_log("OPP_DOCS_ELIMINAR: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'msg' => 'Error inesperado al eliminar.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
    public function subirFotoProducto()
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['ok' => false, 'msg' => 'Método no permitido.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        try {
            $productoId = isset($_POST['producto_id']) ? (int)$_POST['producto_id'] : 0;
            $facturaId  = isset($_POST['factura_id'])  ? (int)$_POST['factura_id']  : 0;
            $orden      = isset($_POST['orden'])        ? (int)$_POST['orden']        : 0;

            // Validaciones básicas
            if ($productoId <= 0 || $facturaId <= 0) {
                echo json_encode(['ok' => false, 'msg' => 'Producto o factura inválidos.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            if ($orden < 1 || $orden > 3) {
                echo json_encode(['ok' => false, 'msg' => 'La posición de la foto debe ser 1, 2 o 3.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['ok' => false, 'msg' => 'No se recibió ninguna foto o hubo un error en la subida.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Validar que el producto exista y pertenezca a la factura
            $prod = $this->model->getProductoById($productoId, $facturaId);
            if (!$prod) {
                echo json_encode(['ok' => false, 'msg' => 'El producto no existe o no pertenece a la factura.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $file     = $_FILES['foto'];
            $origName = (string)$file['name'];
            $tmpPath  = (string)$file['tmp_name'];
            $size     = (int)$file['size'];

            // Validar extensión
            $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            $allowed  = ['jpg', 'jpeg', 'png', 'webp'];
            if (!in_array($ext, $allowed, true)) {
                echo json_encode(['ok' => false, 'msg' => 'Solo se permiten imágenes JPG, PNG o WEBP.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Validar tamaño (5MB máx)
            $maxBytes = 5 * 1024 * 1024;
            if ($size <= 0 || $size > $maxBytes) {
                echo json_encode(['ok' => false, 'msg' => 'La imagen no debe superar 5MB.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            if (!is_uploaded_file($tmpPath)) {
                echo json_encode(['ok' => false, 'msg' => 'Archivo temporal inválido.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // MIME real
            $mime = function_exists('mime_content_type')
                ? (mime_content_type($tmpPath) ?: 'image/jpeg')
                : 'image/jpeg';

            // Construir ruta destino
            $root    = $this->getProjectRootPath();
            $baseDir = $root . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR
                . 'operaciones_partida' . DIRECTORY_SEPARATOR . 'fotos'
                . DIRECTORY_SEPARATOR . $facturaId
                . DIRECTORY_SEPARATOR . $productoId;

            if (!is_dir($baseDir) && !@mkdir($baseDir, 0775, true)) {
                echo json_encode(['ok' => false, 'msg' => 'No se pudo crear el directorio de destino.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Nombre final con UUID para evitar colisiones
            $uuid     = bin2hex(random_bytes(8));
            $fileName = $uuid . '_foto' . $orden . '.' . $ext;
            $destAbs  = $baseDir . DIRECTORY_SEPARATOR . $fileName;
            $rutaRel  = 'uploads/operaciones_partida/fotos/' . $facturaId . '/' . $productoId . '/' . $fileName;

            // Si ya hay foto en esa posición, borrar el archivo físico anterior
            $fotoExistente = $this->model->getFotosByProducto($productoId);
            foreach ($fotoExistente as $fe) {
                if ((int)$fe['orden'] === $orden) {
                    $absAnterior = $root . DIRECTORY_SEPARATOR
                        . str_replace('/', DIRECTORY_SEPARATOR, $fe['ruta_archivo']);
                    if (file_exists($absAnterior)) {
                        @unlink($absAnterior);
                    }
                    break;
                }
            }

            // Mover archivo
            if (!move_uploaded_file($tmpPath, $destAbs)) {
                echo json_encode(['ok' => false, 'msg' => 'No se pudo guardar la imagen.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Guardar en BD (upsert por producto_id + orden)
            $userId = $_SESSION['id_usuario'] ?? null;

            $idFoto = $this->model->upsertFotoProducto([
                'producto_id'    => $productoId,
                'factura_id'     => $facturaId,
                'orden'          => $orden,
                'nombre_archivo' => $origName,
                'ruta_archivo'   => $rutaRel,
                'mime_type'      => $mime,
                'tamano_bytes'   => $size,
                'subido_por'     => $userId
            ]);

            if ($idFoto <= 0) {
                // Si BD falla, limpiar archivo físico
                @unlink($destAbs);
                echo json_encode(['ok' => false, 'msg' => 'No se pudo registrar la foto en la base de datos.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $this->registrarBitacoraPartida(
                'op_partida_evidencias',
                'subir_imagen',
                'op_partida_producto_fotos',
                $idFoto,
                $this->bitacoraOpPartida->desc('foto_producto', 'subida', [
                    'foto_id' => $idFoto,
                    'producto_id' => $productoId,
                    'factura_id' => $facturaId,
                    'orden' => $orden
                ])
            );
            echo json_encode([
                'ok'          => true,
                'msg'         => 'Foto subida correctamente.',
                'id_foto'     => $idFoto,
                'orden'       => $orden,
                'ruta_archivo' => $rutaRel,
                'nombre_archivo' => $origName
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            error_log("Operaciones_por_partida/subirFotoProducto ERROR: " . $e->getMessage());
            echo json_encode(['ok' => false, 'msg' => 'Ocurrió un error al subir la foto.'], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    public function eliminarFotoProducto()
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['ok' => false, 'msg' => 'Método no permitido.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        try {
            $idFoto = isset($_POST['id_foto']) ? (int)$_POST['id_foto'] : 0;

            if ($idFoto <= 0) {
                echo json_encode(['ok' => false, 'msg' => 'ID de foto inválido.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Buscar foto en BD para obtener la ruta
            $foto = $this->model->getFotoById($idFoto);
            if (!$foto) {
                echo json_encode(['ok' => false, 'msg' => 'Foto no encontrada.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Seguridad: verificar que la ruta sea del directorio esperado
            $rutaRel = str_replace('\\', '/', (string)($foto['ruta_archivo'] ?? ''));
            if (strpos($rutaRel, 'uploads/operaciones_partida/fotos/') !== 0) {
                echo json_encode(['ok' => false, 'msg' => 'Ruta no permitida.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Borrar archivo físico
            $root    = $this->getProjectRootPath();
            $absFile = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rutaRel);

            if (file_exists($absFile)) {
                @unlink($absFile);
            }

            // Borrar registro de BD
            $ok = $this->model->eliminarFotoProducto($idFoto);
            if (!$ok) {
                echo json_encode(['ok' => false, 'msg' => 'No se pudo eliminar el registro en la base de datos.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $this->registrarBitacoraPartida(
                'op_partida_evidencias',
                'eliminar_imagen',
                'op_partida_producto_fotos',
                $idFoto,
                $this->bitacoraOpPartida->desc('foto_producto', 'eliminada', [
                    'foto_id' => $idFoto,
                    'producto_id' => (int)$foto['producto_id'],
                    'factura_id' => (int)$foto['factura_id'],
                    'orden' => (int)$foto['orden']
                ])
            );
            echo json_encode([
                'ok'         => true,
                'msg'        => 'Foto eliminada correctamente.',
                'id_foto'    => $idFoto,
                'producto_id' => (int)$foto['producto_id'],
                'orden'      => (int)$foto['orden']
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            error_log("Operaciones_por_partida/eliminarFotoProducto ERROR: " . $e->getMessage());
            echo json_encode(['ok' => false, 'msg' => 'Ocurrió un error al eliminar la foto.'], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    public function getFotosProducto()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $productoId = isset($_GET['producto_id']) ? (int)$_GET['producto_id'] : 0;
            $facturaId  = isset($_GET['factura_id'])  ? (int)$_GET['factura_id']  : 0;

            if ($productoId <= 0 || $facturaId <= 0) {
                echo json_encode(['ok' => false, 'msg' => 'Parámetros inválidos.', 'fotos' => []], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Validar que el producto pertenezca a la factura
            $prod = $this->model->getProductoById($productoId, $facturaId);
            if (!$prod) {
                echo json_encode(['ok' => false, 'msg' => 'Producto no encontrado.', 'fotos' => []], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $fotos = $this->model->getFotosByProducto($productoId);

            echo json_encode([
                'ok'    => true,
                'fotos' => $fotos
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            error_log("Operaciones_por_partida/getFotosProducto ERROR: " . $e->getMessage());
            echo json_encode(['ok' => false, 'msg' => 'Ocurrió un error al obtener las fotos.', 'fotos' => []], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
}
