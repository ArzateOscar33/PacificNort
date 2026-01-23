<?php
class Operaciones_por_partida extends Controller
{
    public function __construct()
    {
        parent::__construct();
        session_start();

        if (empty($_SESSION['nombre_usuario'])) {
            header('Location: ' . BASE_URL . 'admin');
            exit;
        }
    }

    public function index()
    {
        $data['title'] = 'Operaciones por Partida';
        $data['bodegas'] = $this->model->listarBodegasActivas();

        // Si en tu vista el filtro de bodegas se llena con PHP:
        // $data['bodegas'] = $this->model->getBodegasActivas();  (opcional, después)
        $this->views->getView('admin/Operaciones_por_partida', "ver", $data);
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
            $allowed = [10, 25, 50, 100];
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
                    'total' => 0, 'page' => 1, 'per_page' => 10, 'total_pages' => 0
                ]
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }


    public function listarProductos()
{
    header('Content-Type: application/json; charset=utf-8');

    try {
        // ===== Inputs (GET) =====
        $facturaId = isset($_GET['factura_id']) ? (int)$_GET['factura_id'] : 0;
        $term      = isset($_GET['term']) ? trim($_GET['term']) : '';

        $page      = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage   = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 50;

        if ($facturaId <= 0) {
            echo json_encode([
                'ok'   => false,
                'msg'  => 'Factura inválida.',
                'data' => [],
                'meta' => ['total' => 0, 'page' => 1, 'per_page' => 50, 'total_pages' => 0],
                'totals' => ['total_cajas' => 0, 'total_piezas' => 0, 'total_pallets_inv' => 0]
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($page < 1) $page = 1;
        if ($perPage < 1) $perPage = 50;

        // Limitar per_page (modal)
        $allowed = [10, 25, 50, 100, 200];
        if (!in_array($perPage, $allowed, true)) $perPage = 50;

        // ===== Modelo =====
        $result = $this->model->listarProductos($facturaId, [
            'term'     => $term,
            'page'     => $page,
            'per_page' => $perPage
        ]);

        $rows   = $result['rows'] ?? [];
        $total  = (int)($result['total'] ?? 0);
        $totals = $result['totals'] ?? [
            'total_cajas' => 0,
            'total_piezas' => 0,
            'total_pallets_rcv' => 0
        ];

        $totalPages = $perPage > 0 ? (int)ceil($total / $perPage) : 0;

        echo json_encode([
            'ok'     => true,
            'data'   => $rows,
            'meta'   => [
                'total'       => $total,
                'page'        => $page,
                'per_page'    => $perPage,
                'total_pages' => $totalPages
            ],
            'totals' => [
                'total_cajas'       => (int)($totals['total_cajas'] ?? 0),
                'total_piezas'      => (int)($totals['total_piezas'] ?? 0),
                'total_pallets_rcv' => (int)($totals['total_pallets_rcv'] ?? 0),
            ]
        ], JSON_UNESCAPED_UNICODE);

    } catch (Throwable $e) {
        error_log("Operaciones_por_partida/listarProductos ERROR: " . $e->getMessage());

        echo json_encode([
            'ok'   => false,
            'msg'  => 'Ocurrió un error al listar productos.',
            'data' => [],
            'meta' => ['total' => 0, 'page' => 1, 'per_page' => 50, 'total_pages' => 0],
            'totals' => ['total_cajas' => 0, 'total_piezas' => 0, 'total_pallets_rcv' => 0]
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

        // ====== Inputs (POST) desde tu modal ======
        // IDs/vista:
        // - operaciones_partida_bodega (select)
        // - operaciones_partida_factura (input)
        // - operaciones_partida_proveedor (input)
        // - operaciones_partida_revision (checkbox)
        // - operaciones_partida_pallets_inv (number)
        // - operaciones_partida_fechaRecibido (date)
        // - operaciones_partida_notas (text)

        $bodegaId      = isset($_POST['operaciones_partida_bodega']) ? (int)$_POST['operaciones_partida_bodega'] : 0;
        $numeroFactura = isset($_POST['invoice_number']) ? trim((string)$_POST['invoice_number']) : (isset($_POST['operaciones_partida_factura']) ? trim((string)$_POST['operaciones_partida_factura']) : '');
        $proveedor     = isset($_POST['vendor_name']) ? trim((string)$_POST['vendor_name']) : (isset($_POST['operaciones_partida_proveedor']) ? trim((string)$_POST['operaciones_partida_proveedor']) : '');

        // checkbox switch: puede venir "on", "1", etc.
        $revisionPasa  = isset($_POST['revision_pasa']) ? 1 : 0;

        $palletsInv    = isset($_POST['pallets_inv']) ? (int)$_POST['pallets_inv'] : (isset($_POST['operaciones_partida_pallets_inv']) ? (int)$_POST['operaciones_partida_pallets_inv'] : 0);

        $fechaRecibido = isset($_POST['received_date']) ? trim((string)$_POST['received_date']) : (isset($_POST['operaciones_partida_fechaRecibido']) ? trim((string)$_POST['operaciones_partida_fechaRecibido']) : '');
        $notas         = isset($_POST['comentarios']) ? trim((string)$_POST['comentarios']) : (isset($_POST['operaciones_partida_notas']) ? trim((string)$_POST['operaciones_partida_notas']) : '');

        // ====== Sesión: id usuario (FK: usuarios.id_usuario, ON DELETE SET NULL) ======
        // Ajusta el nombre exacto de tu sesión si difiere.
        $creadoPor = isset($_SESSION['id_usuario']) ? (int)$_SESSION['id_usuario'] : null;

        // ====== Modelo ======
        $resp = $this->model->registrarFactura([
            'bodega_id'      => $bodegaId,
            'numero_factura' => $numeroFactura,
            'proveedor'      => $proveedor,
            'revision_pasa'  => $revisionPasa,
            'pallets_inv'    => $palletsInv,
            'fecha_recibido' => $fechaRecibido,
            'notas'          => $notas,
            'creado_por'     => $creadoPor
        ]);

        if (empty($resp) || empty($resp['ok'])) {
            echo json_encode([
                'ok'        => false,
                'msg'       => $resp['msg'] ?? 'No se pudo registrar la factura.',
                'id_factura'=> $resp['id_factura'] ?? null
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // (Opcional) traer la factura recién creada para refrescar UI
        $idFactura = (int)$resp['id_factura'];
        $factura   = $this->model->getFacturaById($idFactura);

        echo json_encode([
            'ok'        => true,
            'msg'       => $resp['msg'] ?? 'Factura registrada correctamente.',
            'id_factura'=> $idFactura,
            'factura'   => $factura ?: null
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


//
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

        // OJO: usa el método “para editar” (con fecha formateada)
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
            echo json_encode(['ok' => false, 'msg' => 'Método no permitido.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $idFactura = isset($_POST['operaciones_partida_id']) ? (int)$_POST['operaciones_partida_id'] : 0;
        if ($idFactura <= 0) {
            echo json_encode(['ok' => false, 'msg' => 'ID de factura inválido.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $bodegaId      = isset($_POST['operaciones_partida_bodega']) ? (int)$_POST['operaciones_partida_bodega'] : 0;
        $numeroFactura = isset($_POST['invoice_number']) ? trim((string)$_POST['invoice_number']) : '';
        $proveedor     = isset($_POST['vendor_name']) ? trim((string)$_POST['vendor_name']) : '';
        $revisionPasa  = isset($_POST['revision_pasa']) ? 1 : 0;
        $palletsInv    = isset($_POST['pallets_inv']) ? (int)$_POST['pallets_inv'] : 0;
        $fechaRecibido = isset($_POST['received_date']) ? trim((string)$_POST['received_date']) : '';
        $notas         = isset($_POST['comentarios']) ? trim((string)$_POST['comentarios']) : '';

        $actualizadoPor = isset($_SESSION['id_usuario']) ? (int)$_SESSION['id_usuario'] : null;

        $resp = $this->model->actualizarFactura($idFactura, [
            'bodega_id'       => $bodegaId,
            'numero_factura'  => $numeroFactura,
            'proveedor'       => $proveedor,
            'revision_pasa'   => $revisionPasa,
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

        // Opcional: devolver factura actualizada para refrescar tabla sin recargar
        $factura = $this->model->getFacturaById($idFactura);

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

    $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
    $upc         = isset($_POST['upc']) ? trim($_POST['upc']) : '';
    $marca       = isset($_POST['marca']) ? trim($_POST['marca']) : '';
    $expiracion  = isset($_POST['expiracion']) ? trim($_POST['expiracion']) : null;

    $inner_pack  = isset($_POST['inner_pack']) && $_POST['inner_pack'] !== '' ? (int)$_POST['inner_pack'] : null;
    $case_pack   = isset($_POST['case_pack']) && $_POST['case_pack'] !== '' ? (int)$_POST['case_pack'] : null;

    $pallets_rcv = isset($_POST['pallets_rcv']) ? (int)$_POST['pallets_rcv'] : 0;
    $cajas       = isset($_POST['cajas']) ? (int)$_POST['cajas'] : 0;
    $piezas      = isset($_POST['piezas']) ? (int)$_POST['piezas'] : 0;

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

    // (Recomendado) Verificar que la factura exista y estatus=1
    $existe = $this->model->existeFacturaActiva($factura_id);
    if (!$existe) {
        echo json_encode(["ok" => false, "msg" => "La factura no existe o está inactiva"]);
        exit;
    }

    $id = $this->model->insertarProductoFactura([
        "factura_id"  => $factura_id,
        "descripcion" => $descripcion,
        "upc"         => $upc,
        "marca"       => $marca,
        "expiracion"  => $expiracion,
        "inner_pack"  => $inner_pack,
        "case_pack"   => $case_pack,
        "pallets_rcv" => $pallets_rcv,
        "cajas"       => $cajas,
        "piezas"      => $piezas
    ]);

    if ($id > 0) {
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
            echo json_encode(['ok'=>false,'msg'=>'Parámetros inválidos.','producto'=>null], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $prod = $this->model->getProductoById($idProducto, $facturaId);

        if (!$prod) {
            echo json_encode(['ok'=>false,'msg'=>'Producto no encontrado o no pertenece a la factura.','producto'=>null], JSON_UNESCAPED_UNICODE);
            exit;
        }

        echo json_encode(['ok'=>true,'producto'=>$prod], JSON_UNESCAPED_UNICODE);
        exit;

    } catch (Throwable $e) {
        error_log("Operaciones_por_partida/getProducto ERROR: " . $e->getMessage());
        echo json_encode(['ok'=>false,'msg'=>'Ocurrió un error al obtener el producto.','producto'=>null], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
public function actualizarProducto()
{
    header('Content-Type: application/json; charset=utf-8');

    try {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['ok'=>false,'msg'=>'Método no permitido.'], JSON_UNESCAPED_UNICODE);
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

        if ($idProducto <= 0 || $facturaId <= 0) {
            echo json_encode(['ok'=>false,'msg'=>'Producto o factura inválidos.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Validaciones mínimas
        if ($descripcion === '' || $upc === '' || $marca === '') {
            echo json_encode(['ok'=>false,'msg'=>'Descripción, UPC y Marca son obligatorios.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if ($pallets_rcv < 0 || $cajas < 0 || $piezas < 0) {
            echo json_encode(['ok'=>false,'msg'=>'Valores numéricos inválidos.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // (Opcional) validar fecha
        if ($expiracion !== null && $expiracion !== '') {
            $d = DateTime::createFromFormat('Y-m-d', $expiracion);
            if (!$d || $d->format('Y-m-d') !== $expiracion) {
                echo json_encode(['ok'=>false,'msg'=>'Expiración inválida.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
        } else {
            $expiracion = null;
        }

        $resp = $this->model->actualizarProductoFactura($idProducto, $facturaId, [
            'descripcion' => $descripcion,
            'upc'         => $upc,
            'marca'       => $marca,
            'expiracion'  => $expiracion,
            'inner_pack'  => $inner_pack,
            'case_pack'   => $case_pack,
            'pallets_rcv' => $pallets_rcv,
            'cajas'       => $cajas,
            'piezas'      => $piezas
        ]);

        echo json_encode($resp, JSON_UNESCAPED_UNICODE);
        exit;

    } catch (Throwable $e) {
        error_log("Operaciones_por_partida/actualizarProducto ERROR: " . $e->getMessage());
        echo json_encode(['ok'=>false,'msg'=>'Ocurrió un error al actualizar el producto.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
public function guardarProductos()
{
    header('Content-Type: application/json; charset=utf-8');

    try {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['ok'=>false,'msg'=>'Método no permitido.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $facturaId = isset($_POST['factura_id']) ? (int)$_POST['factura_id'] : 0;
        $itemsJson = isset($_POST['items']) ? (string)$_POST['items'] : '';

        if ($facturaId <= 0) {
            echo json_encode(['ok'=>false,'msg'=>'Factura inválida.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if ($itemsJson === '') {
            echo json_encode(['ok'=>false,'msg'=>'Items vacíos.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $items = json_decode($itemsJson, true);
        if (!is_array($items)) {
            echo json_encode(['ok'=>false,'msg'=>'Formato de items inválido (JSON).'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Aquí delegas toda la lógica (insert vs update) al MODEL
        $resp = $this->model->guardarProductosFactura($facturaId, $items);

        echo json_encode($resp, JSON_UNESCAPED_UNICODE);
        exit;

    } catch (Throwable $e) {
        error_log("Operaciones_por_partida/guardarProductos ERROR: " . $e->getMessage());
        echo json_encode(['ok'=>false,'msg'=>'Ocurrió un error al guardar productos.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
public function bajaProducto()
{
    header('Content-Type: application/json; charset=utf-8');

    try {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['ok'=>false,'msg'=>'Método no permitido.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $idProducto = isset($_POST['id_producto']) ? (int)$_POST['id_producto'] : 0;
        $facturaId  = isset($_POST['factura_id']) ? (int)$_POST['factura_id'] : 0;

        if ($idProducto <= 0 || $facturaId <= 0) {
            echo json_encode(['ok'=>false,'msg'=>'Parámetros inválidos.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $resp = $this->model->bajaProductoFactura($idProducto, $facturaId);

        // (Opcional) devolver totales ya actualizados para refrescar UI sin relistar
        if (!empty($resp['ok'])) {
            $resp['totals'] = $this->model->getTotalesProductosFactura($facturaId);
        }

        echo json_encode($resp, JSON_UNESCAPED_UNICODE);
        exit;

    } catch (Throwable $e) {
        error_log("Operaciones_por_partida/bajaProducto ERROR: " . $e->getMessage());
        echo json_encode(['ok'=>false,'msg'=>'Ocurrió un error al dar de baja el producto.'], JSON_UNESCAPED_UNICODE);
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
        $permit  = ['pdf','jpg','jpeg','png','doc','docx','xls','xlsx','txt','zip','webp'];
        $maxBytes = 50 * 1024 * 1024; // 50MB (igual que tu FO)

        // ===== Insert BD por archivo =====
        $userId = $_SESSION['id_usuario'] ?? $_SESSION['usuario_id'] ?? $_SESSION['id'] ?? $_SESSION['admin_id'] ?? null;
        if ($userId === null) { error_log('OPP_DOCS_REGISTRAR sin userId.'); }

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
        echo json_encode(['status'=>'error','msg'=>'Método no permitido.'], JSON_UNESCAPED_UNICODE);
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
        echo json_encode(['status'=>'error','msg'=>'Método no permitido.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        $idDocumento = (int)($_POST['id_documento'] ?? 0);
        if ($idDocumento <= 0) {
            echo json_encode(['status'=>'warning','msg'=>'ID de documento inválido.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // 1) Traer documento de BD
        $doc = $this->model->getDocumentoPartidaById($idDocumento);
        if (!$doc) {
            echo json_encode(['status'=>'warning','msg'=>'Documento no encontrado.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $rutaRel = (string)($doc['ruta_archivo'] ?? '');
        if ($rutaRel === '') {
            echo json_encode(['status'=>'error','msg'=>'Ruta de documento inválida en BD.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // 2) Seguridad: evitar path traversal / rutas fuera del directorio esperado
        $rutaRelNorm = str_replace('\\', '/', $rutaRel);
        $prefix = 'Documents/DocumentosPartidas/';
        if (strpos($rutaRelNorm, $prefix) !== 0) {
            echo json_encode(['status'=>'error','msg'=>'Ruta no permitida.'], JSON_UNESCAPED_UNICODE);
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
            echo json_encode(['status'=>'error','msg'=>'No se pudo eliminar el registro en BD.'], JSON_UNESCAPED_UNICODE);
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
        echo json_encode(['status'=>'error','msg'=>'Error inesperado al eliminar.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}


//RUTAS
/*
 // ===================== RUTAS: SUGERENCIAS FACTURAS =====================
public function sugerirFacturasRutas()
{
    header('Content-Type: application/json; charset=utf-8');

    try {
        $term  = isset($_GET['term']) ? trim((string)$_GET['term']) : '';
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

        if ($limit < 1) $limit = 10;
        if ($limit > 25) $limit = 25;

        // Buen patrón UX: mínimo 2 chars
        if ($term === '' || mb_strlen($term) < 2) {
            echo json_encode(['ok' => true, 'data' => []], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $rows = $this->model->sugerirFacturas($term, $limit);

        echo json_encode([
            'ok'   => true,
            'data' => $rows ?: []
        ], JSON_UNESCAPED_UNICODE);
        exit;

    } catch (Throwable $e) {
        error_log("Operaciones_por_partida/sugerirFacturasRutas ERROR: " . $e->getMessage());
        echo json_encode([
            'ok'   => false,
            'msg'  => 'Ocurrió un error al buscar facturas.',
            'data' => []
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}


// ===================== RUTAS: LISTAR PRODUCTOS (TABLA) =====================
public function listarProductosRutas()
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

        // Validación suave: factura exista/activa (evita consultas raras)
        if (!$this->model->existeFacturaActiva($facturaId)) {
            echo json_encode([
                'ok'   => false,
                'msg'  => 'La factura no existe o está inactiva.',
                'data' => []
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $rows = $this->model->listarProductosRutas($facturaId, $term);

        echo json_encode([
            'ok'   => true,
            'data' => $rows ?: []
        ], JSON_UNESCAPED_UNICODE);
        exit;

    } catch (Throwable $e) {
        error_log("Operaciones_por_partida/listarProductosRutas ERROR: " . $e->getMessage());
        echo json_encode([
            'ok'   => false,
            'msg'  => 'Ocurrió un error al listar productos de rutas.',
            'data' => []
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
// ===================== RUTAS: LISTAR ENVIOS DE UN PRODUCTO =====================
public function listarEnviosProductoRutas()
{
    header('Content-Type: application/json; charset=utf-8');

    try {
        $facturaId  = isset($_GET['factura_id']) ? (int)$_GET['factura_id'] : 0;
        $productoId = isset($_GET['producto_id']) ? (int)$_GET['producto_id'] : 0;

        if ($facturaId <= 0 || $productoId <= 0) {
            echo json_encode([
                'ok'   => false,
                'msg'  => 'Parámetros inválidos.',
                'data' => []
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Puedes devolver detalle o resumen. Para UI compacta:
        // $rows = $this->model->resumenEnviosProducto($facturaId, $productoId);

        $rows = $this->model->listarEnviosProducto($facturaId, $productoId);

        echo json_encode([
            'ok'   => true,
            'data' => $rows ?: []
        ], JSON_UNESCAPED_UNICODE);
        exit;

    } catch (Throwable $e) {
        error_log("Operaciones_por_partida/listarEnviosProductoRutas ERROR: " . $e->getMessage());
        echo json_encode([
            'ok'   => false,
            'msg'  => 'Ocurrió un error al listar envíos del producto.',
            'data' => []
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}


// ==== RUTAS: LISTAR CIUDADES (DESTINOS) ====
public function listarCiudadesRutas()
{
    header('Content-Type: application/json; charset=utf-8');

    try {
        $rows = $this->model->listarCiudadesActivas();

        echo json_encode([
            'ok'   => true,
            'data' => $rows ?: []
        ], JSON_UNESCAPED_UNICODE);
        exit;

    } catch (Throwable $e) {
        error_log("Operaciones_por_partida/listarCiudadesRutas ERROR: " . $e->getMessage());
        echo json_encode([
            'ok'   => false,
            'msg'  => 'Ocurrió un error al listar ciudades.',
            'data' => []
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// ==== RUTAS: SUGERIR CAJA/FERRO (contenedores_fisicos) ====
public function sugerirFerroCajaRutas()
{
    header('Content-Type: application/json; charset=utf-8');

    try {
        $term  = isset($_GET['term']) ? trim((string)$_GET['term']) : '';
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

        if ($limit < 1) $limit = 10;
        if ($limit > 25) $limit = 25;

        if ($term === '' || mb_strlen($term) < 2) {
            echo json_encode(['ok' => true, 'data' => []], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $rows = $this->model->sugerirFisicos($term, $limit);

        echo json_encode([
            'ok'   => true,
            'data' => $rows ?: []
        ], JSON_UNESCAPED_UNICODE);
        exit;

    } catch (Throwable $e) {
        error_log("Operaciones_por_partida/sugerirFerroCajaRutas ERROR: " . $e->getMessage());
        echo json_encode([
            'ok'   => false,
            'msg'  => 'Ocurrió un error al buscar Caja/Ferro.',
            'data' => []
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

*/
}