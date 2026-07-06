<?php
require_once "Models/BitacoraOpPartidaModel.php";
class Operaciones_por_partida_envios extends Controller
{
    private const IMG_MIN = 3;
    private const IMG_MAX = 5;
    protected $bitacoraOpPartida;
    public function __construct()
    {
        parent::__construct();
        session_start();

        if (empty($_SESSION['nombre_usuario'])) {
            header('Location: ' . BASE_URL . 'admin');
            exit;
        }

        // Solo usuarios internos
        $this->requireRoles([1, 2, 11, 15]); //1=admin, 11=supervisor, 2=operador, 15=revisor
        $this->bitacoraOpPartida = new BitacoraOpPartidaModel();
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
            error_log('[BITACORA OP PARTIDA ENVIOS] ' . $e->getMessage());
            return false;
        }
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
        } catch (Throwable $e) {
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
       HELPERS IMÁGENES
       ========================================================= */

    private function obtenerUsuarioIdSesion(): ?int
    {
        $candidatos = [
            'id_usuario',
            'usuario_id',
            'idUser',
            'id'
        ];

        foreach ($candidatos as $key) {
            if (isset($_SESSION[$key]) && (int)$_SESSION[$key] > 0) {
                return (int)$_SESSION[$key];
            }
        }

        return null;
    }

    private function normalizarArchivosMultiples(?array $filesField): array
    {
        if (
            empty($filesField) ||
            !isset($filesField['name']) ||
            !is_array($filesField['name'])
        ) {
            return [];
        }

        $out = [];
        $total = count($filesField['name']);

        for ($i = 0; $i < $total; $i++) {
            $error = isset($filesField['error'][$i]) ? (int)$filesField['error'][$i] : UPLOAD_ERR_NO_FILE;

            if ($error === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $out[] = [
                'name'     => (string)($filesField['name'][$i] ?? ''),
                'type'     => (string)($filesField['type'][$i] ?? ''),
                'tmp_name' => (string)($filesField['tmp_name'][$i] ?? ''),
                'error'    => $error,
                'size'     => (int)($filesField['size'][$i] ?? 0)
            ];
        }

        return $out;
    }

    private function validarImagenesSubidas(array $imagenes): array
    {
        $permitidos = [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/webp'
        ];

        $extPermitidas = ['jpg', 'jpeg', 'png', 'webp'];
        $errores = [];

        foreach ($imagenes as $index => $img) {
            $n = $index + 1;

            if (!isset($img['error']) || (int)$img['error'] !== UPLOAD_ERR_OK) {
                $errores[] = "Imagen #{$n}: error al subir archivo.";
                continue;
            }

            if (empty($img['tmp_name']) || !is_uploaded_file($img['tmp_name'])) {
                $errores[] = "Imagen #{$n}: archivo temporal inválido.";
                continue;
            }

            $mime = $this->detectarMimeImagen($img['tmp_name'], (string)($img['type'] ?? ''));
            $ext  = strtolower(pathinfo((string)($img['name'] ?? ''), PATHINFO_EXTENSION));

            if (!in_array($mime, $permitidos, true)) {
                $errores[] = "Imagen #{$n}: formato no permitido.";
                continue;
            }

            if (!in_array($ext, $extPermitidas, true)) {
                $errores[] = "Imagen #{$n}: extensión no permitida.";
                continue;
            }

            if ((int)($img['size'] ?? 0) <= 0) {
                $errores[] = "Imagen #{$n}: archivo vacío.";
                continue;
            }
        }

        return $errores;
    }

    private function detectarMimeImagen(string $tmpPath, string $fallback = ''): string
    {
        $mime = '';

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $mime = (string)finfo_file($finfo, $tmpPath);
                finfo_close($finfo);
            }
        }

        if ($mime === '' && function_exists('mime_content_type')) {
            $mime = (string)@mime_content_type($tmpPath);
        }

        if ($mime === '') {
            $mime = trim($fallback);
        }

        return strtolower($mime);
    }

    private function obtenerCarpetaImagenesRelativa(int $envioId): string
    {
        return 'Uploads/operaciones_partida/envios/' . $envioId . '/';
    }

    private function obtenerCarpetaImagenesAbsoluta(int $envioId): string
    {
        $base = defined('UPLOAD_ROOT')
            ? rtrim(UPLOAD_ROOT, '/\\')
            : rtrim(dirname(__DIR__, 2), '/\\');

        return $base . DIRECTORY_SEPARATOR . 'Uploads'
            . DIRECTORY_SEPARATOR . 'operaciones_partida'
            . DIRECTORY_SEPARATOR . 'envios'
            . DIRECTORY_SEPARATOR . $envioId
            . DIRECTORY_SEPARATOR;
    }

    private function asegurarDirectorio(string $dir): bool
    {
        if (is_dir($dir)) {
            return true;
        }

        return @mkdir($dir, 0775, true) || is_dir($dir);
    }

    private function generarNombreImagenSeguro(string $nombreOriginal, int $ordenVisual): string
    {
        $ext = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
        $ext = $ext !== '' ? $ext : 'jpg';

        try {
            $rand = bin2hex(random_bytes(5));
        } catch (Throwable $e) {
            $rand = substr(md5(uniqid((string)mt_rand(), true)), 0, 10);
        }

        return 'img_' . str_pad((string)$ordenVisual, 2, '0', STR_PAD_LEFT)
            . '_' . date('Ymd_His')
            . '_' . $rand
            . '.' . $ext;
    }

    private function guardarImagenesEnvio(int $envioId, array $imagenes, ?int $subidoPor = null): array
    {
        $errores = [];
        $guardadas = [];
        $dirAbs = $this->obtenerCarpetaImagenesAbsoluta($envioId);
        $dirRel = $this->obtenerCarpetaImagenesRelativa($envioId);

        if (!$this->asegurarDirectorio($dirAbs)) {
            return [
                'ok'        => false,
                'guardadas' => [],
                'errores'   => ['No fue posible crear la carpeta de imágenes del envío.']
            ];
        }

        $orden = $this->model->obtenerSiguienteOrdenImagen($envioId);

        foreach ($imagenes as $index => $img) {
            $mime = $this->detectarMimeImagen($img['tmp_name'], (string)($img['type'] ?? ''));
            $nombreGenerado = $this->generarNombreImagenSeguro((string)$img['name'], $orden);
            $rutaAbs = $dirAbs . $nombreGenerado;
            $rutaRel = $dirRel . $nombreGenerado;

            if (!@move_uploaded_file($img['tmp_name'], $rutaAbs)) {
                $errores[] = 'No fue posible mover la imagen #' . ($index + 1) . ' al servidor.';
                continue;
            }

            $idImagen = $this->model->registrarEnvioImagen(
                $envioId,
                (string)$img['name'],
                $rutaRel,
                $mime !== '' ? $mime : null,
                (int)($img['size'] ?? 0),
                $orden,
                $subidoPor
            );

            if (!$idImagen) {
                @unlink($rutaAbs);
                $errores[] = 'No fue posible registrar la imagen #' . ($index + 1) . ' en la base de datos.';
                continue;
            }

            $guardadas[] = [
                'id_imagen'      => (int)$idImagen,
                'nombre_archivo' => (string)$img['name'],
                'ruta_archivo'   => $rutaRel,
                'orden_visual'   => $orden
            ];

            $orden++;
        }

        return [
            'ok'        => empty($errores),
            'guardadas' => $guardadas,
            'errores'   => $errores
        ];
    }

    private function eliminarArchivoFisicoSiExiste(string $rutaRelativa): void
    {
        if ($rutaRelativa === '') {
            return;
        }

        $base = defined('UPLOAD_ROOT')
            ? rtrim(UPLOAD_ROOT, '/\\')
            : rtrim(dirname(__DIR__, 2), '/\\');

        $rutaRelativa = ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $rutaRelativa), DIRECTORY_SEPARATOR);
        $rutaAbs = $base . DIRECTORY_SEPARATOR . $rutaRelativa;

        if (is_file($rutaAbs)) {
            @unlink($rutaAbs);
        }
    }

    private function parsearIdsImagenesEliminadas($raw): array
    {
        if (is_array($raw)) {
            $ids = $raw;
        } else {
            $raw = trim((string)$raw);

            if ($raw === '') {
                return [];
            }

            $json = json_decode($raw, true);

            if (is_array($json)) {
                $ids = $json;
            } else {
                $ids = explode(',', $raw);
            }
        }

        $ids = array_map('intval', $ids);
        $ids = array_values(array_filter($ids, function ($id) {
            return $id > 0;
        }));

        return array_values(array_unique($ids));
    }

    /* =========================================================
       CATÁLOGOS / BÚSQUEDAS
       ========================================================= */

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

    public function registrar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->methodNotAllowed();
        }

        $contenedorFisicoId = isset($_POST['contenedor_fisico_id']) ? (int)$_POST['contenedor_fisico_id'] : 0;
        $numeroFerro        = isset($_POST['numero_ferro']) ? trim((string)$_POST['numero_ferro']) : '';

        $destinoCiudadId = null;
        if (isset($_POST['destino_ciudad_id']) && $_POST['destino_ciudad_id'] !== '') {
            $destinoCiudadId = (int)$_POST['destino_ciudad_id'];
        } elseif (isset($_POST['destino_id']) && $_POST['destino_id'] !== '') {
            $destinoCiudadId = (int)$_POST['destino_id'];
        }

        $fechaEnvio = isset($_POST['fecha_envio']) ? trim((string)$_POST['fecha_envio']) : '';

        $estatusEnvio = '';
        if (isset($_POST['estatus_envio'])) {
            $estatusEnvio = trim((string)$_POST['estatus_envio']);
        } elseif (isset($_POST['estatus'])) {
            $estatusEnvio = trim((string)$_POST['estatus']);
        }

        $transportistaId = isset($_POST['transportista_id']) && $_POST['transportista_id'] !== ''
            ? (int)$_POST['transportista_id']
            : null;
        $candado = isset($_POST['candado']) ? trim((string)$_POST['candado']) : '';
        $notas = '';
        if (isset($_POST['notas'])) {
            $notas = trim((string)$_POST['notas']);
        } elseif (isset($_POST['nota'])) {
            $notas = trim((string)$_POST['nota']);
        }

        $detalleRaw = isset($_POST['detalle']) ? $_POST['detalle'] : '[]';
        $detalle = json_decode($detalleRaw, true);

        $imagenes = $this->normalizarArchivosMultiples($_FILES['imagenes'] ?? null);

        if ($contenedorFisicoId <= 0 && $numeroFerro === '') {
            $this->jsonResponse([
                'ok'  => false,
                'msg' => 'Debes seleccionar o escribir un ferro/caja válido.'
            ], 400);
        }

        if ($fechaEnvio === '') {
            $this->jsonResponse([
                'ok'  => false,
                'msg' => 'La fecha de envío es obligatoria.'
            ], 400);
        }

        if ($estatusEnvio === '') {
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

        if (count($imagenes) < self::IMG_MIN || count($imagenes) > self::IMG_MAX) {
            $this->jsonResponse([
                'ok'  => false,
                'msg' => 'Debes adjuntar entre ' . self::IMG_MIN . ' y ' . self::IMG_MAX . ' imágenes.'
            ], 400);
        }

        $erroresImagenes = $this->validarImagenesSubidas($imagenes);
        if (!empty($erroresImagenes)) {
            $this->jsonResponse([
                'ok'      => false,
                'msg'     => 'Hay errores en las imágenes seleccionadas.',
                'errores' => $erroresImagenes
            ], 400);
        }

        try {
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

            $detalleValido = [];
            $erroresDetalle = [];

            foreach ($detalle as $index => $item) {
                $facturaId     = isset($item['factura_id']) ? (int)$item['factura_id'] : 0;
                $productoId    = isset($item['producto_id']) ? (int)$item['producto_id'] : 0;
                $cajasEnviadas = isset($item['cajas_enviadas']) ? (int)$item['cajas_enviadas'] : 0;
                $notasDetalle  = isset($item['notas_detalle']) ? trim((string)$item['notas_detalle']) : '';

                if ($facturaId <= 0 || $productoId <= 0 || $cajasEnviadas <= 0) {
                    $erroresDetalle[] = 'Detalle #' . ($index + 1) . ': datos incompletos.';
                    continue;
                }

                $producto = $this->model->obtenerProductoConDisponibilidad($productoId);

                if (!$producto) {
                    $erroresDetalle[] = 'Detalle #' . ($index + 1) . ': producto no encontrado o inactivo.';
                    continue;
                }

                if ((int)$producto['factura_id'] !== $facturaId) {
                    $erroresDetalle[] = 'Detalle #' . ($index + 1) . ': el producto no pertenece a la factura indicada.';
                    continue;
                }

                $cajasRestantes = (int)($producto['cajas_restantes'] ?? 0);
                if ($cajasEnviadas > $cajasRestantes) {
                    $erroresDetalle[] = 'Detalle #' . ($index + 1) . ': cajas solicitadas mayores a las disponibles.';
                    continue;
                }

                $detalleValido[] = [
                    'factura_id'     => $facturaId,
                    'producto_id'    => $productoId,
                    'cajas_enviadas' => $cajasEnviadas,
                    'notas_detalle'  => $notasDetalle
                ];
            }

            if (empty($detalleValido)) {
                $this->jsonResponse([
                    'ok'      => false,
                    'msg'     => 'No hay detalles válidos para registrar el envío.',
                    'errores' => $erroresDetalle
                ], 400);
            }

            $envioId = $this->model->registrarEnvio(
                $contenedorFisicoId,
                $destinoCiudadId,
                $fechaEnvio,
                $estatusEnvio,
                $transportistaId,
                $candado,
                $notas
            );

            if (empty($envioId)) {
                $this->jsonResponse([
                    'ok'  => false,
                    'msg' => 'No fue posible registrar el envío.'
                ], 500);
            }

            $insertados = 0;
            $erroresDetalleSave = [];

            foreach ($detalleValido as $index => $item) {
                $okDetalle = $this->model->registrarEnvioDetalle(
                    (int)$envioId,
                    (int)$item['factura_id'],
                    (int)$item['producto_id'],
                    (int)$item['cajas_enviadas'],
                    (string)$item['notas_detalle']
                );

                if ($okDetalle) {
                    $insertados++;
                } else {
                    $erroresDetalleSave[] = 'Detalle #' . ($index + 1) . ': no fue posible guardar el detalle.';
                }
            }

            if ($insertados <= 0) {
                $this->jsonResponse([
                    'ok'      => false,
                    'msg'     => 'El envío fue creado, pero no se registró ningún detalle válido.',
                    'envio_id' => (int)$envioId,
                    'errores' => array_merge($erroresDetalle, $erroresDetalleSave)
                ], 400);
            }

            $resultadoImagenes = $this->guardarImagenesEnvio(
                (int)$envioId,
                $imagenes,
                $this->obtenerUsuarioIdSesion()
            );

            if (count($resultadoImagenes['guardadas']) < self::IMG_MIN) {
                $this->jsonResponse([
                    'ok'               => false,
                    'msg'              => 'El envío fue registrado, pero no se logró guardar el mínimo de imágenes requerido.',
                    'envio_id'         => (int)$envioId,
                    'detalles_ok'      => (int)$insertados,
                    'imagenes_ok'      => count($resultadoImagenes['guardadas']),
                    'errores_detalle'  => array_merge($erroresDetalle, $erroresDetalleSave),
                    'errores_imagenes' => $resultadoImagenes['errores']
                ], 400);
            }
            $this->registrarBitacoraPartida(
                'op_partida_envios',
                'crear',
                'operaciones_partida_envios',
                (int)$envioId,
                $this->bitacoraOpPartida->desc('envio', 'creado', [
                    'envio_id' => (int)$envioId,
                    'contenedor_fisico_id' => (int)$contenedorFisicoId,
                    'transportista_id' => (int)$transportistaId,
                    'fecha_envio' => $fechaEnvio,
                    'estatus_envio' => $estatusEnvio,
                    'detalles_ok' => (int)$insertados,
                    'imagenes_ok' => count($resultadoImagenes['guardadas'])
                ])
            );
            $this->jsonResponse([
                'ok'               => true,
                'msg'              => 'Envío registrado correctamente.',
                'envio_id'         => (int)$envioId,
                'detalles_ok'      => (int)$insertados,
                'detalles_total'   => count($detalleValido),
                'imagenes_ok'      => count($resultadoImagenes['guardadas']),
                'errores_detalle'  => array_merge($erroresDetalle, $erroresDetalleSave),
                'errores_imagenes' => $resultadoImagenes['errores']
            ]);
        } catch (Throwable $e) {
            $this->jsonResponse([
                'ok'  => false,
                'msg' => 'Error al registrar el envío.',
                'err' => $e->getMessage()
            ], 500);
        }
    }

    /* =========================================================
       OBTENER
       ========================================================= */

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

            $imagenes = isset($envio['imagenes']) && is_array($envio['imagenes'])
                ? $envio['imagenes']
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

            $imagenesOut = array_map(function ($row) {
                return [
                    'id_imagen'      => (int)($row['id_imagen'] ?? 0),
                    'envio_id'       => (int)($row['envio_id'] ?? 0),
                    'nombre_archivo' => (string)($row['nombre_archivo'] ?? ''),
                    'ruta_archivo'   => (string)($row['ruta_archivo'] ?? ''),
                    'mime_type'      => (string)($row['mime_type'] ?? ''),
                    'tamano_bytes'   => (int)($row['tamano_bytes'] ?? 0),
                    'orden_visual'   => (int)($row['orden_visual'] ?? 0),
                    'subido_por'     => isset($row['subido_por']) ? (int)$row['subido_por'] : null,
                    'fecha_subida'   => (string)($row['fecha_subida'] ?? '')
                ];
            }, $imagenes);

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
                    'candado'              => (string)($envio['candado'] ?? ''),
                    'detalle'              => $detalleOut,
                    'imagenes'             => $imagenesOut
                ]
            ]);
        } catch (Throwable $e) {
            $this->jsonResponse([
                'ok'  => false,
                'msg' => 'Error al obtener el envío.',
                'err' => $e->getMessage()
            ], 500);
        }
    }

    /* =========================================================
       ACTUALIZAR
       ========================================================= */

    public function actualizar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->methodNotAllowed();
        }

        $envioId = isset($_POST['id_envio']) ? (int)$_POST['id_envio'] : 0;

        $fechaEnvio = isset($_POST['fecha_envio'])
            ? trim((string)$_POST['fecha_envio'])
            : '';
        $estatusEnvio = '';
        if (isset($_POST['estatus_envio'])) {
            $estatusEnvio = trim((string)$_POST['estatus_envio']);
        } elseif (isset($_POST['estatus'])) {
            $estatusEnvio = trim((string)$_POST['estatus']);
        }

        $notas = '';
        if (isset($_POST['notas'])) {
            $notas = trim((string)$_POST['notas']);
        } elseif (isset($_POST['nota'])) {
            $notas = trim((string)$_POST['nota']);
        }
        $candado = isset($_POST['candado']) ? trim((string)$_POST['candado']) : '';


        $imagenesEliminadasRaw = $_POST['imagenes_eliminadas'] ?? '';
        $idsImagenesEliminar   = $this->parsearIdsImagenesEliminadas($imagenesEliminadasRaw);
        $imagenesNuevas        = $this->normalizarArchivosMultiples($_FILES['imagenes'] ?? null);

        if ($envioId <= 0) {
            $this->jsonResponse([
                'ok'  => false,
                'msg' => 'ID de envío inválido.'
            ], 400);
        }
        if ($fechaEnvio === '') {
            $this->jsonResponse([
                'ok'  => false,
                'msg' => 'La fecha de envío es obligatoria.'
            ], 400);
        }
        if ($estatusEnvio === '') {
            $this->jsonResponse([
                'ok'  => false,
                'msg' => 'El estatus del envío es obligatorio.'
            ], 400);
        }

        $erroresImagenes = $this->validarImagenesSubidas($imagenesNuevas);
        if (!empty($erroresImagenes)) {
            $this->jsonResponse([
                'ok'      => false,
                'msg'     => 'Hay errores en las imágenes seleccionadas.',
                'errores' => $erroresImagenes
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

            $imagenesActuales = isset($envio['imagenes']) && is_array($envio['imagenes'])
                ? $envio['imagenes']
                : [];

            $mapImagenesActuales = [];
            foreach ($imagenesActuales as $img) {
                $idImg = (int)($img['id_imagen'] ?? 0);
                if ($idImg > 0) {
                    $mapImagenesActuales[$idImg] = $img;
                }
            }

            $idsValidosEliminar = [];
            $rutasEliminar = [];

            foreach ($idsImagenesEliminar as $idImg) {
                if (isset($mapImagenesActuales[$idImg])) {
                    $idsValidosEliminar[] = $idImg;
                    $rutasEliminar[] = (string)($mapImagenesActuales[$idImg]['ruta_archivo'] ?? '');
                }
            }

            $idsValidosEliminar = array_values(array_unique($idsValidosEliminar));
            $cantidadActual = count($imagenesActuales);
            $cantidadEliminar = count($idsValidosEliminar);
            $cantidadNuevas = count($imagenesNuevas);
            $cantidadFinal = $cantidadActual - $cantidadEliminar + $cantidadNuevas;

            if ($cantidadFinal < self::IMG_MIN || $cantidadFinal > self::IMG_MAX) {
                $this->jsonResponse([
                    'ok'  => false,
                    'msg' => 'Después de actualizar, el envío debe conservar entre ' . self::IMG_MIN . ' y ' . self::IMG_MAX . ' imágenes.',
                    'meta' => [
                        'imagenes_actuales' => $cantidadActual,
                        'imagenes_eliminar' => $cantidadEliminar,
                        'imagenes_nuevas'   => $cantidadNuevas,
                        'imagenes_final'    => $cantidadFinal
                    ]
                ], 400);
            }

            $estatusActual = (string)($envio['estatus_envio'] ?? '');
            $notasActual   = (string)($envio['notas'] ?? '');
            $fechaEnvioActual = (string)($envio['fecha_envio'] ?? '');
            $candadoActual = (string)($envio['candado'] ?? '');

            $requiereActualizarEnvio = (
                trim($fechaEnvioActual) !== trim($fechaEnvio) ||
                trim($estatusActual) !== trim($estatusEnvio) ||
                trim($notasActual) !== trim($notas)
                || trim($candadoActual) !== trim($candado)
            );

            if ($requiereActualizarEnvio) {
                $ok = $this->model->actualizarEnvioEditable(
                    $envioId,

                    $estatusEnvio,
                    $notas,
                    $fechaEnvio,
                    $candado
                );

                if (!$ok) {
                    $this->jsonResponse([
                        'ok'  => false,
                        'msg' => 'No fue posible actualizar el envío.'
                    ], 500);
                }
            }

            $imagenesDesactivadas = 0;

            if (!empty($idsValidosEliminar)) {
                $okDelete = $this->model->desactivarImagenesEnvio($idsValidosEliminar, $envioId);

                if (!$okDelete) {
                    $this->jsonResponse([
                        'ok'  => false,
                        'msg' => 'No fue posible actualizar las imágenes eliminadas.'
                    ], 500);
                }

                $imagenesDesactivadas = count($idsValidosEliminar);

                foreach ($rutasEliminar as $rutaRelativa) {
                    $this->eliminarArchivoFisicoSiExiste((string)$rutaRelativa);
                }
            }

            $resultadoImagenes = [
                'guardadas' => [],
                'errores'   => []
            ];

            if (!empty($imagenesNuevas)) {
                $resultadoImagenes = $this->guardarImagenesEnvio(
                    $envioId,
                    $imagenesNuevas,
                    $this->obtenerUsuarioIdSesion()
                );
            }

            $conteoFinal = $this->model->contarImagenesActivasEnvio($envioId);

            if ($conteoFinal < self::IMG_MIN || $conteoFinal > self::IMG_MAX) {
                $this->jsonResponse([
                    'ok'               => false,
                    'msg'              => 'La actualización dejó una cantidad inválida de imágenes en el envío.',
                    'imagenes_finales' => $conteoFinal,
                    'errores_imagenes' => $resultadoImagenes['errores']
                ], 400);
            }
            $this->registrarBitacoraPartida(
                'op_partida_envios',
                'actualizacion',
                'operaciones_partida_envios',
                (int)$envioId,
                $this->bitacoraOpPartida->desc('envio', 'actualizado', [
                    'envio_id' => (int)$envioId,
                    'fecha_envio' => $fechaEnvio,
                    'estatus_envio' => $estatusEnvio,
                    'imagenes_eliminadas' => (int)$imagenesDesactivadas,
                    'imagenes_agregadas' => count($resultadoImagenes['guardadas']),
                    'imagenes_finales' => (int)$conteoFinal
                ])
            );
            $this->jsonResponse([
                'ok'                   => true,
                'msg'                  => 'Envío actualizado correctamente.',
                'imagenes_eliminadas'  => $imagenesDesactivadas,
                'imagenes_agregadas'   => count($resultadoImagenes['guardadas']),
                'imagenes_finales'     => $conteoFinal,
                'errores_imagenes'     => $resultadoImagenes['errores']
            ]);
        } catch (Throwable $e) {
            $this->jsonResponse([
                'ok'  => false,
                'msg' => 'Error al actualizar el envío.',
                'err' => $e->getMessage()
            ], 500);
        }
    }
}
