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

}
